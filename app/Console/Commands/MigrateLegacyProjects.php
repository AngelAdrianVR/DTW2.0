<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class MigrateLegacyProjects extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:migrate-legacy-projects';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migra los datos de proyectos, tareas y miembros desde la base de datos antigua a la nueva estructura.';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info("Iniciando la migración del Módulo de Proyectos...");

        if ($this->confirm('¿Deseas eliminar TODOS los datos de "projects", "project_members", "tasks" y "time_logs" antes de empezar?', true)) {
            try {
                DB::statement('SET FOREIGN_key_CHECKS=0;');
                DB::table('time_logs')->truncate();
                DB::table('tasks')->truncate();
                DB::table('project_members')->truncate();
                DB::table('projects')->truncate();
                DB::statement('SET FOREIGN_key_CHECKS=1;');
                $this->warn('Las tablas de destino han sido limpiadas.');
            } catch (Throwable $e) {
                $this->error("Error al limpiar las tablas: " . $e->getMessage());
                return 1;
            }
        }

        try {
            $oldDb = DB::connection('mysql_old');
            $newDb = DB::connection('mysql');

            $newDb->transaction(function () use ($oldDb, $newDb) {
                
                $newDb->statement('SET FOREIGN_key_CHECKS=0;');

                // --- 1. Migrar Proyectos ---
                $this->line('');
                $this->info('Migrando Proyectos...');
                $old_projects = $oldDb->table('projects')->orderBy('id')->get();
                $progressBar = $this->output->createProgressBar($old_projects->count());

                foreach ($old_projects as $project) {
                    $quoteId = null;
                    if ($project->quote_id) {
                       $quoteId = $oldDb->table('quotes')->where('id', $project->quote_id)->exists() ? $project->quote_id : null;
                    }
                    
                    $clientId = null;
                    if ($project->client_id) {
                        $clientId = $oldDb->table('clients')->where('id', $project->client_id)->exists() ? $project->client_id : null;
                    }

                    $newDb->table('projects')->insert([
                        'id' => $project->id,
                        'client_id' => $clientId,
                        'quote_id' => $quoteId,
                        'name' => $project->name,
                        'description' => $project->description,
                        'status' => $this->mapProjectStatus($project->state),
                        'start_date' => $project->start_date,
                        'end_date' => $project->finish_date,
                        'budget' => $project->price,
                        'total_invested_minutes' => 0, // Se inicializa en 0
                        'created_at' => $project->created_at,
                        'updated_at' => $project->updated_at,
                    ]);
                    $progressBar->advance();
                }
                $progressBar->finish();
                $this->info(' -> Proyectos migrados con éxito.');

                // --- NUEVO: Crear un mapa de IDs de usuarios ---
                $this->line('');
                $this->info('Creando mapa de correspondencia de usuarios (antiguo ID -> nuevo ID)...');
                $oldUsers = $oldDb->table('users')->select('id', 'name')->get();
                $newUsers = $newDb->table('users')->select('id', 'name')->get()->keyBy('name');
                $userIdMap = [];
                foreach ($oldUsers as $oldUser) {
                    if (isset($newUsers[$oldUser->name])) {
                        $userIdMap[$oldUser->id] = $newUsers[$oldUser->name]->id;
                    }
                }
                $this->info(' -> Mapa de usuarios creado.');

                // --- 2. Migrar Miembros del Proyecto ---
                $this->line('');
                $this->info('Migrando Miembros de Proyectos...');
                $progressBar = $this->output->createProgressBar($old_projects->count());
                foreach ($old_projects as $project) {
                    if ($project->responsible_id) {
                        $newResponsibleId = $userIdMap[$project->responsible_id] ?? null;

                        if ($newResponsibleId) {
                            $newDb->table('project_members')->insertOrIgnore([
                                'project_id' => $project->id,
                                'user_id' => $newResponsibleId,
                                'created_at' => $project->created_at,
                                'updated_at' => $project->updated_at,
                            ]);
                        }
                    }
                    $progressBar->advance();
                }
                $progressBar->finish();
                $this->info(' -> Miembros de proyectos migrados con éxito.');

                // --- 3. Migrar Tareas ---
                $this->line('');
                $this->info('Migrando Tareas...');
                $old_tasks = $oldDb->table('project_tasks')->orderBy('id')->get();
                $progressBar = $this->output->createProgressBar($old_tasks->count());
                foreach ($old_tasks as $task) {
                    $newAssignedToId = $userIdMap[$task->user_id] ?? null;

                    $newDb->table('tasks')->insert([
                        'id' => $task->id,
                        'project_id' => $task->project_id,
                        'assigned_to' => $newAssignedToId,
                        'title' => $task->title,
                        'description' => $task->description,
                        'estimated_hours' => null,
                        'total_invested_minutes' => $task->minutes,
                        'status' => $this->mapTaskStatus($task->status, $task->is_paused),
                        'priority' => $this->mapTaskPriority($task->priority),
                        'due_date' => $task->end_date,
                        'created_at' => $task->created_at,
                        'updated_at' => $task->updated_at,
                    ]);
                    $progressBar->advance();
                }
                $progressBar->finish();
                $this->info(' -> Tareas migradas con éxito.');

                // --- 4. Migrar Registros de Tiempo (Time Logs) ---
                $this->line('');
                $this->info('Migrando Registros de Tiempo...');
                $old_tasks_with_time = $oldDb->table('project_tasks')->where('minutes', '>', 0)->whereNotNull('started_at')->orderBy('id')->get();
                $progressBar = $this->output->createProgressBar($old_tasks_with_time->count());
                foreach ($old_tasks_with_time as $task) {
                    $newUserId = $userIdMap[$task->user_id] ?? null;

                    if ($newUserId) {
                        $newDb->table('time_logs')->insert([
                            'task_id' => $task->id,
                            'user_id' => $newUserId,
                            'start_time' => $task->started_at,
                            'end_time' => $task->paused_at,
                            'duration_minutes' => $task->minutes,
                            'notes' => 'Registro migrado desde el sistema anterior.',
                            'created_at' => $task->created_at,
                            'updated_at' => $task->updated_at,
                        ]);
                    }
                    $progressBar->advance();
                }
                $progressBar->finish();
                $this->info(' -> Registros de tiempo migrados con éxito.');


                // --- 5. Actualizar Cotizaciones con el ID del Proyecto ---
                $this->line('');
                $this->info('Actualizando Cotizaciones...');
                $projects_with_quotes = $oldDb->table('projects')->whereNotNull('quote_id')->get();
                $progressBar = $this->output->createProgressBar($projects_with_quotes->count());
                foreach ($projects_with_quotes as $project) {
                    $newDb->table('quotes')->where('id', $project->quote_id)->update([
                        'project_id' => $project->id
                    ]);
                    $progressBar->advance();
                }
                $progressBar->finish();
                $this->info(' -> Cotizaciones actualizadas con éxito.');

                // --- 6. Calcular y actualizar el tiempo total invertido por proyecto ---
                $this->line('');
                $this->info('Calculando y actualizando el tiempo total invertido por proyecto...');
                $project_times = $oldDb->table('project_tasks')
                    ->select('project_id', DB::raw('SUM(minutes) as total_minutes'))
                    ->groupBy('project_id')
                    ->get();
                $progressBar = $this->output->createProgressBar($project_times->count());
                foreach ($project_times as $time) {
                    $newDb->table('projects')->where('id', $time->project_id)->update([
                        'total_invested_minutes' => $time->total_minutes
                    ]);
                    $progressBar->advance();
                }
                $progressBar->finish();
                $this->info(' -> Tiempos de proyectos actualizados con éxito.');

                $newDb->statement('SET FOREIGN_key_CHECKS=1;');
            });

            $this->line('');
            $this->info("\n¡MIGRACIÓN COMPLETADA EXITOSAMENTE!");

        } catch (Throwable $e) {
            DB::connection('mysql')->statement('SET FOREIGN_key_CHECKS=1;');
            $this->error("\n\nERROR DURANTE LA MIGRACIÓN: " . $e->getMessage());
            $this->error("No se realizó ningún cambio en la nueva base de datos. Revisa el error y vuelve a intentarlo.");
            Log::error('Error en migración de proyectos: ' . $e->getFile() . ' en línea ' . $e->getLine() . ' - ' . $e->getMessage());
            return 1;
        }

        return 0;
    }

    private function mapProjectStatus(string $oldStatus): string {
        return match (strtolower($oldStatus)) {
            'en revisión', 'pendiente' => 'Pendiente',
            'en proceso' => 'En proceso',
            'terminado', 'completado' => 'Completado',
            'pausado' => 'Pausado',
            'cancelado' => 'Cancelado',
            default => 'Pendiente',
        };
    }

    private function mapTaskStatus(string $oldStatus, bool $isPaused): string {
        if ($isPaused) {
            return 'Pausada';
        }
        return match (strtolower($oldStatus)) {
            'por hacer' => 'Pendiente',
            'en progreso' => 'En proceso',
            'hecho', 'completada' => 'Completada',
            default => 'Pendiente',
        };
    }

    private function mapTaskPriority(string $oldPriority): string {
        return match (strtolower($oldPriority)) {
            'baja' => 'Baja',
            'media' => 'Media',
            'alta' => 'Alta',
            default => 'Media',
        };
    }
}

