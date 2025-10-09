<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\PomodoroSetting;
use App\Models\Task; // Asegúrate que la ruta a tu modelo Task sea correcta
use App\Models\TimeLog;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PomodoroController extends Controller
{
    /**
     * Get the user's pomodoro settings.
     */
    public function getSettings(Request $request)
    {
        // Usamos firstOrNew para obtener la configuración o un nuevo modelo si no existe.
        // Luego, usamos fill para establecer valores predeterminados si faltan.
        $settings = $request->user()->pomodoroSetting()->firstOrNew([]);
        $settings->fill([
            'work_minutes' => $settings->work_minutes ?? 25,
            'break_minutes' => $settings->break_minutes ?? 5,
            'long_break_minutes' => $settings->long_break_minutes ?? 15,
            'sessions_before_long_break' => $settings->sessions_before_long_break ?? 4,
        ]);

        return response()->json($settings);
    }

    /**
     * Save the user's pomodoro settings.
     */
    public function saveSettings(Request $request)
    {
        $validated = $request->validate([
            'work_minutes' => 'required|integer|min:1',
            'break_minutes' => 'required|integer|min:1',
            'long_break_minutes' => 'required|integer|min:1',
            'sessions_before_long_break' => 'required|integer|min:1',
        ]);

        $request->user()->pomodoroSetting()->updateOrCreate(
            ['user_id' => $request->user()->id],
            $validated
        );

        return response()->json(['message' => 'Configuración guardada correctamente.']);
    }

    
    /**
     * NUEVO: Registra una sesión de Pomodoro completada.
     */
    public function logSession(Request $request)
    {
        $validated = $request->validate([
            'type' => 'required|string|in:work,break',
        ]);

        $request->user()->pomodoroSessions()->create([
            'type' => $validated['type'],
            'completed_at' => Carbon::now(),
        ]);

        return response()->json(['message' => 'Session logged successfully.']);
    }

    /**
     * Update user's tasks status to 'Pendiente'.
     * Esta es la lógica que se ejecutará cuando termine un ciclo de trabajo.
     */
    public function pauseActiveTasks(Request $request)
    {
        $activeTasks = $request->user()->assignedTasks()->where('status', 'En proceso')->get();

        if ($activeTasks->isEmpty()) {
            return response()->json(['message' => 'No hay tareas activas para pausar.'], 200);
        }

        DB::beginTransaction();
        try {
            foreach ($activeTasks as $task) {
                // Lógica para encontrar el TimeLog abierto y registrar el tiempo.
                $timeLog = TimeLog::where('task_id', $task->id)
                                  ->whereNull('end_time')
                                  ->latest('start_time')
                                  ->first();

                if ($timeLog) {
                    $startTime = Carbon::parse($timeLog->start_time);
                    $endTime = Carbon::now();
                    $durationInSeconds = $endTime->diffInSeconds($startTime, true);
                    // Solo registrar si ha pasado al menos un minuto.
                    $durationInMinutes = (int) floor($durationInSeconds / 60);

                    if ($durationInMinutes > 0) {
                        $timeLog->update([
                            'end_time' => $endTime,
                            'duration_minutes' => $durationInMinutes,
                        ]);

                        // Acumular tiempo en la tarea y el proyecto.
                        $task->increment('total_invested_minutes', $durationInMinutes);
                        if ($task->project) {
                            $task->project->increment('total_invested_minutes', $durationInMinutes);
                        }
                    } else {
                        // Si el tiempo es menor a un minuto, no lo contamos y simplemente lo borramos.
                        $timeLog->delete();
                    }
                }
                
                // Finalmente, actualizamos el estado de la tarea.
                $task->update(['status' => 'Pendiente']);
            }
            DB::commit();
            return response()->json(['message' => 'Tareas activas pausadas y tiempo registrado.']);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error en pauseActiveTasks de Pomodoro: ' . $e->getMessage());
            return response()->json(['message' => 'Error al procesar las tareas.'], 500);
        }
    }

     /**
     * Update user's tasks status to 'En proceso'.
     * Esta es la lógica que se ejecutará cuando el usuario decida continuar.
     */
    public function resumePausedTasks(Request $request)
    {
        // Se asume que solo se reanuda la tarea pendiente más reciente
        $taskToResume = $request->user()->assignedTasks()
                ->where('status', 'Pendiente')
                ->latest('updated_at')
                ->first();
        
        if($taskToResume){
            TimeLog::create([
                'task_id' => $taskToResume->id,
                'user_id' => Auth::id(),
                'start_time' => Carbon::now(),
            ]);
            $taskToResume->update(['status' => 'En proceso']);
            return response()->json(['message' => 'Tarea reanudada.']);
        }

        return response()->json(['message' => 'No hay tareas pendientes para reanudar.']);
    }
}
