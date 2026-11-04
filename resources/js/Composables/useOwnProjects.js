import { computed, unref } from 'vue';
import { usePage } from '@inertiajs/vue3';

/**
 * Texto plano a partir del HTML que guarda el editor del back-office.
 * Las tarjetas no renderizan HTML: sólo una descripción corta y legible.
 *
 * @param {string} value
 */
function plainText(value = '') {
    return String(value)
        .replace(/<[^>]*>/g, ' ')
        .replace(/&nbsp;/g, ' ')
        .replace(/\s+/g, ' ')
        .trim();
}

/**
 * Normaliza un proyecto propio del back-office (WebContent tipo `own_projects`).
 *
 * Son los proyectos que DTW construye y libera gratis para la comunidad: no
 * tienen ficha interna (a diferencia de los proyectos de clientes), así que la
 * tarjeta enlaza al sitio externo guardado en `link_url`.
 *
 * @param {Record<string, any>|null} project
 * @param {'es'|'en'|string} locale
 */
export function translateOwnProject(project, locale = 'es') {
    if (!project) {
        return null;
    }

    const title =
        locale === 'es'
            ? project.spanish_title || project.english_title || ''
            : project.english_title || project.spanish_title || '';

    const content =
        locale === 'es'
            ? project.spanish_content || project.english_content || ''
            : project.english_content || project.spanish_content || '';

    return {
        id: project.id,
        title,
        subtitle: project.subtitle || '',
        description: plainText(content),
        image: project.cover_url || project.image_url || null,
        url: project.link_url || '',
        linkText: project.link_text || '',
    };
}

/**
 * Lista de proyectos propios ya normalizada y traducida.
 *
 * @param {Array|import('vue').Ref<Array>|null} source Opcional; si se omite se
 *        usa la prop `ownProjects` compartida por LandingController.
 */
export function useOwnProjects(source = null) {
    const page = usePage();

    return computed(() => {
        const list = source ? unref(source) : page.props.ownProjects;
        const locale = page.props.locale || 'es';

        return (list || []).map((project) => translateOwnProject(project, locale));
    });
}
