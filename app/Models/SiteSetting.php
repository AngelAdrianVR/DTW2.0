<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Log;

/**
 * Ajustes sencillos del sitio (clave/valor) editables desde el back-office.
 */
class SiteSetting extends Model
{
    /** Ajuste que guarda la portada de carga inicial del sitio. */
    public const SPLASH_THEME_KEY = 'splash_theme';

    /** Temas disponibles para la pantalla de carga inicial. */
    public const SPLASH_THEMES = [
        'day-of-the-dead',
        'buen-fin',
        'christmas',
        'halloween',
    ];

    protected $primaryKey = 'key';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = ['key', 'value'];

    /**
     * Lee un ajuste y devuelve `$default` si no existe (o si la tabla aún no
     * se ha migrado en el entorno actual).
     */
    public static function get(string $key, ?string $default = null): ?string
    {
        try {
            return static::query()->where('key', $key)->value('value') ?? $default;
        } catch (QueryException $e) {
            Log::warning('No se pudo leer el ajuste "' . $key . '": ' . $e->getMessage());

            return $default;
        }
    }

    /**
     * Crea o actualiza un ajuste.
     */
    public static function put(string $key, ?string $value): void
    {
        static::query()->updateOrCreate(['key' => $key], ['value' => $value]);
    }

    /**
     * Portada de carga activa, con Día de Muertos como respaldo.
     */
    public static function splashTheme(): string
    {
        $theme = static::get(self::SPLASH_THEME_KEY, 'day-of-the-dead');

        return in_array($theme, self::SPLASH_THEMES, true) ? $theme : 'day-of-the-dead';
    }
}
