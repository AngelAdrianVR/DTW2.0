<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Models\TimeLog;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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
            return redirect()->back();
        }

        $task->load('project');

        DB::beginTransaction();
        try {
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
                    
                    // --- CORRECCIÓN 1: CÁLCULO DE DURACIÓN ROBUSTO ---
                    // Se agrega `true` para forzar a que la diferencia sea siempre un valor absoluto (positivo).
                    // Esto resuelve el problema de los segundos negativos causado por desfases de reloj.
                    $durationInSeconds = $endTime->diffInSeconds($startTime, true);
                    $durationInMinutes = max(0, (int) round($durationInSeconds / 60));

                    DB::table('time_logs')->where('id', $timeLog->id)->update([
                        'end_time' => $endTime,
                        'duration_minutes' => $durationInMinutes,
                        'updated_at' => Carbon::now(),
                    ]);
                    
                    if ($durationInMinutes > 0) {
                        // --- LÓGICA DE ACUMULACIÓN DE TIEMPO ---
                        // El método ->increment() es la clave. Automáticamente lee el valor actual
                        // de 'total_invested_minutes' en la base de datos, le suma $durationInMinutes
                        // y guarda el resultado. Esto asegura que el tiempo se acumule correctamente.
                        $task->update([
                            'total_invested_minutes' => DB::raw("total_invested_minutes + $durationInMinutes")
                        ]);
                        $task->project->increment('total_invested_minutes', $durationInMinutes);

                        // Actualizamos el modelo en memoria para que el accessor use el nuevo total.
                        $task->refresh();
                    }
                }
            }

            $task->update(['status' => $newStatus]);

            DB::commit();

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error updating task status: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Error al actualizar la tarea.');
        }

        return redirect()->back();
    }
}
