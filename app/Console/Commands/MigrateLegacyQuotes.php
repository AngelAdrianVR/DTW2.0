<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;
use Carbon\Carbon;

class MigrateLegacyQuotes extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:migrate-legacy-quotes';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migra cotizaciones y sus pagos asociados desde la base de datos antigua a la nueva estructura.';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info("Iniciando la migración de Cotizaciones y Pagos...");

        // Confirmación para limpiar las tablas nuevas
        if ($this->confirm('¿Deseas eliminar TODOS los datos de las tablas "quotes" y "client_payments" antes de empezar? Se recomienda para una migración limpia.', true)) {
            try {
                DB::statement('SET FOREIGN_KEY_CHECKS=0;');
                // Es importante truncar los pagos primero por la llave foránea
                DB::table('client_payments')->truncate();
                DB::table('quotes')->truncate();
                DB::statement('SET FOREIGN_KEY_CHECKS=1;');
                $this->warn('Las tablas "quotes" y "client_payments" han sido limpiadas.');
            } catch (Throwable $e) {
                $this->error("Error al limpiar las tablas: " . $e->getMessage());
                return 1;
            }
        }

        try {
            $oldDb = DB::connection('mysql_old');
            $newDb = DB::connection('mysql');

            $newDb->transaction(function () use ($oldDb, $newDb) {
                $this->line('');
                $this->info('Migrando cotizaciones...');
                $old_quotes = $oldDb->table('quotes')->orderBy('id')->get();
                $progressBar = $this->output->createProgressBar($old_quotes->count());

                foreach ($old_quotes as $quote) {
                    // 1. Encontrar el cliente correspondiente en la nueva BD
                    $parent_name = null;
                    if ($quote->client_id) {
                        $old_parent = $oldDb->table('clients')->where('id', $quote->client_id)->first();
                        if ($old_parent) $parent_name = $old_parent->name;
                    } elseif ($quote->prospect_id) {
                        $old_parent = $oldDb->table('prospects')->where('id', $quote->prospect_id)->first();
                        if ($old_parent) $parent_name = $old_parent->name;
                    }

                    if (!$parent_name) {
                        $this->warn("\n -> Advertencia: No se encontró cliente/prospecto para la cotización '{$quote->name}' (ID antiguo: {$quote->id}). Se omitirá.");
                        $progressBar->advance();
                        continue;
                    }

                    $new_client = $newDb->table('clients')->where('name', $parent_name)->first();

                    if (!$new_client) {
                        $this->warn("\n -> Advertencia: No se encontró el cliente '{$parent_name}' en la nueva DB para la cotización '{$quote->name}'. Se omitirá.");
                        $progressBar->advance();
                        continue;
                    }

                    // 2. Mapear datos y verificar usuario
                    $description = trim($quote->description . "\n\n" . "Características Adicionales:\n" . $quote->features);
                    $status = $this->mapStatus($quote);
                    $valid_until = Carbon::parse($quote->created_at)->addDays($quote->offer_validity_days)->toDateString();
                    
                    // Verificación de existencia del usuario en la nueva base de datos
                    $user_exists = $newDb->table('users')->where('id', $quote->user_id)->exists();
                    $new_user_id = $user_exists ? $quote->user_id : null;

                    if (!$user_exists) {
                        $this->warn("\n -> Advertencia: El usuario creador con ID antiguo '{$quote->user_id}' para la cotización '{$quote->name}' no se encontró en la nueva base de datos. El campo 'user_id' se dejará nulo.");
                    }

                    // 3. Insertar la cotización y obtener su nuevo ID
                    $new_quote_id = $newDb->table('quotes')->insertGetId([
                        'client_id' => $new_client->id,
                        'user_id' => $new_user_id, // Usar el ID verificado o nulo
                        'title' => $quote->name,
                        'description' => $description,
                        'work_days' => $quote->total_work_days,
                        'percentage_discount' => $quote->percentage_discount,
                        'payment_type' => $quote->payment_type,
                        'amount' => $quote->total_cost,
                        'status' => $status,
                        'origin' => 'Interno', // Valor fijo como se solicitó
                        'valid_until' => $valid_until,
                        'show_process' => $quote->show_process,
                        'show_benefits' => $quote->show_benefits,
                        'show_bank_info' => $quote->show_bank_info,
                        'created_at' => $quote->created_at,
                        'updated_at' => $quote->updated_at,
                    ]);

                    // 4. Si la cotización estaba pagada, crear el registro de pago
                    if ($quote->paid_at) {
                        $newDb->table('client_payments')->insert([
                            'client_id' => $new_client->id,
                            'quote_id' => $new_quote_id,
                            'amount' => $quote->total_cost,
                            'payment_date' => Carbon::parse($quote->paid_at)->toDateString(),
                            'notes' => 'Pago registrado durante migración desde sistema anterior.',
                            'created_at' => $quote->paid_at,
                            'updated_at' => $quote->paid_at,
                        ]);
                    }

                    $progressBar->advance();
                }

                $progressBar->finish();
                $this->info(' -> Cotizaciones migradas con éxito.');
            });

            $this->line('');
            $this->info("\n¡MIGRACIÓN DE COTIZACIONES COMPLETADA EXITOSAMENTE!");

        } catch (Throwable $e) {
            $this->error("\n\nERROR DURANTE LA MIGRACIÓN: " . $e->getMessage());
            $this->error("No se realizó ningún cambio en la nueva base de datos. Revisa el error y vuelve a intentarlo.");
            Log::error('Error en migración de cotizaciones: ' . $e->getFile() . ' en línea ' . $e->getLine() . ' - ' . $e->getMessage());
            return 1;
        }

        return 0;
    }

    /**
     * Determina el estado de la cotización basado en las fechas de la tabla antigua.
     *
     * @param object $quote
     * @return string
     */
    private function mapStatus(object $quote): string
    {
        if ($quote->paid_at) {
            return 'Pagado';
        }
        if ($quote->authorized_at) {
            return 'Aceptado';
        }
        if ($quote->rejected_at) {
            return 'Rechazado';
        }
        if ($quote->sent_at) {
            return 'Enviado';
        }
        return 'Pendiente';
    }
}

