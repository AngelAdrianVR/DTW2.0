/**
 * Directiva `v-reveal`: revela un elemento cuando entra en el viewport.
 *
 * Uso:
 *   <div v-reveal>…</div>
 *   <div v-reveal="120">…</div>            (retardo en ms)
 *   <div v-reveal="{ delay: 120 }">…</div>
 *
 * Las clases viven en resources/css/app.css y respetan
 * `prefers-reduced-motion` (en ese caso el contenido se muestra sin animación).
 */
const REVEAL_CLASS = 'dtw-reveal';
const VISIBLE_CLASS = 'dtw-reveal--visible';

function prefersReducedMotion() {
    return (
        typeof window !== 'undefined' &&
        typeof window.matchMedia === 'function' &&
        window.matchMedia('(prefers-reduced-motion: reduce)').matches
    );
}

export default {
    mounted(el, binding) {
        el.classList.add(REVEAL_CLASS);

        const rawDelay = typeof binding.value === 'object' && binding.value !== null ? binding.value.delay : binding.value;
        const delay = Number(rawDelay);

        if (Number.isFinite(delay) && delay > 0) {
            el.style.transitionDelay = `${delay}ms`;
        }

        if (prefersReducedMotion() || !('IntersectionObserver' in window)) {
            el.classList.add(VISIBLE_CLASS);
            return;
        }

        const observer = new IntersectionObserver(
            (entries) => {
                entries.forEach((entry) => {
                    if (entry.isIntersecting) {
                        el.classList.add(VISIBLE_CLASS);
                        observer.disconnect();
                    }
                });
            },
            { threshold: 0.12, rootMargin: '0px 0px -8% 0px' },
        );

        observer.observe(el);
        el._dtwRevealObserver = observer;
    },

    unmounted(el) {
        el._dtwRevealObserver?.disconnect();
        delete el._dtwRevealObserver;
    },
};
