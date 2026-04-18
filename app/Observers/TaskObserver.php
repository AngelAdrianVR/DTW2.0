<?php

namespace App\Observers;

use App\Models\Task;
use App\Models\Project;

class TaskObserver
{
    /**
     * Se ejecuta cuando una tarea es creada.
     */
    public function created(Task $task)
    {
        $this->updateProjectStatus($task);
    }

    /**
     * Se ejecuta cuando una tarea es actualizada (ej. arrastraste al Kanban).
     */
    public function updated(Task $task)
    {
        // Solo verificamos si el campo de "status" fue el que cambió
        if ($task->isDirty('status')) {
            $this->updateProjectStatus($task);
        }
    }

    /**
     * Se ejecuta cuando eliminas una tarea.
     */
    public function deleted(Task $task)
    {
        $this->updateProjectStatus($task);
    }

    /**
     * Lógica central para calcular y actualizar el estatus del proyecto.
     */
    protected function updateProjectStatus(Task $task)
    {
        $project = $task->project;

        if (!$project) {
            return;
        }

        // Si el proyecto fue cancelado o pausado manualmente, no lo tocamos automáticamente
        if (in_array($project->status, ['Cancelado', 'Pausado'])) {
            return;
        }

        $totalTasks = $project->tasks()->count();

        // Si se eliminaron todas las tareas, dejamos el estatus como estaba o en Pendiente
        if ($totalTasks === 0) {
            return;
        }

        $completedTasks = $project->tasks()->where('status', 'Completada')->count();
        $inProgressTasks = $project->tasks()->where('status', 'En proceso')->count();

        // 1. Si TODAS las tareas están en Completada, pasa a Completado.
        if ($completedTasks === $totalTasks) {
            if ($project->status !== 'Completado') {
                $project->update(['status' => 'Completado']);
            }
        } else {
            // 2. Si NO están todas completadas, determinamos si debe ser En proceso o Pendiente
            if ($project->status === 'Completado') {
                // Pasó de estar Completado a regresar tareas (ej. se agregó una nueva o regresó de estado)
                $project->update(['status' => $inProgressTasks > 0 ? 'En proceso' : 'Pendiente']);
            } elseif ($project->status === 'Pendiente' && $inProgressTasks > 0) {
                // Estaba en Pendiente y se pasó una tarea a En proceso
                $project->update(['status' => 'En proceso']);
            }
        }
    }
}