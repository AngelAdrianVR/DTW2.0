<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class MigrateLegacyDeliveries extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:migrate-legacy-deliveries';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migra las entregas de producción (delivered_productions) de la BD antigua a la nueva tabla tpsp_inventory_movements.';

    /**
     * Mapeo de nombres de kits antiguos a nombres de productos nuevos.
     * @var array
     */
    private $kitMap = [
        'Pedicure Experto' => 'Kit pedicure experto',
        'Manicure Experto' => 'Kit manicure experto',
        'Pedicure Infantil' => 'Kit pedicure infantil',
        'Masaje Antiestrés' => 'Kit pedicure anti-estrés',
        'Pedicure Correctivo' => 'Kit pedicure correctivo',
        'Campo' => 'Kit Campo',
        'Campo/Protector' => 'Kit Campo/Protector',
    ];

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info("Iniciando la migración de Entregas de Producción...");

        // Confirmación para limpiar los movimientos de tipo 'Venta' y las Órdenes de Producción
        if ($this->confirm('¿Deseas eliminar TODOS los datos de "tpsp_production_orders" Y los movimientos de "Venta" de "tpsp_inventory_movements"? Se recomienda para una migración limpia.', true)) {
            try {
                // Es importante deshabilitar las llaves foráneas para truncar
                DB::statement('SET FOREIGN_KEY_CHECKS=0;');

                $deleted_count = DB::table('tpsp_inventory_movements')->where('type', 'Venta')->delete();
                $this->warn("Se eliminaron {$deleted_count} movimientos de tipo 'Venta' de la tabla 'tpsp_inventory_movements'.");

                DB::table('tpsp_production_orders')->truncate();
                $this->warn("La tabla 'tpsp_production_orders' ha sido truncada.");

                DB::statement('SET FOREIGN_KEY_CHECKS=1;');
            } catch (Throwable $e) {
                $this->error("Error al limpiar las tablas: " . $e->getMessage());
                return 1;
            }
        }

        try {
            $oldDb = DB::connection('mysql_old');
            $newDb = DB::connection('mysql');

            // --- 1. Pre-cargar el mapeo de Productos ---
            // Esto es mucho más eficiente que hacer una consulta por cada fila
            $this->line('');
            $this->info('Pre-cargando mapeo de IDs de productos de la nueva DB...');
            $productNameMap = $this->kitMap;
            $newProductNames = array_values($productNameMap);

            $newProducts = $newDb->table('tpsp_products')
                                 ->whereIn('name', $newProductNames)
                                 ->pluck('id', 'name'); // Forma un array: ['Kit pedicure experto' => 1, ...]

            // Mapeo final de nombre_antiguo => id_nuevo
            $productIdMap = [];
            foreach ($productNameMap as $oldName => $newName) {
                if (isset($newProducts[$newName])) {
                    $productIdMap[$oldName] = $newProducts[$newName];
                } else {
                    $this->warn(" -> Advertencia: No se encontró el producto nuevo '{$newName}' en la tabla 'tpsp_products'. Las entregas para '{$oldName}' se omitirán.");
                }
            }
            $this->info('Mapeo de productos cargado.');


            // --- 2. Iniciar la transacción y migrar ---
            $newDb->transaction(function () use ($oldDb, $newDb, $productIdMap) {
                $this->line('');
                $this->info('Migrando entregas de producción...');
                
                $old_deliveries = $oldDb->table('delivered_productions')->orderBy('id')->get();
                $progressBar = $this->output->createProgressBar($old_deliveries->count());

                foreach ($old_deliveries as $delivery) {
                    // 2.1. Encontrar el ID del producto nuevo
                    if (!isset($productIdMap[$delivery->kit_type])) {
                        $this->warn("\n -> Advertencia: No se encontró mapeo de ID para el kit '{$delivery->kit_type}' (ID antiguo de entrega: {$delivery->id}). Se omitirá.");
                        $progressBar->advance();
                        continue;
                    }
                    
                    $new_product_id = $productIdMap[$delivery->kit_type];

                    // 2.2. Crear la Orden de Producción primero
                    // Asumimos que la cantidad solicitada y producida es la misma, y el estado es completado
                    $new_production_order_id = $newDb->table('tpsp_production_orders')->insertGetId([
                        'product_id' => $new_product_id,
                        'order_number' => 'MIG-' . $delivery->id, // Creamos un número de orden único basado en el ID antiguo
                        'quantity_requested' => $delivery->amount,
                        'quantity_produced' => $delivery->amount,
                        'status' => 'Completado', // Ya que fue una entrega, asumimos que se completó
                        'due_date' => $delivery->created_at, // Usamos la fecha de creación como 'due_date' aprox.
                        'notes' => $delivery->notes,
                        'created_at' => $delivery->created_at,
                        'updated_at' => $delivery->updated_at,
                    ]);

                    // 2.3. Insertar el movimiento de inventario (Venta/Salida)
                    $total_price = $delivery->amount * $delivery->price;
                    
                    // IMPORTANTE: Las entregas son salidas de inventario, por lo tanto, la cantidad debe ser negativa.
                    $quantity = -$delivery->amount;

                    // 2.2. Insertar el movimiento de inventario
                    $newDb->table('tpsp_inventory_movements')->insert([
                        'product_id' => $new_product_id,
                        'quantity' => $quantity,
                        'type' => 'Venta', // Asumimos que una "entrega" es una 'Venta'
                        'unit_price' => $delivery->price,
                        'total_price' => $total_price,
                        'reference_type' => 'App\Models\tpspProductionOrder', // Valor fijo solicitado
                        'reference_id' => $new_production_order_id,   // <-- ¡Este es el ID de la orden que acabamos de crear!
                        'notes' => $delivery->notes,
                        'created_at' => $delivery->created_at,
                        'updated_at' => $delivery->updated_at,
                    ]);

                    $progressBar->advance();
                }

                $progressBar->finish();
                $this->info(' -> Entregas migradas con éxito.');
            });

            $this->line('');
            $this->info("\n¡MIGRACIÓN DE ENTREGAS DE PRODUCCIÓN COMPLETADA EXITOSAMENTE!");

        } catch (Throwable $e) {
            $this->error("\n\nERROR DURANTE LA MIGRACIÓN: " . $e->getMessage());
            $this->error("No se realizó ningún cambio en la nueva base de datos. Revisa el error y vuelve a intentarlo.");
            Log::error('Error en migración de entregas: ' . $e->getFile() . ' en línea ' . $e->getLine() . ' - ' . $e->getMessage());
            return 1;
        }

        return 0;
    }
}