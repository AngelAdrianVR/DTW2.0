<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PomodoroSetting;
use App\Models\Task; // Asegúrate que la ruta a tu modelo Task sea correcta
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PomodoroController extends Controller
{
    /**
     * Get the user's pomodoro settings.
     */
    public function getSettings(Request $request)
    {
        $settings = $request->user()->pomodoroSetting()->first();

        if (!$settings) {
            return response()->json([
                'work_minutes' => 25,
                'break_minutes' => 5,
            ]);
        }

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
        ]);

        $request->user()->pomodoroSetting()->updateOrCreate(
            ['user_id' => $request->user()->id],
            $validated
        );

        return response()->json(['message' => 'Configuración guardada correctamente.']);
    }

    /**
     * Update user's tasks status to 'Pendiente'.
     * Esta es la lógica que se ejecutará cuando termine un ciclo de trabajo.
     */
    public function pauseActiveTasks(Request $request)
    {
        // Esta es una implementación de ejemplo.
        // Asume que el status 'En proceso' se identifica con un ID o un string.
        // ¡Ajusta la consulta a tu estructura de datos!
        $request->user()->tasks()
                ->where('status', 'En proceso') // O por ejemplo ->where('status_id', 2)
                ->update(['status' => 'Pendiente']); // O por ejemplo 'status_id' => 3

        return response()->json(['message' => 'Tareas activas pausadas.']);
    }

     /**
     * Update user's tasks status to 'En proceso'.
     * Esta es la lógica que se ejecutará cuando el usuario decida continuar.
     */
    public function resumePausedTasks(Request $request)
    {
        $request->user()->tasks()
                ->where('status', 'Pendiente')
                ->limit(1) // O la lógica que uses para saber cuál tarea reanudar
                ->update(['status' => 'En proceso']);

        return response()->json(['message' => 'Tarea reanudada.']);
    }
}
