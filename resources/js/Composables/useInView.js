import { onBeforeUnmount, onMounted, ref } from 'vue';

/**
 * Detecta cuándo un elemento entra en el viewport.
 *
 * Se usa para animar cifras y activar el revelado de secciones sin castigar
 * el rendimiento (IntersectionObserver, sin listeners de scroll).
 *
 * @param {{threshold?: number, rootMargin?: string, once?: boolean}} options
 * @returns {{target: import('vue').Ref, isInView: import('vue').Ref<boolean>}}
 */
export function useInView(options = {}) {
    const { threshold = 0.15, rootMargin = '0px 0px -10% 0px', once = true } = options;

    const target = ref(null);
    const isInView = ref(false);
    let observer = null;

    onMounted(() => {
        // Sin soporte (o sin elemento) mostramos el contenido de inmediato.
        if (typeof window === 'undefined' || !('IntersectionObserver' in window) || !target.value) {
            isInView.value = true;
            return;
        }

        observer = new IntersectionObserver(
            (entries) => {
                entries.forEach((entry) => {
                    if (entry.isIntersecting) {
                        isInView.value = true;
                        if (once) {
                            observer?.disconnect();
                        }
                    } else if (!once) {
                        isInView.value = false;
                    }
                });
            },
            { threshold, rootMargin },
        );

        observer.observe(target.value);
    });

    onBeforeUnmount(() => {
        observer?.disconnect();
    });

    return { target, isInView };
}
