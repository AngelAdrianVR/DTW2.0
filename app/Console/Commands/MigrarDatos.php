<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model; // <-- Asegúrate de que esta línea exista arriba

class MigrarDatos extends Command
{
    protected $signature = 'app:migrar-datos';
    protected $description = 'Migra los datos del ERP viejo al nuevo';

    public function handle()
    {
        // APAGAMOS LA PROTECCIÓN DE LARAVEL TEMPORALMENTE
        Model::unguard();

        // --- 1. MIGRACIÓN DE USUARIOS ---
        $this->info('Iniciando la migración de datos...');

        \DB::connection('mysql_legacy')->table('users')->orderBy('id')->chunk(200, function ($usuariosViejos) {
            foreach ($usuariosViejos as $viejo) {
                \App\Models\User::updateOrCreate(
                    ['id' => $viejo->id],
                    [
                        'name' => $viejo->name, 
                        'email' => $viejo->email,
                        'password' => $viejo->password,
                    ]
                );
            }
        });
        $this->info('¡Migración de usuarios terminada con éxito!');


        // --- 2. MIGRACIÓN DE CLIENTES ---
        $this->info('Iniciando la migración de clientes...');

        \DB::connection('mysql_legacy')->table('clients')->orderBy('id')->chunk(200, function ($clientesViejos) {
            foreach ($clientesViejos as $viejo) {
                \App\Models\Client::updateOrCreate(
                    ['id' => $viejo->id], 
                    [
                        'name' => $viejo->name,
                        'tax_id' => $viejo->tax_id, 
                        'address' => $viejo->address,
                        'status' => $viejo->status, 
                        'source' => $viejo->source,
                        'created_at' => $viejo->created_at,
                        'updated_at' => $viejo->updated_at,
                    ]
                );
            }
        });
        $this->info('¡Migración de clientes terminada con éxito!');


        // --- 3. MIGRACIÓN DE CONTACTOS ---
        $this->info('Iniciando la migración de contactos...');

        \DB::connection('mysql_legacy')->table('contacts')->orderBy('id')->chunk(200, function ($contactosViejos) {
            foreach ($contactosViejos as $viejo) {
                \App\Models\Contact::updateOrCreate(
                    ['id' => $viejo->id], 
                    [
                        'contactable_type' => $viejo->contactable_type,
                        'contactable_id'   => $viejo->contactable_id,
                        'name'             => $viejo->name,
                        'email'            => $viejo->email,
                        'phone'            => $viejo->phone,
                        'position'         => $viejo->position,
                        'created_at'       => $viejo->created_at,
                        'updated_at'       => $viejo->updated_at,
                    ]
                );
            }
        });
        $this->info('¡Migración de contactos terminada con éxito!');

        // --- 4. MIGRACIÓN DE COTIZACIONES ---
        $this->info('Iniciando la migración de cotizaciones...');

        \DB::connection('mysql_legacy')->table('quotes')->orderBy('id')->chunk(200, function ($cotizacionesViejas) {
            foreach ($cotizacionesViejas as $viejo) {
                
                \App\Models\Quote::updateOrCreate(
                    ['id' => $viejo->id], 
                    (array) $viejo // Pasamos toda la información de golpe
                );
            }
        });
        $this->info('¡Migración de cotizaciones terminada con éxito!');

        // --- 5. MIGRACIÓN DE PAGOS ---
        $this->info('Iniciando la migración de pagos...');
        \DB::connection('mysql_legacy')->table('client_payments')->orderBy('id')->chunk(200, function ($pagosViejos) {
            foreach ($pagosViejos as $viejo) {
                \App\Models\ClientPayment::updateOrCreate(['id' => $viejo->id], (array) $viejo);
            }
        });
        $this->info('¡Migración de pagos terminada!');

        // --- 4. MIGRACIÓN DE PROYECTOS ---
        $this->info('Iniciando la migración de proyectos...');

        \DB::connection('mysql_legacy')->table('projects')->orderBy('id')->chunk(200, function ($proyectosViejos) {
            foreach ($proyectosViejos as $viejo) {
                
                // TRUCO NINJA: (array) $viejo pasa todas las columnas automáticamente
                // sin tener que escribirlas una por una.
                \App\Models\Project::updateOrCreate(
                    ['id' => $viejo->id], 
                    (array) $viejo
                );
            }
        });
        $this->info('¡Migración de proyectos terminada con éxito!');

        // --- 7. MIGRACIÓN DE TAREAS ---
        $this->info('Iniciando la migración de tareas...');
        \DB::connection('mysql_legacy')->table('tasks')->orderBy('id')->chunk(200, function ($tareasViejas) {
            foreach ($tareasViejas as $viejo) {
                \App\Models\Task::updateOrCreate(['id' => $viejo->id], (array) $viejo);
            }
        });
        $this->info('¡Migración de tareas terminada!');

        // --- 8. MIGRACIÓN DE REGISTROS DE TIEMPO ---
        $this->info('Iniciando la migración de tiempos...');
        \DB::connection('mysql_legacy')->table('time_logs')->orderBy('id')->chunk(200, function ($tiemposViejos) {
            foreach ($tiemposViejos as $viejo) {
                \App\Models\TimeLog::updateOrCreate(['id' => $viejo->id], (array) $viejo);
            }
        });
        $this->info('¡Migración de tiempos terminada!');

        // --- 9. MIGRACIÓN DE MIEMBROS DE PROYECTO ---
        $this->info('Iniciando la migración de miembros de proyectos...');
        \DB::connection('mysql_legacy')->table('project_members')->orderBy('id')->chunk(200, function ($miembros) {
            foreach ($miembros as $viejo) {
                \App\Models\ProjectMember::updateOrCreate(['id' => $viejo->id], (array) $viejo);
            }
        });
        $this->info('¡Migración de miembros terminada!');

        // --- 10. MIGRACIÓN DE PRODUCTOS ---
        $this->info('Iniciando la migración de productos...');
        \DB::connection('mysql_legacy')->table('tpsp_products')->orderBy('id')->chunk(200, function ($productos) {
            foreach ($productos as $viejo) {
                // *OJO: Revisa si tu modelo se llama TpspProduct o solo Product y ajústalo aquí si es necesario
                \App\Models\TpspProduct::updateOrCreate(['id' => $viejo->id], (array) $viejo);
            }
        });
        $this->info('¡Migración de productos terminada!');

        // --- 11. MIGRACIÓN DE COMPONENTES DE KITS ---
        // (Depende de que los productos ya existan)
        $this->info('Iniciando la migración de componentes de kits...');
        \DB::connection('mysql_legacy')->table('tpsp_kit_components')->orderBy('id')->chunk(200, function ($kits) {
            foreach ($kits as $viejo) {
                \App\Models\TpspKitComponent::updateOrCreate(['id' => $viejo->id], (array) $viejo);
            }
        });
        $this->info('¡Migración de componentes terminada!');

        // --- 12. MIGRACIÓN DE ÓRDENES DE PRODUCCIÓN ---
        $this->info('Iniciando la migración de órdenes de producción...');
        \DB::connection('mysql_legacy')->table('tpsp_production_orders')->orderBy('id')->chunk(200, function ($ordenes) {
            foreach ($ordenes as $viejo) {
                \App\Models\TpspProductionOrder::updateOrCreate(['id' => $viejo->id], (array) $viejo);
            }
        });
        $this->info('¡Migración de órdenes terminada!');

        // --- 13. MIGRACIÓN DE MOVIMIENTOS DE INVENTARIO ---
        $this->info('Iniciando la migración de movimientos de inventario...');
        \DB::connection('mysql_legacy')->table('tpsp_inventory_movements')->orderBy('id')->chunk(200, function ($movimientos) {
            foreach ($movimientos as $viejo) {
                $datos = (array) $viejo; 

                // 1. Limpieza de las referencias (lo que ya habíamos arreglado)
                if ($datos['reference_id'] === null) {
                    $datos['reference_id'] = 0;
                }
                if ($datos['reference_type'] === null) {
                    $datos['reference_type'] = 'Manual'; 
                }

                // 2. TRADUCCIÓN DE TIPOS DE INVENTARIO
                switch ($datos['type']) {
                    case 'Compra':
                        // Si el sistema viejo dice "Compra", en el nuevo será "Entrada de material"
                        $datos['type'] = 'Entrada de material';
                        break;
                    case 'Entrada de material':
                    case 'Consumo_Produccion':
                    case 'Entrada_Produccion':
                    case 'Venta':
                    case 'Ajuste':
                        // Si la palabra ya coincide con tu nueva lista, no le hacemos nada (pasa directo)
                        break;
                    default:
                        // Si viene cualquier OTRA palabra vieja que no reconozcamos, 
                        // la guardamos como 'Ajuste' para que no truene el sistema.
                        $datos['type'] = 'Ajuste';
                        break;
                }

                \App\Models\TpspInventoryMovement::updateOrCreate(['id' => $viejo->id], $datos);
            }
        });
        $this->info('¡Migración de inventario terminada!');

        // --- 14. MIGRACIÓN DE CLIENTES DE HOSTING ---
        $this->info('Iniciando la migración de clientes de hosting...');
        \DB::connection('mysql_legacy')->table('hosting_clients')->orderBy('id')->chunk(200, function ($hostings) {
            foreach ($hostings as $viejo) {
                \App\Models\HostingClient::updateOrCreate(['id' => $viejo->id], (array) $viejo);
            }
        });
        $this->info('¡Migración de clientes de hosting terminada!');

        // --- 15. MIGRACIÓN DE PAGOS DE HOSTING ---
        $this->info('Iniciando la migración de pagos de hosting...');
        \DB::connection('mysql_legacy')->table('hosting_payments')->orderBy('id')->chunk(200, function ($pagos) {
            foreach ($pagos as $viejo) {
                \App\Models\HostingPayment::updateOrCreate(['id' => $viejo->id], (array) $viejo);
            }
        });
        $this->info('¡Migración de pagos de hosting terminada!');

        // --- 16. MIGRACIÓN DE CONFIGURACIONES POMODORO ---
        $this->info('Iniciando la migración de configuraciones Pomodoro...');
        \DB::connection('mysql_legacy')->table('pomodoro_settings')->orderBy('id')->chunk(200, function ($settings) {
            foreach ($settings as $viejo) {
                \App\Models\PomodoroSetting::updateOrCreate(['id' => $viejo->id], (array) $viejo);
            }
        });
        $this->info('¡Migración de configuraciones Pomodoro terminada!');

        // --- 17. MIGRACIÓN DE SESIONES POMODORO ---
        $this->info('Iniciando la migración de sesiones Pomodoro...');
        \DB::connection('mysql_legacy')->table('pomodoro_sessions')->orderBy('id')->chunk(200, function ($sessions) {
            foreach ($sessions as $viejo) {
                \App\Models\PomodoroSession::updateOrCreate(['id' => $viejo->id], (array) $viejo);
            }
        });
        $this->info('¡Migración de sesiones Pomodoro terminada!');

        // --- 18. MIGRACIÓN DE CONTENIDOS WEB ---
        $this->info('Iniciando la migración de contenidos web...');
        \DB::connection('mysql_legacy')->table('web_contents')->orderBy('id')->chunk(200, function ($contents) {
            foreach ($contents as $viejo) {
                \App\Models\WebContent::updateOrCreate(['id' => $viejo->id], (array) $viejo);
            }
        });
        $this->info('¡Migración de contenidos web terminada!');

        // --- 19. MIGRACIÓN DE MEDIA (ARCHIVOS) ---
        $this->info('Iniciando la migración de registros multimedia...');
        \DB::connection('mysql_legacy')->table('media')->orderBy('id')->chunk(200, function ($media) {
            foreach ($media as $viejo) {
                // Aquí usamos DB::table en lugar de un Modelo porque la tabla 'media' 
                // suele pertenecer a paquetes internos (como Spatie) y así evitamos errores.
                \DB::table('media')->updateOrInsert(['id' => $viejo->id], (array) $viejo);
            }
        });
        $this->info('¡Migración de multimedia terminada!');

        // VOLVEMOS A ENCENDER LA PROTECCIÓN POR BUENA PRÁCTICA
        Model::reguard();
        
    }
}