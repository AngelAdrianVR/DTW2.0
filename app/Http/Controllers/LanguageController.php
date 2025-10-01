<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Session;

class LanguageController extends Controller
{
    /**
     * Cambia el idioma de la aplicación y lo guarda en la sesión.
     *
     * @param string $locale
     * @return \Illuminate\Http\RedirectResponse
     */
    public function switch($locale)
    {
        // Valida que el idioma esté permitido (ej. 'en' o 'es')
        if (!in_array($locale, ['en', 'es'])) {
            abort(400, 'Idioma no soportado.');
        }

        // Establece el idioma para la sesión actual
        App::setLocale($locale);
        // Guarda el idioma en la sesión para futuras peticiones
        Session::put('locale', $locale);

        // Redirige al usuario a la página anterior
        return redirect()->back();
    }
}
