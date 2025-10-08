<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $users = User::query()
            ->when($request->input('search'), function ($query, $search) {
                $query->where('name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%");
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('User/Index', [
            'users' => $users,
            'filters' => $request->only('search')
        ]);
    }

    /**
     * Muestra el formulario para crear un nuevo usuario.
     */
    public function create()
    {
        return Inertia::render('User/Create');
    }

    /**
     * Almacena un nuevo usuario en la base de datos.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:'.User::class,
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        // La redirección asume que tienes una ruta llamada 'users.index'.
        // Si no la tienes, puedes cambiarla a 'dashboard' o a donde prefieras.
        return redirect()->route('dashboard')->with('success', 'Usuario creado correctamente.');
    }

    /**
     * Muestra el formulario para editar un usuario existente.
     */
    public function edit(User $user)
    {
        return Inertia::render('User/Edit', [
            'user' => $user
        ]);
    }

    /**
     * Actualiza un usuario existente en la base de datos.
     */
    public function update(Request $request, User $user)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique(User::class)->ignore($user->id)
            ],
        ]);

        $user->update($request->only('name', 'email'));

        return redirect()->route('users.index')->with('success', 'Usuario actualizado correctamente.');
    }

    public function show(User $user)
    {
        // Cargar relaciones necesarias para evitar N+1 queries
        $user->load([
            'assignedTasks' => function ($query) {
                $query->with('project:id,name')->latest();
            },
            'projects' => function ($query) {
                $query->with('client:id,name')->latest();
            },
            'quotes' => function ($query) {
                $query->with('client:id,name')->latest();
            },
            'timeLogs' // Necesario para calcular el tiempo total invertido
        ]);

        // Calcular estadísticas
        $completedTasksCount = $user->assignedTasks->where('status', 'Completada')->count();
        $totalMinutes = $user->timeLogs->sum('duration_minutes');
        
        // Formatear el tiempo total a HH:MM
        $hours = floor($totalMinutes / 60);
        $minutes = $totalMinutes % 60;
        $totalTimeInvested = sprintf('%02d:%02d', $hours, $minutes);

        return Inertia::render('User/Show', [
            'user' => $user,
            'stats' => [
                'completedTasksCount' => $completedTasksCount,
                'totalTimeInvested' => $totalTimeInvested,
                'quotesCount' => $user->quotes->count(),
                'projectsCount' => $user->projects->count(),
            ]
        ]);
    }
}
