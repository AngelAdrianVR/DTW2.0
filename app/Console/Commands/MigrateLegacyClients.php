<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class MigrateLegacyClients extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:migrate-legacy-clients';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migra los datos de clientes y prospectos desde la base de datos antigua a la nueva estructura unificada.';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info("Iniciando la migración de Clientes y Prospectos...");

        // Confirmación para limpiar la tabla nueva
        if ($this->confirm('¿Deseas eliminar TODOS los datos de la tabla "clients" antes de empezar? Se recomienda para una migración limpia.', true)) {
            try {
                DB::statement('SET FOREIGN_KEY_CHECKS=0;');
                DB::table('clients')->truncate();
                DB::statement('SET FOREIGN_KEY_CHECKS=1;');
                $this->warn('La tabla "clients" ha sido limpiada.');
            } catch (Throwable $e) {
                $this->error("Error al limpiar la tabla: " . $e->getMessage());
                return 1;
            }
        }

        try {
            // Conexiones a las bases de datos definidas en config/database.php
            $oldDb = DB::connection('mysql_old');
            $newDb = DB::connection('mysql'); // Conexión por defecto

            // Usamos una única transacción para asegurar la integridad de todos los datos.
            // Si algo falla, se revierte toda la migración.
            $newDb->transaction(function () use ($oldDb, $newDb) {

                // --- 1. Migrar la tabla `prospects` antigua ---
                $this->line('');
                $this->info('Migrando prospectos...');
                $old_prospects = $oldDb->table('prospects')->orderBy('id')->get();
                $progressBarProspects = $this->output->createProgressBar($old_prospects->count());

                foreach ($old_prospects as $prospect) {
                    $newDb->table('clients')->insert([
                        'name' => $prospect->name,
                        'tax_id' => null, // La tabla antigua de prospectos no tenía RFC/tax_id
                        'address' => trim($prospect->address . ' ' . $prospect->state),
                        'status' => 'Prospecto', // Se asigna el estado correspondiente
                        'source' => 'Migración Legacy', // Origen para identificar datos migrados
                        'created_at' => $prospect->created_at,
                        'updated_at' => $prospect->updated_at,
                    ]);
                    $progressBarProspects->advance();
                }
                $progressBarProspects->finish();
                $this->info(' -> Prospectos migrados con éxito a la tabla de clientes.');


                // --- 2. Migrar la tabla `clients` antigua ---
                $this->line('');
                $this->info('Migrando clientes...');
                $old_clients = $oldDb->table('clients')->orderBy('id')->get();
                $progressBarClients = $this->output->createProgressBar($old_clients->count());

                foreach ($old_clients as $client) {
                    $newDb->table('clients')->insert([
                        'name' => $client->name,
                        'tax_id' => $client->rfc, // Mapeo de rfc a tax_id
                        'address' => trim($client->address . ' ' . $client->state),
                        'status' => 'Cliente', // Se asigna el estado correspondiente
                        'source' => 'Migración Legacy',
                        'created_at' => $client->created_at,
                        'updated_at' => $client->updated_at,
                    ]);
                    $progressBarClients->advance();
                }
                $progressBarClients->finish();
                $this->info(' -> Clientes antiguos migrados con éxito.');
            });

            $this->line('');
            $this->info("\n¡MIGRACIÓN COMPLETADA EXITOSAMENTE!");
            $this->info("Todos los clientes y prospectos han sido transferidos a la nueva tabla 'clients'.");

        } catch (Throwable $e) {
            $this->error("\n\nERROR DURANTE LA MIGRACIÓN: " . $e->getMessage());
            $this->error("No se realizó ningún cambio en la nueva base de datos. Revisa el error y vuelve a intentarlo.");
            Log::error('Error en migración de clientes: ' . $e->getFile() . ' en línea ' . $e->getLine() . ' - ' . $e->getMessage());
            return 1;
        }

        return 0;
    }
}
