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
        // Nota: La deuda se sigue calculando solo sobre cotizaciones 'Aceptado' que aún no se han pagado.
        $totalOwedPerClient = DB::table('quotes')
            ->select('client_id', DB::raw('SUM(amount * (1 - COALESCE(percentage_discount, 0) / 100)) as total_owed'))
            ->where('status', 'Aceptado')
            ->groupBy('client_id');

        $totalPaidPerClient = DB::table('client_payments')
            ->select('client_id', DB::raw('SUM(amount) as total_paid'))
            ->groupBy('client_id');

        $clientsWithDebt = DB::table('clients')
            ->select('clients.name', DB::raw('COALESCE(owed.total_owed, 0) - COALESCE(paid.total_paid, 0) as debt'))
            ->leftJoinSub($totalOwedPerClient, 'owed', 'clients.id', '=', 'owed.client_id')
            ->leftJoinSub($totalPaidPerClient, 'paid', 'clients.id', '=', 'paid.client_id')
            ->having('debt', '>', 0.01)
            ->orderBy('debt', 'desc')
            ->limit(10)
            ->get();
            
        // --- KPI de Proyectos y Hostings ---
        $projectsCount = Project::count();
        $hostingsCount = HostingClient::count();
        $clientsCount = Client::count();

        // --- KPI de Desempeño ---
        // Se optimiza la consulta para no cargar 'timeLogs', que ya no son necesarios aquí.
        $users = User::with('assignedTasks')->get();
        $performanceData = $users->map(function ($user) {
            // Se calcula el total de minutos sumando directamente desde las tareas asignadas.
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
                    'pending' => $tasks->whereIn('status', ['Pendiente', 'Por hacer'])->count(),
                ],
            ];
        });

        // --- KPI Financieros y Gráfica de Ingresos ---
        // Se incluyen cotizaciones con estado 'Aceptado' y 'Pagado' para los cálculos de facturación.
        $acceptedAndPaidQuotesQuery = Quote::whereIn('quotes.status', ['Aceptado', 'Pagado']);
    
        // Total facturado (Gran Total)
        $totalInvoiced = (clone $acceptedAndPaidQuotesQuery)->get()->sum('final_amount');
    
        // Desglose de facturación por cliente.
        $invoicedPerClient = Quote::query()
            ->join('clients', 'quotes.client_id', '=', 'clients.id')
            ->whereIn('quotes.status', ['Aceptado', 'Pagado']) // Se incluyen ambos estados.
            ->select('clients.name', DB::raw('SUM(quotes.amount * (1 - COALESCE(quotes.percentage_discount, 0) / 100)) as total'))
            ->groupBy('clients.name')
            ->orderBy('total', 'desc')
            ->get();


        $currentYear = Carbon::now()->year;

        // Suma mensual de pagos de clientes
        $clientPaymentsByMonth = ClientPayment::query()
            ->select(DB::raw('MONTH(payment_date) as month'), DB::raw('SUM(amount) as total'))
            ->whereYear('payment_date', $currentYear)
            ->groupBy('month')
            ->pluck('total', 'month');

        // Suma mensual de pagos de hosting
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
                    'accepted' => ($quoteStatusCounts['Aceptado'] ?? 0) + ($quoteStatusCounts['Pagado'] ?? 0),
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
                ],
                'hostings' => [
                    'total' => $hostingsCount,
                ],
                'performance' => $performanceData,
                'financials' => [
                    'income_chart' => $incomeChartData,
                ],
            ],
        ]);
    }

    /**
     * Devuelve datos financieros para el gráfico como JSON para una solicitud AJAX.
     */
    public function getFinancialsByYear(Request $request)
    {
        $validated = $request->validate([
            'year' => 'required|integer|min:2020|max:' . (Carbon::now()->year + 1),
        ]);

        $financials = $this->getFinancialDataForYear($validated['year']);

        return response()->json($financials);
    }

    /**
     * Obtiene y formatea los datos financieros para un año específico.
     */
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

    /**
     * Obtiene los registros de tiempo de un usuario para una semana específica.
     */
    public function getWeeklyPerformance(Request $request, User $user)
    {
        $request->validate([
            'week' => 'required|string', // e.g., "2025-W41"
        ]);

        [$year, $weekNumber] = sscanf($request->input('week'), '%d-W%d');
        
        $startDate = Carbon::now()->setISODate($year, $weekNumber)->startOfWeek(Carbon::MONDAY);
        $endDate = $startDate->copy()->endOfWeek(Carbon::SUNDAY);

        $timeLogs = TimeLog::with('task')
            ->where('user_id', $user->id)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->orderBy('created_at', 'asc')
            ->get();
        
        // CORRECCIÓN: Usar 'duration_minutes'
        $totalWeekMinutes = $timeLogs->sum('duration_minutes');

        $logsByDay = $timeLogs->groupBy(function ($log) {
            return Carbon::parse($log->created_at)->format('l'); // 'Monday', 'Tuesday', etc.
        });

        $weekDays = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
        $formattedLogs = [];

        foreach ($weekDays as $day) {
            $dayLogs = $logsByDay->get($day, collect());
            // CORRECCIÓN: Usar 'duration_minutes'
            $totalDayMinutes = $dayLogs->sum('duration_minutes');

            $activities = 'Sin actividades registradas';
            if (!$dayLogs->isEmpty()) {
                // CORRECCIÓN: Agrupar por tarea y sumar los minutos
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

            $formattedLogs[$day] = [
                'activities' => $activities,
                'total_day_hours_formatted' => sprintf('%d hrs %d min', floor($totalDayMinutes / 60), $totalDayMinutes % 60),
            ];
        }

        return response()->json([
            'week_data' => $formattedLogs,
            'total_week_hours_formatted' => sprintf('%d hrs %d min', floor($totalWeekMinutes / 60), $totalWeekMinutes % 60),
            'week_label' => 'Semana ' . $weekNumber . ', ' . $year
        ]);
    }
}
