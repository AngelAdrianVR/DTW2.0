import { computed, unref } from 'vue';
import { usePage } from '@inertiajs/vue3';

/**
 * Normaliza un proyecto del back-office (WebContent) para la landing.
 *
 * El panel guarda un título en español y otro en inglés; se elige según el
 * idioma activo y se deja la ficha lista para enlazar. `cover_url` es la
 * portada elegida en el back-office e `image_url` ya viene resuelta (portada o,
 * si no hay, la primera imagen de la galería).
 *
 * @param {Record<string, any>|null} project
 * @param {'es'|'en'|string} locale
 */
export function translateProject(project, locale = 'es') {
    if (!project) {
        return null;
    }

    const title =
        locale === 'es'
            ? project.spanish_title || project.english_title || ''
            : project.english_title || project.spanish_title || '';

    return {
        id: project.id,
        title,
        subtitle: project.subtitle || '',
        image: project.cover_url || project.image_url || null,
        href: `/landing-projects/${project.id}`,
    };
}

/**
 * Lista de proyectos ya normalizada y traducida.
 *
 * @param {Array|import('vue').Ref<Array>|null} source Opcional; si se omite se
 *        usa la prop `portfolio` compartida por LandingController.
 */
export function useProjects(source = null) {
    const page = usePage();

    return computed(() => {
        const list = source ? unref(source) : page.props.portfolio;
        const locale = page.props.locale || 'es';

        return (list || []).map((project) => translateProject(project, locale));
    });
}
