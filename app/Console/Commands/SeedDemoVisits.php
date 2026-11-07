<?php

namespace App\Console\Commands;

use App\Models\PageVisit;
use Carbon\Carbon;
use Illuminate\Console\Command;

/**
 * Genera visitas de ejemplo para previsualizar el módulo de métricas
 * en local. No debe ejecutarse en producción.
 */
class SeedDemoVisits extends Command
{
    protected $signature = 'visits:demo
        {--days=45 : Días hacia atrás que se van a rellenar}
        {--fresh : Elimina las visitas existentes antes de generar las de ejemplo}';

    protected $description = 'Genera visitas de ejemplo para el módulo de métricas (solo desarrollo)';

    private const PATHS = [
        ['path' => '/inicio', 'route' => 'landing.index', 'weight' => 34],
        ['path' => '/proyectos', 'route' => 'landing.projects', 'weight' => 20],
        ['path' => '/landing-projects/1', 'route' => 'landing-projects.show', 'weight' => 12],
        ['path' => '/landing-projects/2', 'route' => 'landing-projects.show', 'weight' => 9],
        ['path' => '/landing-projects/3', 'route' => 'landing-projects.show', 'weight' => 7],
        ['path' => '/privacy-policy', 'route' => 'privacy.show', 'weight' => 3],
        ['path' => '/terms-of-service', 'route' => 'terms.show', 'weight' => 2],
    ];

    private const REFERRERS = [
        ['host' => null, 'weight' => 34],
        ['host' => 'www.google.com', 'weight' => 26],
        ['host' => 'www.facebook.com', 'weight' => 12],
        ['host' => 'www.linkedin.com', 'weight' => 9],
        ['host' => 'www.instagram.com', 'weight' => 7],
        ['host' => 'www.bing.com', 'weight' => 5],
        ['host' => 'dtw.test', 'weight' => 7],
    ];

    private const DEVICES = [
        'desktop' => 58,
        'mobile' => 37,
        'tablet' => 5,
    ];

    private const AGENTS = [
        'desktop' => [
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/129.0.0.0 Safari/537.36',
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.4 Safari/605.1.15',
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:130.0) Gecko/20100101 Firefox/130.0',
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/129.0.0.0 Safari/537.36 Edg/129.0.0.0',
        ],
        'mobile' => [
            'Mozilla/5.0 (iPhone; CPU iPhone OS 17_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.5 Mobile/15E148 Safari/604.1',
            'Mozilla/5.0 (Linux; Android 14; Pixel 8) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/129.0.0.0 Mobile Safari/537.36',
            'Mozilla/5.0 (Linux; Android 13; SM-S918B) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/128.0.0.0 Mobile Safari/537.36',
        ],
        'tablet' => [
            'Mozilla/5.0 (iPad; CPU OS 17_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.5 Mobile/15E148 Safari/604.1',
        ],
    ];

    public function handle(): int
    {
        if (app()->environment('production')) {
            $this->error('Este comando no está permitido en producción.');

            return self::FAILURE;
        }

        $days = max(1, (int) $this->option('days'));

        if ($this->option('fresh')) {
            PageVisit::query()->delete();
            $this->warn('Visitas anteriores eliminadas.');
        }

        $rows = [];
        $now = Carbon::now();
        $visitorPool = [];

        for ($offset = $days - 1; $offset >= 0; $offset--) {
            $day = $now->copy()->subDays($offset)->startOfDay();
            $isWeekend = $day->isWeekend();

            $sessions = $isWeekend
                ? random_int(5, 14)
                : random_int(12, 30);

            for ($session = 0; $session < $sessions; $session++) {
                // 30 % de los visitantes ya habían entrado en días previos.
                if ($visitorPool && random_int(1, 100) <= 30) {
                    $visitorId = $visitorPool[array_rand($visitorPool)];
                } else {
                    $visitorId = 'demo-'.bin2hex(random_bytes(8));
                    $visitorPool[] = $visitorId;
                }

                $device = $this->weighted(self::DEVICES);
                $agent = self::AGENTS[$device][array_rand(self::AGENTS[$device])];
                $referrerHost = $this->weighted(array_column(self::REFERRERS, 'weight', 'host'));
                $views = random_int(1, 4);
                $sessionId = 'demo-session-'.bin2hex(random_bytes(8));

                $hour = (int) $this->weighted([
                    7 => 2, 8 => 4, 9 => 7, 10 => 9, 11 => 10, 12 => 8,
                    13 => 7, 14 => 8, 15 => 9, 16 => 10, 17 => 9, 18 => 7,
                    19 => 5, 20 => 4, 21 => 2, 22 => 1,
                ]);

                for ($view = 0; $view < $views; $view++) {
                    $page = self::PATHS[$this->weightedIndex(array_column(self::PATHS, 'weight'))];

                    $visitedAt = $day->copy()
                        ->addHours($hour)
                        ->addMinutes(random_int(0, 59))
                        ->addSeconds(random_int(0, 59));

                    if ($visitedAt->gt($now)) {
                        $visitedAt = $now->copy()->subMinutes(random_int(1, 30));
                    }

                    $rows[] = [
                        'path' => $page['path'],
                        'route_name' => $page['route'],
                        'referrer' => $referrerHost ? 'https://'.$referrerHost.'/' : null,
                        'referrer_host' => $referrerHost,
                        'user_agent' => $agent,
                        'visitor_id' => $visitorId,
                        'session_id' => $sessionId,
                        'ip_hash' => hash('sha256', $visitorId),
                        'device' => $device,
                        'browser' => $this->browserFromAgent($agent),
                        'platform' => $this->platformFromAgent($agent),
                        'is_bot' => false,
                        'visited_at' => $visitedAt,
                        'created_at' => $visitedAt,
                        'updated_at' => $visitedAt,
                    ];
                }
            }
        }

        // Algunas visitas de bots para comprobar que quedan excluidas.
        for ($bot = 0; $bot < max(3, (int) ($days / 4)); $bot++) {
            $visitedAt = $now->copy()->subDays(random_int(0, $days - 1))->subMinutes(random_int(0, 600));

            $rows[] = [
                'path' => '/inicio',
                'route_name' => 'landing.index',
                'referrer' => null,
                'referrer_host' => null,
                'user_agent' => 'Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)',
                'visitor_id' => 'bot-'.bin2hex(random_bytes(6)),
                'session_id' => null,
                'ip_hash' => hash('sha256', 'bot'.random_int(1, 999)),
                'device' => 'bot',
                'browser' => 'Otro',
                'platform' => 'Otro',
                'is_bot' => true,
                'visited_at' => $visitedAt,
                'created_at' => $visitedAt,
                'updated_at' => $visitedAt,
            ];
        }

        foreach (array_chunk($rows, 500) as $chunk) {
            PageVisit::insert($chunk);
        }

        $this->info(sprintf('Se generaron %s visitas de ejemplo en los últimos %d días.', number_format(count($rows)), $days));

        return self::SUCCESS;
    }

    /**
     * @param  array<string, int>  $weights
     */
    private function weighted(array $weights): string
    {
        $keys = array_keys($weights);

        return (string) $keys[$this->weightedIndex(array_values($weights))];
    }

    /**
     * @param  array<int, int>  $weights
     */
    private function weightedIndex(array $weights): int
    {
        $total = array_sum($weights);
        $random = random_int(1, $total);
        $accumulated = 0;

        foreach ($weights as $index => $weight) {
            $accumulated += $weight;

            if ($random <= $accumulated) {
                return $index;
            }
        }

        return 0;
    }

    private function browserFromAgent(string $agent): string
    {
        return match (true) {
            (bool) preg_match('/edg\//i', $agent) => 'Edge',
            (bool) preg_match('/firefox/i', $agent) => 'Firefox',
            (bool) preg_match('/chrome|crios/i', $agent) => 'Chrome',
            (bool) preg_match('/safari/i', $agent) => 'Safari',
            default => 'Otro',
        };
    }

    private function platformFromAgent(string $agent): string
    {
        return match (true) {
            (bool) preg_match('/iphone|ipad|ipod/i', $agent) => 'iOS',
            (bool) preg_match('/android/i', $agent) => 'Android',
            (bool) preg_match('/windows/i', $agent) => 'Windows',
            (bool) preg_match('/mac os x|macintosh/i', $agent) => 'macOS',
            default => 'Otro',
        };
    }
}
