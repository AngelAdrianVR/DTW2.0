<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\File;
use League\CommonMark\GithubFlavoredMarkdownConverter;

class LegalController extends Controller
{
    /**
     * Muestra la página de Términos de Servicio.
     */
    public function showTerms()
    {
        list($content, $title) = $this->getLegalContent('TermsOfService', 'Términos de Servicio', 'Terms of Service');

        return Inertia::render('LandingPage/Legal/Show', [
            'title' => $title,
            'content' => $content,
        ]);
    }

    /**
     * Muestra la página de Política de Privacidad.
     */
    public function showPrivacy()
    {
        list($content, $title) = $this->getLegalContent('PrivacyPolicy', 'Política de Privacidad', 'Privacy Policy');

        return Inertia::render('LandingPage/Legal/Show', [
            'title' => $title,
            'content' => $content,
        ]);
    }

    /**
     * Obtiene y convierte el contenido de un archivo legal Markdown a HTML.
     *
     * @param string $fileName El nombre base del archivo (ej. 'TermsOfService').
     * @param string $titleEs El título en español.
     * @param string $titleEn El título en inglés.
     * @return array [string $htmlContent, string $title]
     */
    private function getLegalContent(string $fileName, string $titleEs, string $titleEn): array
    {
        $locale = App::getLocale(); // 'es' o 'en'
        $title = ($locale === 'es') ? $titleEs : $titleEn;

        $path = resource_path("js/Pages/Public/Legal/{$fileName}_" . strtoupper($locale) . ".md");

        // Si el archivo del idioma actual no existe, intenta usar el inglés como fallback.
        if (!File::exists($path)) {
            $path = resource_path("js/Pages/Public/Legal/{$fileName}_EN.md");
        }
        
        // Si aún no existe, muestra un mensaje de error.
        if (!File::exists($path)) {
            return ['<p>Contenido no disponible.</p>', $title];
        }

        $markdownContent = File::get($path);

        $converter = new GithubFlavoredMarkdownConverter();
        $htmlContent = $converter->convert($markdownContent)->getContent();
        
        return [$htmlContent, $title];
    }
}
