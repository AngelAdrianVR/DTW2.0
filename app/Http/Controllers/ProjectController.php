<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Project;
use App\Models\Quote;
use App\Models\TimeLog;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Validation\Rule;

class ProjectController extends Controller
{
    public function index(Request $request)
    {
        $projects = Project::query()
            // Eager load relationships to avoid N+1 query issues
            ->with(['client', 'members:id,name,profile_photo_path'])
            // Eager load task counts for the progress bar calculation
            ->withCount(['tasks', 'tasks as completed_tasks_count' => function ($query) {
                $query->where('status', 'Completada');
            }])
            ->when($request->input('search'), function ($query, $search) {
                $query->where(function($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhereHas('client', function ($q2) use ($search) {
                          $q2->where('name', 'like', "%{$search}%");
                      });
                });
            })
            ->when($request->input('status'), function ($query, $status) {
                $query->where('status', $status);
            })
            ->when($request->input('client_id'), function ($query, $clientId) {
                if ($clientId === 'interno') {
                    $query->whereNull('client_id');
                } else {
                    $query->where('client_id', $clientId);
                }
            })
            ->latest() // Order by creation date, newest first
            ->paginate(15)
            ->withQueryString();

        $clients = Client::select('id', 'name')->get();

        return Inertia::render('Project/Index', [
            'projects' => $projects,
            'clients' => $clients,
            'filters' => $request->only(['search', 'status', 'client_id'])
        ]);
    }

    public function create(Request $request)
    {
        // Obtener listas de recursos para los dropdowns del formulario
        $clients = Client::select('id', 'name')->get();
        $users = User::select('id', 'name')->get();

        // Obtener cotizaciones aceptadas o pagadas que AÚN NO tienen un proyecto asignado
        $quotes = Quote::whereIn('status', ['Aceptado', 'Pagado'])
            ->whereDoesntHave('project')
            ->latest('id') // <-- ORDENAR DE MÁS RECIENTE A MÁS ANTIGUO
            ->get();

        return Inertia::render('Project/Create', [
            'clients' => $clients,
            'users' => $users,
            'quotes' => $quotes,
            'quoteIdFromUrl' => $request->query('quote_id') ? (int) $request->query('quote_id') : null,
        ]);
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'client_id' => 'nullable|exists:clients,id',
            'quote_id' => 'nullable|exists:quotes,id',
            'description' => 'nullable|string',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'budget' => 'nullable|numeric|min:0',
            'budgeted_hours' => 'nullable|integer|min:0',
            'member_ids' => 'nullable|array',
            'member_ids.*' => 'exists:users,id',
        ]);

        // Asignar un estado por defecto al crear un nuevo proyecto
        $validatedData['status'] = 'Pendiente';

        $project = Project::create($validatedData);

        // Vincular los miembros seleccionados al proyecto
        if (!empty($validatedData['member_ids'])) {
            $project->members()->sync($validatedData['member_ids']);
        }

        // Si el proyecto se creó vinculado a una cotización, actualizamos dicha cotización
        if ($project->quote_id) {
            Quote::where('id', $project->quote_id)->update(['project_id' => $project->id]);
        }

        return redirect()->route('projects.index')->with('success', 'Proyecto creado correctamente.');
    }

    public function show(Project $project)
    {
        $project->load([
            'client:id,name',
            'members:id,name,profile_photo_path',
            'tasks' => function ($query) {
                // Hacemos eager loading también de timeLogs para que estén disponibles al editar la tarea
                $query->latest()->with(['assignee:id,name,profile_photo_path', 'timeLogs']);
            },
        ]);

        // Calcular el tiempo total invertido sumando el campo 'total_invested_minutes' de las tareas ya cargadas.
        $totalMinutes = $project->tasks->sum('total_invested_minutes');
        
        // Formatear el tiempo a "Xh Ym"
        $hours = floor($totalMinutes / 60);
        $minutes = $totalMinutes % 60;
        $totalTimeInvested = "{$hours}h {$minutes}m invertido";

        $users = User::select('id', 'name', 'profile_photo_path')->get();

        return Inertia::render('Project/Show', [
            'project' => $project,
            'totalTimeInvested' => $totalTimeInvested,
            'users' => $users,
        ]);
    }

    public function edit(Project $project)
    {
        // Carga los miembros actuales del proyecto para pasarlos a la vista
        $project->load('members:id');

        // Obtener listas de recursos para los dropdowns
        $clients = Client::select('id', 'name')->get();
        $users = User::select('id', 'name')->get();

        // Obtener cotizaciones que están aceptadas o pagadas y O no tienen proyecto O pertenecen a este proyecto.
        $quotes = Quote::whereIn('status', ['Aceptado', 'Pagado'])
            ->where(function ($query) use ($project) {
                $query->whereDoesntHave('project')
                      ->orWhere('id', $project->quote_id) // Seguro extra: siempre incluye el vinculado actual
                      ->orWhere('project_id', $project->id);
            })
            // Seleccionar budgeted_hours para que Vue lo auto-rellene
            ->select('id', 'title', 'client_id', 'amount', 'percentage_discount', 'description', 'budgeted_hours')
            ->latest('id') // <-- ORDENAR DE MÁS RECIENTE A MÁS ANTIGUO
            ->get();

        return Inertia::render('Project/Edit', [
            'project' => $project,
            'clients' => $clients,
            'users' => $users,
            'quotes' => $quotes,
        ]);
    }

    public function update(Request $request, Project $project)
    {
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'client_id' => 'nullable|exists:clients,id',
            'quote_id' => 'nullable|exists:quotes,id',
            'description' => 'nullable|string',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'budget' => 'nullable|numeric|min:0',
            'budgeted_hours' => 'nullable|integer|min:0',
            'member_ids' => 'nullable|array',
            'member_ids.*' => 'exists:users,id',
        ]);

        // Manejar el cambio de cotización
        $oldQuoteId = $project->quote_id;
        $newQuoteId = $request->input('quote_id'); // Asegura capturar el valor (incluso null)

        // Limpiar relaciones cruzadas en cotizaciones si cambió
        if ($oldQuoteId !== $newQuoteId) {
            if ($oldQuoteId) {
                Quote::where('id', $oldQuoteId)->update(['project_id' => null]);
            }
            if ($newQuoteId) {
                Quote::where('id', $newQuoteId)->update(['project_id' => $project->id]);
            }
        }
        
        // Forzamos explícitamente el quote_id actualizado por si fue removido como nulo
        $validatedData['quote_id'] = $newQuoteId;

        $project->update($validatedData);

        if (isset($validatedData['member_ids'])) {
            $project->members()->sync($validatedData['member_ids']);
        } else {
            $project->members()->detach();
        }

        return redirect()->route('projects.index')->with('success', 'Proyecto actualizado correctamente.');
    }

    public function destroy(Project $project)
    {
        $project->delete();

        return redirect()->route('projects.index')->with('success', 'Proyecto eliminado correctamente.');
    }

    // Nuevo método para actualizar estados rápidos desde el Index
    public function updateStatus(Request $request, Project $project)
    {
        $validatedData = $request->validate([
            'status' => ['required', 'string', Rule::in(['Pendiente', 'En proceso', 'Completado', 'Pausado', 'Cancelado'])],
        ]);

        $project->update(['status' => $validatedData['status']]);

        return redirect()->back()->with('success', 'Estado del proyecto actualizado.');
    }
}