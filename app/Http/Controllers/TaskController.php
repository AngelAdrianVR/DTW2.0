<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Models\TimeLog;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class TaskController extends Controller
{
    public function index()
    {
        //
    }

    public function create()
    {
        //
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'project_id' => 'required|exists:projects,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string', // Validar la descripción
            'assigned_to' => 'nullable|exists:users,id',
            'due_date' => 'nullable|date',
        ]);

        $task = Task::create($validated + ['status' => 'Pendiente']);

        // Devolvemos la tarea creada como JSON para poder agregarla dinámicamente
        // return response()->json($task->load('assignee'));
    }

    public function show(Task $task)
    {
        //
    }

    public function edit(Task $task)
    {
        //
    }

    public function update(Request $request, Task $task)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'assigned_to' => 'nullable|exists:users,id',
            'due_date' => 'nullable|date',
        ]);

        $task->update($validated);
        
        // Devolvemos la tarea actualizada para refrescar la UI
        // return response()->json($task->load('assignee'));
    }

    public function destroy(Task $task)
    {
        $task->delete();
        return response()->json(['success' => true, 'message' => 'Tarea eliminada.']);
    }

    public function updateStatus(Request $request, Task $task)
    {
        $validated = $request->validate([
            'status' => 'required|string|in:Pendiente,En proceso,Completada',
        ]);

        $oldStatus = $task->status;
        $newStatus = $validated['status'];

        if ($oldStatus === $newStatus) {
            // return response()->json(['message' => 'El estado no ha cambiado.'], 200);
        }

        try {
            DB::beginTransaction();

            if ($newStatus === 'En proceso' && $oldStatus !== 'En proceso') {
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
                    
                    // CORRECCIÓN: Calcular duración en segundos para evitar errores de rango y decimales.
                    $durationInSeconds = $endTime->diffInSeconds($startTime);
                    $durationInMinutes = (int) round($durationInSeconds / 60);

                    $timeLog->update([
                        'end_time' => $endTime,
                        'duration_minutes' => $durationInMinutes,
                    ]);
                    
                    // ACUMULAR TIEMPO: Sumamos la duración a la tarea y al proyecto.
                    if ($durationInMinutes > 0) {
                        $task->increment('total_invested_minutes', $durationInMinutes);
                        $task->project->increment('total_invested_minutes', $durationInMinutes);
                    }
                }
            }

            $task->update(['status' => $newStatus]);

            DB::commit();

            // return response()->json([
            //     'success' => true,
            //     'message' => 'Estado de la tarea actualizado.',
            // ]);

        } catch (\Exception $e) {
            DB::rollBack();
            // return response()->json(['success' => false, 'message' => 'Error al actualizar la tarea: ' . $e->getMessage()], 500);
        }
    }
}
