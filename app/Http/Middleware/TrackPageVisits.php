<?php

namespace App\Http\Middleware;

use App\Models\PageVisit;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * Registra de forma pasiva cada visita a las páginas públicas del sitio.
 *
 * Se ejecuta en terminate(), es decir, después de enviar la respuesta al
 * visitante, para que la analítica nunca añada latencia a la navegación.
 */
class TrackPageVisits
{
    /**
     * Rutas internas (panel, assets, auth, API...) que no son tráfico del sitio.
     */
    private const EXCLUDED_PREFIXES = [
        'admin',
        'dashboard',
        'api',
        'tpsp',
        'build',
        'storage',
        'up',
        'login',
        'logout',
        'register',
        'forgot-password',
        'reset-password',
        'user',
        'profile',
        'sanctum',
        'public',
        'vendor',
        '_ignition',
        'test-419',
    ];

    private const BOT_PATTERN = '/bot|crawl|spider|slurp|bingpreview|facebookexternalhit|headless|curl|wget|python-requests|postman|go-http-client|scrapy|semrush|ahrefs|pingdom|lighthouse/i';

    public function handle(Request $request, Closure $next): Response
    {
        // Identificador persistente de visitante (cookie de 1 año) para poder
        // distinguir visitantes nuevos de recurrentes entre distintas sesiones.
        $visitorId = $request->cookie('dtw_vid');

        if (! $visitorId && $request->isMethod('GET')) {
            $visitorId = (string) Str::uuid();
            Cookie::queue('dtw_vid', $visitorId, 60 * 24 * 365);
        }

        $request->attributes->set('dtw_visitor_id', $visitorId);

        return $next($request);
    }

    public function terminate(Request $request, Response $response): void
    {
        try {
            if (! $this->shouldTrack($request, $response)) {
                return;
            }

            $userAgent = (string) $request->userAgent();
            $isBot = (bool) preg_match(self::BOT_PATTERN, $userAgent);

            $sessionId = $request->hasSession() ? $request->session()->getId() : null;
            $ipHash = hash('sha256', ($request->ip() ?? 'unknown').'|'.config('app.key'));
            $visitorId = $request->attributes->get('dtw_visitor_id') ?: ($sessionId ?: $ipHash);

            PageVisit::create([
                'path' => Str::limit('/'.ltrim($request->path(), '/'), 255, ''),
                'route_name' => $request->route()?->getName(),
                'referrer' => Str::limit((string) $request->headers->get('referer'), 500, ''),
                'referrer_host' => $this->referrerHost($request),
                'user_agent' => Str::limit($userAgent, 500, ''),
                'visitor_id' => $visitorId,
                'session_id' => $sessionId,
                'ip_hash' => $ipHash,
                'device' => $this->device($userAgent, $isBot),
                'browser' => $this->browser($userAgent),
                'platform' => $this->platform($userAgent),
                'is_bot' => $isBot,
                'visited_at' => now(),
            ]);
        } catch (\Throwable $e) {
            // La analítica nunca debe romper la visita del usuario.
            Log::debug('No se pudo registrar la visita: '.$e->getMessage());
        }
    }

    private function shouldTrack(Request $request, Response $response): bool
    {
        if (! $request->isMethod('GET')) {
            return false;
        }

        if ($response->getStatusCode() !== 200) {
            return false;
        }

        // Solo páginas HTML (no assets, JSON ni descargas).
        if (! Str::contains((string) $response->headers->get('Content-Type'), 'text/html')) {
            return false;
        }

        // Ignorar recargas parciales de Inertia: no son visitas nuevas.
        if ($request->headers->has('X-Inertia-Partial-Component')
            || $request->headers->has('X-Inertia-Partial-Data')) {
            return false;
        }

        // La portada temática es sólo una transición: la visita real es la
        // landing a la que entra después.
        if ($request->route()?->getName() === 'landing.splash') {
            return false;
        }

        $path = trim($request->path(), '/');

        foreach (self::EXCLUDED_PREFIXES as $prefix) {
            if ($path === $prefix || Str::startsWith($path, $prefix.'/')) {
                return false;
            }
        }

        // Ignorar archivos con extensión (favicon, sitemap, imágenes...).
        if ($path !== '' && Str::contains(basename($path), '.')) {
            return false;
        }

        return true;
    }

    private function referrerHost(Request $request): ?string
    {
        $referrer = (string) $request->headers->get('referer');

        if ($referrer === '') {
            return null;
        }

        $host = parse_url($referrer, PHP_URL_HOST);

        return $host ? Str::lower($host) : null;
    }

    private function device(string $userAgent, bool $isBot): string
    {
        if ($isBot) {
            return 'bot';
        }

        if (preg_match('/ipad|tablet|kindle|silk|playbook/i', $userAgent)) {
            return 'tablet';
        }

        if (preg_match('/mobile|android|iphone|ipod|blackberry|opera mini|iemobile/i', $userAgent)) {
            return 'mobile';
        }

        return 'desktop';
    }

    private function browser(string $userAgent): ?string
    {
        return match (true) {
            $userAgent === '' => null,
            (bool) preg_match('/edg\//i', $userAgent) => 'Edge',
            (bool) preg_match('/opr\/|opera/i', $userAgent) => 'Opera',
            (bool) preg_match('/samsungbrowser/i', $userAgent) => 'Samsung Internet',
            (bool) preg_match('/chrome|crios/i', $userAgent) => 'Chrome',
            (bool) preg_match('/firefox|fxios/i', $userAgent) => 'Firefox',
            (bool) preg_match('/safari/i', $userAgent) => 'Safari',
            default => 'Otro',
        };
    }

    private function platform(string $userAgent): ?string
    {
        return match (true) {
            $userAgent === '' => null,
            (bool) preg_match('/iphone|ipad|ipod/i', $userAgent) => 'iOS',
            (bool) preg_match('/android/i', $userAgent) => 'Android',
            (bool) preg_match('/windows/i', $userAgent) => 'Windows',
            (bool) preg_match('/mac os x|macintosh/i', $userAgent) => 'macOS',
            (bool) preg_match('/linux/i', $userAgent) => 'Linux',
            default => 'Otro',
        };
    }
}
