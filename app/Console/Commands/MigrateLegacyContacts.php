<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class MigrateLegacyContacts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:migrate-legacy-contacts';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migra los datos de contactos desde la base de datos antigua a la nueva estructura.';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info("Iniciando la migración de Contactos...");

        // Confirmación para limpiar la tabla nueva
        if ($this->confirm('¿Deseas eliminar TODOS los datos de la tabla "contacts" antes de empezar? Se recomienda para una migración limpia.', true)) {
            try {
                DB::statement('SET FOREIGN_KEY_CHECKS=0;');
                DB::table('contacts')->truncate();
                DB::statement('SET FOREIGN_KEY_CHECKS=1;');
                $this->warn('La tabla "contacts" ha sido limpiada.');
            } catch (Throwable $e) {
                $this->error("Error al limpiar la tabla: " . $e->getMessage());
                return 1;
            }
        }

        try {
            // Conexiones a las bases de datos
            $oldDb = DB::connection('mysql_old');
            $newDb = DB::connection('mysql');

            $newDb->transaction(function () use ($oldDb, $newDb) {
                $this->line('');
                $this->info('Migrando contactos...');
                $old_contacts = $oldDb->table('contacts')->orderBy('id')->get();
                $progressBar = $this->output->createProgressBar($old_contacts->count());

                foreach ($old_contacts as $contact) {
                    // 1. Encontrar el nombre de la entidad padre (Cliente o Prospecto) en la BD antigua
                    $parent_name = null;
                    $parent_table = '';

                    if (str_contains($contact->contactable_type, 'Client')) {
                        $parent_table = 'clients';
                    } elseif (str_contains($contact->contactable_type, 'Prospect')) {
                        $parent_table = 'prospects';
                    }

                    if ($parent_table) {
                        $old_parent = $oldDb->table($parent_table)->where('id', $contact->contactable_id)->first();
                        if ($old_parent) {
                            $parent_name = $old_parent->name;
                        }
                    }

                    if (!$parent_name) {
                        $this->warn(" -> Advertencia: No se encontró la entidad padre para el contacto '{$contact->name}' (ID antiguo: {$contact->id}). Se omitirá.");
                        $progressBar->advance();
                        continue;
                    }

                    // 2. Encontrar el cliente correspondiente en la nueva BD por su nombre
                    $new_client = $newDb->table('clients')->where('name', $parent_name)->first();

                    if (!$new_client) {
                        $this->warn(" -> Advertencia: No se encontró el cliente '{$parent_name}' en la nueva DB para el contacto '{$contact->name}' (ID antiguo: {$contact->id}). Se omitirá.");
                        $progressBar->advance();
                        continue;
                    }
                    
                    $new_contactable_id = $new_client->id;
                    $new_contactable_type = 'App\\Models\\Client'; // El nuevo tipo siempre es Cliente

                    // 3. Insertar el contacto principal con el ID correcto
                    $newDb->table('contacts')->insert([
                        'contactable_id' => $new_contactable_id,
                        'contactable_type' => $new_contactable_type,
                        'name' => $contact->name,
                        'email' => $contact->email,
                        'phone' => $contact->phone,
                        'position' => null, // Campo nuevo, no existe en la data antigua
                        'created_at' => $contact->created_at,
                        'updated_at' => $contact->updated_at,
                    ]);

                    // 4. Procesar correos adicionales
                    if (!empty($contact->additional_emails)) {
                        $additional_emails = json_decode($contact->additional_emails, true);
                        if (is_array($additional_emails)) {
                            foreach ($additional_emails as $additional_email) {
                                if (empty(trim($additional_email))) continue;
                                $newDb->table('contacts')->insert([
                                    'contactable_id' => $new_contactable_id,
                                    'contactable_type' => $new_contactable_type,
                                    'name' => $contact->name . ' (Email Adicional)',
                                    'email' => $additional_email,
                                    'phone' => null,
                                    'position' => null,
                                    'created_at' => $contact->created_at,
                                    'updated_at' => $contact->updated_at,
                                ]);
                            }
                        }
                    }
                    
                    // 5. Procesar teléfonos adicionales
                    if (!empty($contact->additional_phones)) {
                        $additional_phones = json_decode($contact->additional_phones, true);
                         if (is_array($additional_phones)) {
                            foreach ($additional_phones as $additional_phone) {
                                if (empty(trim($additional_phone))) continue;
                                $newDb->table('contacts')->insert([
                                    'contactable_id' => $new_contactable_id,
                                    'contactable_type' => $new_contactable_type,
                                    'name' => $contact->name . ' (Tel. Adicional)',
                                    'email' => null,
                                    'phone' => $additional_phone,
                                    'position' => null,
                                    'created_at' => $contact->created_at,
                                    'updated_at' => $contact->updated_at,
                                ]);
                            }
                        }
                    }

                    $progressBar->advance();
                }

                $progressBar->finish();
                $this->info(' -> Contactos migrados con éxito.');
            });

            $this->line('');
            $this->info("\n¡MIGRACIÓN DE CONTACTOS COMPLETADA EXITOSAMENTE!");

        } catch (Throwable $e) {
            $this->error("\n\nERROR DURANTE LA MIGRACIÓN: " . $e->getMessage());
            $this->error("No se realizó ningún cambio en la nueva base de datos. Revisa el error y vuelve a intentarlo.");
            Log::error('Error en migración de contactos: ' . $e->getFile() . ' en línea ' . $e->getLine() . ' - ' . $e->getMessage());
            return 1;
        }

        return 0;
    }
}

