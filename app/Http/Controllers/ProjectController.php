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
                $query->where('name', 'like', "%{$search}%")
                    ->orWhereHas('client', function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%");
                    })
                    ->orWhere('status', 'like', "%{$search}%");
            })
            ->latest() // Order by creation date, newest first
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Project/Index', [
            'projects' => $projects,
            'filters' => $request->only('search')
        ]);
    }

    public function create(Request $request)
    {
        // Fetch clients and users to populate the dropdowns in the form
        $clients = Client::select('id', 'name')->get();
        $users = User::select('id', 'name')->get();

        // Fetch accepted quotes that don't have a project yet.
        // This assumes a 'project' hasOne relationship exists on the Quote model.
        $quotes = Quote::where('status', 'Aceptado')
            ->whereDoesntHave('project')
            // Se añaden los campos necesarios para el autocompletado
            ->select('id', 'title', 'client_id', 'amount', 'percentage_discount', 'description')
            ->get();

        return Inertia::render('Project/Create', [
            'clients' => $clients,
            'users' => $users,
            'quotes' => $quotes,
            // --- CAMBIO CLAVE ---
            // Se pasa el ID de la cotización desde la URL a la vista
            'quoteIdFromUrl' => (int) $request->input('quote_id'),
        ]);
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'quote_id' => 'nullable|exists:quotes,id|unique:projects,quote_id',
            'client_id' => 'required_without:quote_id|nullable|exists:clients,id',
            'description' => 'nullable|string',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'budget' => 'nullable|numeric|min:0',
            'member_ids' => 'nullable|array',
            'member_ids.*' => 'exists:users,id', // Validate each ID in the array
        ]);

        // Create the project with the main data
        $project = Project::create($validatedData);

        // agrega el id del proyecto a la cotización si se seleccionó una
        if (!empty($validatedData['quote_id'])) {
            $quote = Quote::find($validatedData['quote_id']);
            if ($quote) {
                $quote->project_id = $project->id;
                $quote->save();
            }
        }

        // If members were selected, attach them to the project
        if (!empty($validatedData['member_ids'])) {
            $project->members()->attach($validatedData['member_ids']);
        }

        return redirect()->route('projects.index')->with('success', 'Proyecto creado correctamente.');
    }

    public function show(Project $project)
    {
        // Cargar relaciones necesarias para evitar N+1
        $project->load([
            'client:id,name', 
            'members:id,name,profile_photo_path', 
            'tasks.assignee:id,name,profile_photo_path'
        ]);

        // Calcular el tiempo total invertido sumando los logs de tiempo de las tareas
        $totalMinutes = TimeLog::whereIn('task_id', $project->tasks->pluck('id'))->sum('duration_minutes');
        
        // Formatear el tiempo a "Xh Ym"
        $hours = floor($totalMinutes / 60);
        $minutes = $totalMinutes % 60;
        $totalTimeInvested = "{$hours}h {$minutes}m invertido";

        return Inertia::render('Project/Show', [
            'project' => $project,
            'totalTimeInvested' => $totalTimeInvested,
        ]);
    }

    public function edit(Project $project)
    {
        // Carga los miembros actuales del proyecto para pasarlos a la vista
        $project->load('members:id');

        // Obtener listas de recursos para los dropdowns
        $clients = Client::select('id', 'name')->get();
        $users = User::select('id', 'name')->get();

        // Obtener cotizaciones que están aceptadas y O no tienen proyecto O pertenecen a este proyecto.
        // Esto permite que la cotización actual siga apareciendo en el dropdown.
        $quotes = Quote::where('status', 'Aceptado')
            ->where(function ($query) use ($project) {
                $query->whereDoesntHave('project')
                      ->orWhere('project_id', $project->id);
            })
            ->select('id', 'title', 'client_id', 'amount', 'percentage_discount', 'description')
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
            // La cotización debe ser única, ignorando el proyecto actual
            'quote_id' => [
                'nullable',
                'exists:quotes,id',
                Rule::unique('projects')->ignore($project->id),
            ],
            'client_id' => 'required_without:quote_id|nullable|exists:clients,id',
            'description' => 'nullable|string',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'budget' => 'nullable|numeric|min:0',
            'member_ids' => 'nullable|array',
            'member_ids.*' => 'exists:users,id',
        ]);

        // Manejar el cambio de cotización
        $oldQuoteId = $project->quote_id;
        $newQuoteId = $validatedData['quote_id'] ?? null;

        // Si la cotización ha cambiado...
        if ($oldQuoteId != $newQuoteId) {
            // Desvincular la cotización anterior si existía
            if ($oldQuoteId) {
                Quote::where('id', $oldQuoteId)->update(['project_id' => null]);
            }
            // Vincular la nueva cotización si se proporcionó una
            if ($newQuoteId) {
                Quote::where('id', $newQuoteId)->update(['project_id' => $project->id]);
            }
        }

        // Actualizar el proyecto con los datos validados
        $project->update($validatedData);

        // Sincronizar los miembros del equipo.
        // sync() se encarga de añadir/eliminar miembros según sea necesario.
        if (isset($validatedData['member_ids'])) {
            $project->members()->sync($validatedData['member_ids']);
        } else {
            // Si no se envían IDs de miembros, se eliminan todas las asociaciones
            $project->members()->detach();
        }


        return redirect()->route('projects.index')->with('success', 'Proyecto actualizado correctamente.');
    }

    public function destroy(Project $project)
    {
        $project->delete();

        return redirect()->route('projects.index')->with('success', 'Proyecto eliminado correctamente.');
    }
}
