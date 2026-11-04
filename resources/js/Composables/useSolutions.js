import { computed, unref } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { useT } from './useT';

/**
 * Resuelve con t() los textos de una solución.
 *
 * Las soluciones viven en `config/dtw.php` y el controlador las envía como
 * props de Inertia; los textos se guardan como claves de traducción para que
 * la misma estructura sirva en español e inglés.
 *
 * @param {Record<string, any>|null} solution
 * @param {(key: string) => string} t
 */
export function translateSolution(solution, t) {
    if (!solution) {
        return null;
    }

    return {
        slug: solution.slug,
        icon: solution.icon,
        accent: solution.accent || '#A855F7',
        image: solution.image,
        gallery: solution.gallery || [],
        title: t(solution.title_key),
        tagline: t(solution.tagline_key),
        summary: t(solution.summary_key),
        bullets: (solution.bullet_keys || []).map((key) => t(key)),
    };
}

/**
 * Lista de soluciones ya traducida.
 *
 * @param {Array|import('vue').Ref<Array>|null} source Opcional; si se omite
 *        se usa la prop `solutions` compartida por el controlador.
 */
export function useSolutions(source = null) {
    const page = usePage();
    const t = useT();

    return computed(() => {
        const list = source ? unref(source) : page.props.solutions;

        return (list || []).map((solution) => translateSolution(solution, t));
    });
}
