<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Task;
use App\Models\TimeLog;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TaskController extends Controller
{
    /**
     * Helper para automatizar el estado del proyecto basado en sus tareas.
     */
    private function updateProjectAutomatedStatus(Project $project)
    {
        $totalTasks = $project->tasks()->count();
        
        if ($totalTasks === 0) {
            return; 
        }

        $completedTasks = $project->tasks()->where('status', 'Completada')->count();
        $inProgressTasks = $project->tasks()->where('status', 'En proceso')->count();

        if ($completedTasks === $totalTasks) {
            $newStatus = 'Completado';
        } elseif ($inProgressTasks > 0) {
            $newStatus = 'En proceso';
        } else {
            $newStatus = 'Pendiente';
        }

        if ($project->status !== $newStatus) {
            $project->update(['status' => $newStatus]);
        }
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'project_id' => 'required|exists:projects,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'assigned_to' => 'required|exists:users,id',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
            'is_high_priority' => 'boolean',
        ]);

        $task = Task::create($validated + ['status' => 'Pendiente']);

        $task->project->members()->syncWithoutDetaching([$validated['assigned_to']]);
        $this->updateProjectAutomatedStatus($task->project);

        return redirect()->back()->with('success', 'Tarea creada correctamente.');
    }

    public function update(Request $request, Task $task)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'assigned_to' => 'required|exists:users,id',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
            'is_high_priority' => 'boolean',
            'time_logs' => 'nullable|array',
            'time_logs.*.id' => 'required|exists:time_logs,id',
            'time_logs.*.duration_minutes' => 'required|integer|min:0',
        ]);

        $task->update([
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'assigned_to' => $validated['assigned_to'],
            'start_date' => $validated['start_date'] ?? null,
            'end_date' => $validated['end_date'] ?? null,
            'is_high_priority' => $validated['is_high_priority'] ?? false,
        ]);
        
        $task->project->members()->syncWithoutDetaching([$validated['assigned_to']]);

        if (isset($validated['time_logs'])) {
            foreach ($validated['time_logs'] as $logData) {
                $timeLog = TimeLog::find($logData['id']);
                if ($timeLog && $timeLog->duration_minutes !== $logData['duration_minutes']) {
                    $difference = $logData['duration_minutes'] - $timeLog->duration_minutes;
                    $timeLog->update(['duration_minutes' => $logData['duration_minutes']]);
                    
                    $task->increment('total_invested_minutes', $difference);
                    $task->project->increment('total_invested_minutes', $difference);
                }
            }
        }
        
        return redirect()->back()->with('success', 'Tarea y tiempos actualizados.');
    }

    public function destroy(Task $task)
    {
        $project = $task->project;
        $investedMinutes = $task->total_invested_minutes;

        $task->timeLogs()->delete();
        $task->delete();

        if ($investedMinutes > 0) {
            $project->decrement('total_invested_minutes', $investedMinutes);
        }

        $this->updateProjectAutomatedStatus($project);

        return redirect()->back()->with('success', 'Tarea eliminada exitosamente.');
    }

    public function updateStatus(Request $request, Task $task)
    {
        $validated = $request->validate([
            'status' => 'required|string|in:Pendiente,En proceso,Completada',
        ]);

        $oldStatus = $task->status;
        $newStatus = $validated['status'];

        if ($oldStatus === $newStatus) {
            return redirect()->back();
        }

        $task->load('project');

        DB::beginTransaction();
        try {
            if ($newStatus === 'En proceso' && $oldStatus !== 'En proceso') {
                if (!$task->start_date) {
                    $task->update(['start_date' => Carbon::today()]);
                }
                
                TimeLog::create([
                    'task_id' => $task->id,
                    'user_id' => Auth::id(),
                    'start_time' => Carbon::now(),
                ]);
            }

            if ($oldStatus === 'En proceso' && $newStatus !== 'En proceso') {
                $timeLog = TimeLog::where('task_id', $task->id)
                                  ->whereNull('end_time')
                                  ->latest('start_time')
                                  ->first();

                if ($timeLog) {
                    $startTime = Carbon::parse($timeLog->start_time);
                    $endTime = Carbon::now();
                    
                    $durationInSeconds = $endTime->diffInSeconds($startTime, true);
                    $durationInMinutes = max(0, (int) round($durationInSeconds / 60));

                    DB::table('time_logs')->where('id', $timeLog->id)->update([
                        'end_time' => $endTime,
                        'duration_minutes' => $durationInMinutes,
                        'updated_at' => Carbon::now(),
                    ]);
                    
                    if ($durationInMinutes > 0) {
                        $task->update([
                            'total_invested_minutes' => DB::raw("total_invested_minutes + $durationInMinutes")
                        ]);
                        $task->project->increment('total_invested_minutes', $durationInMinutes);
                        $task->refresh();
                    }
                }
            }

            if ($newStatus === 'Completada' && $oldStatus !== 'Completada') {
                if (!$task->end_date) {
                    $task->update(['end_date' => Carbon::today()]);
                }
            }

            $task->update(['status' => $newStatus]);
            $this->updateProjectAutomatedStatus($task->project);

            DB::commit();

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error updating task status: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Error al actualizar la tarea.');
        }

        return redirect()->back();
    }
}