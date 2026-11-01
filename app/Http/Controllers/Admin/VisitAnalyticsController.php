<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PageVisit;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class VisitAnalyticsController extends Controller
{
    private const PAGE_LABELS = [
        'landing.index' => 'Inicio',
        'landing.solutions' => 'Soluciones',
        'landing-projects.show' => 'Detalle de proyecto',
        'terms.show' => 'Términos de servicio',
        'privacy.show' => 'Aviso de privacidad',
    ];

    public function index(Request $request)
    {
        $mode = $request->query('mode') === 'month' ? 'month' : 'week';
        $week = $this->normalizeWeek($request->query('week'));
        $month = $this->normalizeMonth($request->query('month'));

        [$start, $end] = $this->resolvePeriod($mode, $week, $month);

        return Inertia::render('Admin/Visits/Index', [
            'initial' => $this->metrics($start, $end, $mode),
            'filters' => [
                'mode' => $mode,
                'week' => $week,
                'month' => $month,
                'current_week' => now()->format('o-\WW'),
                'current_month' => now()->format('Y-m'),
            ],
        ]);
    }

    public function data(Request $request)
    {
        $validated = $request->validate([
            'mode' => 'required|in:week,month',
            'week' => 'nullable|string|max:10',
            'month' => 'nullable|string|max:7',
        ]);

        $week = $this->normalizeWeek($validated['week'] ?? null);
        $month = $this->normalizeMonth($validated['month'] ?? null);

        [$start, $end] = $this->resolvePeriod($validated['mode'], $week, $month);

        return response()->json($this->metrics($start, $end, $validated['mode']));
    }

    /**
     * Calcula todas las métricas de un periodo (semana o mes).
     *
     * @return array<string, mixed>
     */
    private function metrics(Carbon $start, Carbon $end, string $mode): array
    {
        $base = PageVisit::query()
            ->humans()
            ->whereBetween('visited_at', [$start, $end]);

        $totalViews = (clone $base)->count();
        $totalVisitors = (clone $base)->distinct()->count('visitor_id');
        $totalPages = (clone $base)->distinct()->count('path');

        // Estadísticas agregadas por sesión de navegación.
        $sessionStats = DB::query()->fromSub(
            (clone $base)
                ->selectRaw('COALESCE(session_id, visitor_id) as session_key')
                ->selectRaw('COUNT(*) as views')
                ->selectRaw('MIN(visited_at) as first_view')
                ->selectRaw('MAX(visited_at) as last_view')
                ->groupBy('session_key'),
            'sessions'
        )
            ->selectRaw('COUNT(*) as sessions')
            ->selectRaw('COALESCE(SUM(CASE WHEN views = 1 THEN 1 ELSE 0 END), 0) as bounces')
            ->selectRaw('COALESCE(AVG(views), 0) as avg_views')
            ->selectRaw('COALESCE(AVG(TIMESTAMPDIFF(SECOND, first_view, last_view)), 0) as avg_seconds')
            ->first();

        $sessions = (int) ($sessionStats->sessions ?? 0);
        $bounces = (int) ($sessionStats->bounces ?? 0);
        $bounceRate = $sessions > 0 ? round(($bounces / $sessions) * 100, 1) : 0;

        // Serie diaria (visitas y visitantes únicos).
        $daily = (clone $base)
            ->selectRaw('DATE(visited_at) as day')
            ->selectRaw('COUNT(*) as views')
            ->selectRaw('COUNT(DISTINCT visitor_id) as visitors')
            ->groupBy('day')
            ->get()
            ->keyBy('day');

        $labels = [];
        $viewsSeries = [];
        $visitorsSeries = [];
        $bestDay = ['label' => '—', 'views' => 0];

        $cursor = $start->copy();
        while ($cursor->lte($end)) {
            $key = $cursor->format('Y-m-d');
            $row = $daily->get($key);

            $views = (int) ($row->views ?? 0);
            $visitors = (int) ($row->visitors ?? 0);

            $labels[] = $mode === 'week'
                ? ucfirst($cursor->locale('es')->isoFormat('ddd D'))
                : $cursor->format('j');

            $viewsSeries[] = $views;
            $visitorsSeries[] = $visitors;

            if ($views > $bestDay['views']) {
                $bestDay = [
                    'label' => ucfirst($cursor->locale('es')->isoFormat('ddd D MMM')),
                    'views' => $views,
                ];
            }

            $cursor->addDay();
        }

        // Distribución por hora del día.
        $hourlyRaw = (clone $base)
            ->selectRaw('HOUR(visited_at) as hour')
            ->selectRaw('COUNT(*) as views')
            ->groupBy('hour')
            ->pluck('views', 'hour');

        $hourlyLabels = [];
        $hourlySeries = [];
        $peakHour = ['label' => '—', 'views' => 0];

        for ($hour = 0; $hour < 24; $hour++) {
            $views = (int) ($hourlyRaw->get($hour) ?? 0);
            $hourlyLabels[] = str_pad((string) $hour, 2, '0', STR_PAD_LEFT).'h';
            $hourlySeries[] = $views;

            if ($views > $peakHour['views']) {
                $peakHour = [
                    'label' => sprintf('%02d:00 – %02d:59', $hour, $hour),
                    'views' => $views,
                ];
            }
        }

        // Distribución por día de la semana.
        $weekdayRaw = (clone $base)
            ->selectRaw('WEEKDAY(visited_at) as weekday')
            ->selectRaw('COUNT(*) as views')
            ->groupBy('weekday')
            ->pluck('views', 'weekday');

        $weekdayLabels = ['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo'];
        $weekdaySeries = [];

        for ($day = 0; $day < 7; $day++) {
            $weekdaySeries[] = (int) ($weekdayRaw->get($day) ?? 0);
        }

        // Páginas más visitadas.
        $topPages = (clone $base)
            ->select('path')
            ->selectRaw('MAX(route_name) as route_name')
            ->selectRaw('COUNT(*) as views')
            ->selectRaw('COUNT(DISTINCT visitor_id) as visitors')
            ->groupBy('path')
            ->orderByDesc('views')
            ->limit(6)
            ->get()
            ->map(fn ($row) => [
                'path' => $row->path,
                'label' => $this->pageLabel($row->route_name, $row->path),
                'views' => (int) $row->views,
                'visitors' => (int) $row->visitors,
                'percentage' => $totalViews > 0 ? round(($row->views / $totalViews) * 100, 1) : 0,
            ])
            ->values();

        // Fuentes de tráfico.
        $ownHost = parse_url((string) config('app.url'), PHP_URL_HOST);
        $referrers = [];

        $directViews = (clone $base)
            ->where(fn ($query) => $query->whereNull('referrer_host')->orWhere('referrer_host', ''))
            ->count();

        $internalViews = $ownHost
            ? (clone $base)->where('referrer_host', $ownHost)->count()
            : 0;

        $externalReferrers = (clone $base)
            ->whereNotNull('referrer_host')
            ->where('referrer_host', '!=', '')
            ->when($ownHost, fn ($query) => $query->where('referrer_host', '!=', $ownHost))
            ->select('referrer_host')
            ->selectRaw('COUNT(*) as views')
            ->groupBy('referrer_host')
            ->orderByDesc('views')
            ->limit(6)
            ->get();

        if ($directViews > 0) {
            $referrers[] = ['label' => 'Directo', 'views' => $directViews];
        }

        foreach ($externalReferrers as $referrer) {
            $referrers[] = [
                'label' => $referrer->referrer_host,
                'views' => (int) $referrer->views,
            ];
        }

        if ($internalViews > 0) {
            $referrers[] = ['label' => 'Navegación interna', 'views' => $internalViews];
        }

        $referrers = collect($referrers)
            ->map(fn ($item) => [
                ...$item,
                'percentage' => $totalViews > 0 ? round(($item['views'] / $totalViews) * 100, 1) : 0,
            ])
            ->sortByDesc('views')
            ->values();

        // Dispositivos y navegadores.
        $deviceLabels = ['desktop' => 'Escritorio', 'mobile' => 'Móvil', 'tablet' => 'Tablet'];
        $devices = (clone $base)
            ->select('device')
            ->selectRaw('COUNT(*) as views')
            ->groupBy('device')
            ->orderByDesc('views')
            ->get()
            ->map(fn ($row) => [
                'label' => $deviceLabels[$row->device] ?? ucfirst($row->device),
                'value' => (int) $row->views,
            ])
            ->values();

        $browsers = (clone $base)
            ->whereNotNull('browser')
            ->select('browser')
            ->selectRaw('COUNT(*) as views')
            ->groupBy('browser')
            ->orderByDesc('views')
            ->limit(5)
            ->get()
            ->map(fn ($row) => [
                'label' => $row->browser,
                'views' => (int) $row->views,
            ])
            ->values();

        // Visitantes nuevos vs. recurrentes.
        $newVisitors = DB::query()->fromSub(
            PageVisit::query()
                ->humans()
                ->select('visitor_id')
                ->selectRaw('MIN(visited_at) as first_seen')
                ->groupBy('visitor_id'),
            'visitors'
        )
            ->whereBetween('first_seen', [$start, $end])
            ->count();

        $newVisitors = min($newVisitors, $totalVisitors);
        $returningVisitors = max(0, $totalVisitors - $newVisitors);

        // Comparación contra el periodo inmediatamente anterior.
        $length = $start->diffInSeconds($end) + 1;
        $previousEnd = $start->copy()->subSecond();
        $previousStart = $previousEnd->copy()->subSeconds($length - 1);

        $previousBase = PageVisit::query()
            ->humans()
            ->whereBetween('visited_at', [$previousStart, $previousEnd]);

        $previousViews = (clone $previousBase)->count();
        $previousVisitors = (clone $previousBase)->distinct()->count('visitor_id');

        $previousLabel = $mode === 'week'
            ? 'la semana anterior'
            : 'el mes anterior';

        return [
            'period' => [
                'mode' => $mode,
                'start' => $start->format('Y-m-d'),
                'end' => $end->format('Y-m-d'),
                'label' => $this->periodLabel($mode, $start, $end),
                'previous_label' => $previousLabel,
            ],
            'kpis' => [
                'views' => $totalViews,
                'views_delta' => $this->percentageChange($totalViews, $previousViews),
                'previous_views' => $previousViews,
                'visitors' => $totalVisitors,
                'visitors_delta' => $this->percentageChange($totalVisitors, $previousVisitors),
                'previous_visitors' => $previousVisitors,
                'sessions' => $sessions,
                'views_per_visitor' => $totalVisitors > 0 ? round($totalViews / $totalVisitors, 2) : 0,
                'bounce_rate' => $bounceRate,
                'avg_session_seconds' => (int) round((float) ($sessionStats->avg_seconds ?? 0)),
                'pages' => $totalPages,
                'new_visitors' => $newVisitors,
                'returning_visitors' => $returningVisitors,
                'best_day' => $bestDay,
                'peak_hour' => $peakHour,
            ],
            'series' => [
                'labels' => $labels,
                'views' => $viewsSeries,
                'visitors' => $visitorsSeries,
            ],
            'hourly' => [
                'labels' => $hourlyLabels,
                'views' => $hourlySeries,
            ],
            'weekdays' => [
                'labels' => $weekdayLabels,
                'views' => $weekdaySeries,
            ],
            'top_pages' => $topPages,
            'referrers' => $referrers,
            'devices' => $devices,
            'browsers' => $browsers,
        ];
    }

    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    private function resolvePeriod(string $mode, string $week, string $month): array
    {
        if ($mode === 'month') {
            $start = Carbon::createFromFormat('Y-m', $month)->startOfMonth();

            return [$start, $start->copy()->endOfMonth()];
        }

        [$year, $weekNumber] = explode('-W', $week);

        $start = Carbon::now()
            ->setISODate((int) $year, (int) $weekNumber, 1)
            ->startOfDay();

        return [$start, $start->copy()->addDays(6)->endOfDay()];
    }

    private function periodLabel(string $mode, Carbon $start, Carbon $end): string
    {
        if ($mode === 'month') {
            return ucfirst($start->copy()->locale('es')->translatedFormat('F Y'));
        }

        return sprintf(
            'Semana %s · %s – %s',
            $start->isoFormat('W'),
            $start->copy()->locale('es')->translatedFormat('d M'),
            $end->copy()->locale('es')->translatedFormat('d M Y')
        );
    }

    private function pageLabel(?string $routeName, string $path): string
    {
        if ($routeName && isset(self::PAGE_LABELS[$routeName])) {
            return self::PAGE_LABELS[$routeName];
        }

        return $path === '/' ? 'Inicio' : $path;
    }

    private function percentageChange(int $current, int $previous): ?float
    {
        if ($previous === 0) {
            return $current > 0 ? 100.0 : null;
        }

        return round((($current - $previous) / $previous) * 100, 1);
    }

    private function normalizeWeek(?string $week): string
    {
        if (is_string($week) && preg_match('/^\d{4}-W\d{1,2}$/', $week)) {
            [, $number] = explode('-W', $week);
            $number = (int) $number;

            if ($number >= 1 && $number <= 53) {
                return sprintf('%s-W%02d', substr($week, 0, 4), $number);
            }
        }

        return now()->format('o-\WW');
    }

    private function normalizeMonth(?string $month): string
    {
        if (is_string($month) && preg_match('/^\d{4}-\d{2}$/', $month)) {
            [, $number] = explode('-', $month);
            $number = (int) $number;

            if ($number >= 1 && $number <= 12) {
                return $month;
            }
        }

        return now()->format('Y-m');
    }
}
