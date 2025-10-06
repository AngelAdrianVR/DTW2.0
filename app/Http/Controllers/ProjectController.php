<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Project;
use App\Models\Quote;
use App\Models\TimeLog;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

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

    public function create()
    {
        // Fetch clients and users to populate the dropdowns in the form
        $clients = Client::select('id', 'name')->get();
        $users = User::select('id', 'name')->get();

        // Fetch accepted quotes that don't have a project yet.
        // This assumes a 'project' hasOne relationship exists on the Quote model.
        $quotes = Quote::where('status', 'Aceptado')
            ->whereDoesntHave('project')
            ->select('id', 'title', 'client_id', 'amount')
            ->get();


        return Inertia::render('Project/Create', [
            'clients' => $clients,
            'users' => $users,
            'quotes' => $quotes, // Pass quotes to the view
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
        //
    }

    public function update(Request $request, Project $project)
    {
        //
    }

    public function destroy(Project $project)
    {
        $project->delete();

        return redirect()->route('projects.index')->with('success', 'Proyecto eliminado correctamente.');
    }
}
