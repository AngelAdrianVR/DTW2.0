import { usePage } from '@inertiajs/vue3';

/**
 * Traducción disponible dentro de <script setup>.
 *
 * La app ya expone un método global `t()` mediante un mixin (app.js), pero los
 * mixins no están disponibles en el ámbito de `setup`. Este composable reutiliza
 * exactamente la misma lógica: clave = frase en inglés, valor = traducción.
 */
export function useT() {
    const page = usePage();

    return (key, replacements = {}) => {
        const translations = page.props.translations || {};
        let translation = translations[key] || key;

        Object.keys(replacements).forEach((placeholder) => {
            translation = translation.replace(`:${placeholder}`, replacements[placeholder]);
        });

        return translation;
    };
}
