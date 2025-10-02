
import { usePage } from '@inertiajs/vue3';

/**
 * Hook para manejar las traducciones.
 * Provee una función __() que busca la clave en las traducciones compartidas.
 */
export function useTranslations() {
    const { props } = usePage();

    /**
     * Traduce una clave de texto.
     * @param {string} key - La clave de traducción (ej. "Welcome to our company").
     * @returns {string} - El texto traducido o la clave si no se encuentra.
     */
    const __ = (key) => {
        // Accede a las traducciones desde las props de la página
        const translations = props.translations || {};
        return translations[key] || key;
    };

    return { __ };
}
