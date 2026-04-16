<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\ClientPayment;
use App\Models\HostingClient;
use App\Models\HostingPayment;
use App\Models\Project;
use App\Models\Quote;
use App\Models\TimeLog;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index()
    {
        // --- KPI de Cotizaciones ---
        $quoteStatusCounts = Quote::query()
            ->select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status')
            ->all();

        // --- KPI de Clientes ---
        $totalOwedPerClient = DB::table('quotes')
            ->select('client_id', DB::raw('SUM(amount * (1 - COALESCE(percentage_discount, 0) / 100)) as total_owed'))
            ->whereIn('status', ['Aceptado', 'Pagado']) // <--- Corrección: Se agrega 'Pagado' para equilibrar los pagos totales
            ->groupBy('client_id');

        $totalPaidPerClient = DB::table('client_payments')
            ->select('client_id', DB::raw('SUM(amount) as total_paid'))
            ->groupBy('client_id');

        $clientsWithDebt = DB::table('clients')
            ->select('clients.id', 'clients.name', DB::raw('COALESCE(owed.total_owed, 0) - COALESCE(paid.total_paid, 0) as debt'))
            ->leftJoinSub($totalOwedPerClient, 'owed', 'clients.id', '=', 'owed.client_id')
            ->leftJoinSub($totalPaidPerClient, 'paid', 'clients.id', '=', 'paid.client_id')
            ->having('debt', '>', 0.01)
            ->orderBy('debt', 'desc')
            ->get();
            
        // --- KPI de Proyectos y Hostings ---
        $projectsCount = Project::count();
        
        // Desglose de estados de proyectos para el KPI mejorado
        $projectsStats = Project::select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status')
            ->all();

        $hostingsCount = HostingClient::count();
        $clientsCount = Client::count();

        // --- Hostings: Próximos pagos del mes actual ---
        $currentMonth = Carbon::now()->month;
        $currentYear = Carbon::now()->year;

        $upcomingHostings = HostingClient::with('client:id,name')
            ->where('status', 'Activo')
            ->whereMonth('next_payment_date', $currentMonth)
            ->whereYear('next_payment_date', $currentYear)
            ->orderBy('next_payment_date', 'asc')
            ->get()
            ->map(function ($h) {
                return [
                    'id' => $h->id,
                    'client_name' => $h->client ? $h->client->name : 'Sin Cliente',
                    'date' => $h->next_payment_date ? Carbon::parse($h->next_payment_date)->format('Y-m-d') : null,
                    'amount' => $h->payment_amount,
                ];
            });

        // --- KPI de Desempeño ---
        $users = User::with('assignedTasks')->get();
        $performanceData = $users->map(function ($user) {
            $totalMinutes = $user->assignedTasks->sum('total_invested_minutes');

            $hours = floor($totalMinutes / 60);
            $minutes = $totalMinutes % 60;
            $tasks = $user->assignedTasks;

            return [
                'id' => $user->id,
                'name' => $user->name,
                'total_hours_formatted' => sprintf('%dhrs %dmin', $hours, $minutes),
                'last_login_at' => $user->last_login_at ? $user->last_login_at->format('j M, Y, g:i A') : 'N/A',
                'is_active' => $tasks->where('status', 'En proceso')->count() > 0,
                'stats' => [
                    'completed' => $tasks->where('status', 'Completada')->count(),
                    'in_progress' => $tasks->where('status', 'En proceso')->count(),
                    // Tareas actualmente en curso para mostrarlas en el hover
                    'in_progress_details' => $tasks->where('status', 'En proceso')->map(function($t) {
                        return [
                            'id' => $t->id,
                            'title' => $t->title,
                            'project_id' => $t->project_id
                        ];
                    })->values(),
                    'pending' => $tasks->whereIn('status', ['Pendiente', 'Por hacer'])->count(),
                    'pending_details' => $tasks->whereIn('status', ['Pendiente', 'Por hacer'])
                        ->map(function($t) {
                            return [
                                'id' => $t->id,
                                'title' => $t->title,
                                'project_id' => $t->project_id
                            ];
                        })->values(),
                ],
            ];
        });

        // --- KPI Financieros y Gráfica de Ingresos ---
        $acceptedAndPaidQuotesQuery = Quote::whereIn('quotes.status', ['Aceptado', 'Pagado']);
        $totalInvoiced = (clone $acceptedAndPaidQuotesQuery)->get()->sum('final_amount');
    
        $invoicedPerClient = Quote::query()
            ->join('clients', 'quotes.client_id', '=', 'clients.id')
            ->whereIn('quotes.status', ['Aceptado', 'Pagado'])
            ->select('clients.name', DB::raw('SUM(quotes.amount * (1 - COALESCE(quotes.percentage_discount, 0) / 100)) as total'))
            ->groupBy('clients.name')
            ->orderBy('total', 'desc')
            ->get();

        $clientPaymentsByMonth = ClientPayment::query()
            ->select(DB::raw('MONTH(payment_date) as month'), DB::raw('SUM(amount) as total'))
            ->whereYear('payment_date', $currentYear)
            ->groupBy('month')
            ->pluck('total', 'month');

        $hostingPaymentsByMonth = HostingPayment::query()
            ->select(DB::raw('MONTH(payment_date) as month'), DB::raw('SUM(amount) as total'))
            ->whereYear('payment_date', $currentYear)
            ->groupBy('month')
            ->pluck('total', 'month');

        $clientPaymentsData = [];
        $hostingPaymentsData = [];
        $monthLabels = [];

        for ($month = 1; $month <= 12; $month++) {
            $monthLabels[] = ucfirst(Carbon::create()->month($month)->locale('es')->shortMonthName);
            $clientPaymentsData[] = $clientPaymentsByMonth->get($month, 0);
            $hostingPaymentsData[] = $hostingPaymentsByMonth->get($month, 0);
        }

        $incomeChartData = [
            'labels' => $monthLabels,
            'datasets' => [
                [
                    'label' => 'Pagos de Proyectos',
                    'data' => $clientPaymentsData,
                    'backgroundColor' => 'rgba(59, 130, 246, 0.2)',
                    'borderColor' => 'rgb(59, 130, 246)',
                ],
                [
                    'label' => 'Pagos de Hosting',
                    'data' => $hostingPaymentsData,
                    'backgroundColor' => 'rgba(16, 185, 129, 0.2)',
                    'borderColor' => 'rgb(16, 185, 129)',
                ],
            ],
        ];

        return Inertia::render('Dashboard/Index', [
            'kpis' => [
                'quotes' => [
                    'total' => array_sum($quoteStatusCounts),
                    'paid' => $quoteStatusCounts['Pagado'] ?? 0,
                    'accepted' => $quoteStatusCounts['Aceptado'] ?? 0,
                    'rejected' => $quoteStatusCounts['Rechazado'] ?? 0,
                    'pending' => $quoteStatusCounts['Pendiente'] ?? 0,
                    'sent' => $quoteStatusCounts['Enviado'] ?? 0,
                ],
                'clients' => [
                    'total' => $clientsCount,
                    'with_debt' => $clientsWithDebt,
                    'total_invoiced' => $totalInvoiced,
                    'invoiced_per_client' => $invoicedPerClient,
                ],
                'projects' => [
                    'total' => $projectsCount,
                    'en_proceso' => $projectsStats['En proceso'] ?? 0,
                    'pendientes' => $projectsStats['Pendiente'] ?? 0,
                    'completados' => $projectsStats['Completado'] ?? 0,
                ],
                'hostings' => [
                    'total' => $hostingsCount,
                    'upcoming' => $upcomingHostings,
                    'current_month_name' => Carbon::now()->locale('es')->monthName,
                ],
                'performance' => $performanceData,
                'financials' => [
                    'income_chart' => $incomeChartData,
                ],
            ],
        ]);
    }

    public function getFinancialsByYear(Request $request)
    {
        $validated = $request->validate([
            'year' => 'required|integer|min:2020|max:' . (Carbon::now()->year + 1),
        ]);

        $financials = $this->getFinancialDataForYear($validated['year']);

        return response()->json($financials);
    }

    private function getFinancialDataForYear(int $year)
    {
        $clientPaymentsByMonth = ClientPayment::query()
            ->select(DB::raw('MONTH(payment_date) as month'), DB::raw('SUM(amount) as total'))
            ->whereYear('payment_date', $year)
            ->groupBy('month')
            ->pluck('total', 'month');

        $hostingPaymentsByMonth = HostingPayment::query()
            ->select(DB::raw('MONTH(payment_date) as month'), DB::raw('SUM(amount) as total'))
            ->whereYear('payment_date', $year)
            ->groupBy('month')
            ->pluck('total', 'month');

        $clientPaymentsData = [];
        $hostingPaymentsData = [];
        $monthLabels = [];

        for ($month = 1; $month <= 12; $month++) {
            $monthLabels[] = ucfirst(Carbon::create()->month($month)->locale('es')->shortMonthName);
            $clientPaymentsData[] = $clientPaymentsByMonth->get($month, 0);
            $hostingPaymentsData[] = $hostingPaymentsByMonth->get($month, 0);
        }

        return [
            'labels' => $monthLabels,
            'datasets' => [
                ['label' => 'Pagos de Proyectos', 'data' => $clientPaymentsData, 'backgroundColor' => 'rgba(59, 130, 246, 0.2)', 'borderColor' => 'rgb(59, 130, 246)'],
                ['label' => 'Pagos de Hosting', 'data' => $hostingPaymentsData, 'backgroundColor' => 'rgba(16, 185, 129, 0.2)', 'borderColor' => 'rgb(16, 185, 129)'],
            ],
        ];
    }

    public function getWeeklyPerformance(Request $request, User $user)
    {
        $request->validate([
            'week' => 'required|string',
        ]);

        [$year, $weekNumber] = sscanf($request->input('week'), '%d-W%d');
        
                $startDate = Carbon::now()->setISODate($year, $weekNumber)->startOfWeek(Carbon::MONDAY);
        $endDate = $startDate->copy()->endOfWeek(Carbon::SUNDAY);

        $timeLogs = TimeLog::with('task')
            ->where('user_id', $user->id)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->orderBy('created_at', 'asc')
            ->get();
        
        $totalWeekMinutes = $timeLogs->sum('duration_minutes');

        $logsByDay = $timeLogs->groupBy(function ($log) {
            return Carbon::parse($log->created_at)->format('Y-m-d'); 
        });

        $formattedLogs = [];
        $currentDate = $startDate->copy();

        for ($i = 0; $i < 7; $i++) {
            $dateKey = $currentDate->format('Y-m-d');
            $dayName = $currentDate->format('l,'); // Monday, Tuesday, etc.
            $dateFormatted = $currentDate->format('d'); // Ej: 23/02/26
            
            $dayLogs = $logsByDay->get($dateKey, collect());
            $totalDayMinutes = $dayLogs->sum('duration_minutes');

            $activities = 'Sin actividades registradas';
            if (!$dayLogs->isEmpty()) {
                $logsByTask = $dayLogs->groupBy('task_id');
                
                $activityStrings = $logsByTask->map(function ($taskLogs) {
                    $totalTaskMinutes = $taskLogs->sum('duration_minutes');
                    $taskTitle = $taskLogs->first()->task->title ?? 'Tarea eliminada';
                    $hours = floor($totalTaskMinutes / 60);
                    $minutes = $totalTaskMinutes % 60;
                    return sprintf('• %s - %d hrs %d min', $taskTitle, $hours, $minutes);
                });
                
                $activities = $activityStrings->implode("\n");
            }

            $formattedLogs[] = [
                'day_name' => $dayName,
                'date' => $dateFormatted,
                'activities' => $activities,
                'total_day_hours_formatted' => sprintf('%d hrs %d min', floor($totalDayMinutes / 60), $totalDayMinutes % 60),
            ];
            
            $currentDate->addDay();
        }

        // Formateamos las fechas para el paréntesis
        $startDateLabel = $startDate->locale('es')->translatedFormat('d M Y');
        $endDateLabel = $endDate->locale('es')->translatedFormat('d M Y');

        return response()->json([
            'week_data' => $formattedLogs,
            'total_week_hours_formatted' => sprintf('%d hrs %d min', floor($totalWeekMinutes / 60), $totalWeekMinutes % 60),
            'week_label' => "Semana $weekNumber, $year ($startDateLabel al $endDateLabel)"
        ]);
    }

    public function getQuotesByStatus(Request $request)
    {
        $validated = $request->validate([
            'status' => 'required|string',
        ]);

        $quotes = Quote::with('client')
            ->where('status', $validated['status'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($quotes);
    }

    public function getProjectsByStatus(Request $request)
    {
        $validated = $request->validate([
            'status' => 'required|string',
        ]);

        $projects = Project::with('client:id,name')
            ->where('status', $validated['status'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($projects);
    }
}