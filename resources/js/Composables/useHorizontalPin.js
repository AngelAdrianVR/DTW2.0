import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';

const clamp = (value, min = 0, max = 1) => Math.min(Math.max(value, min), max);

/**
 * Convierte el scroll vertical de una sección en un desplazamiento horizontal
 * (efecto "pinned horizontal scroll").
 *
 * La sección crece a `100vh + ancho del riel`, el contenido se queda pegado
 * con `position: sticky` y el riel se mueve con `translateX` mientras el
 * usuario hace scroll. Cuando el riel termina, la página continúa normal.
 *
 * En pantallas chicas o con `prefers-reduced-motion` no se ancla nada y el
 * componente debe usar su variante de scroll horizontal nativo.
 *
 * `canPin` expone sólo esa condición de entorno (pantalla grande sin
 * reduced-motion) para los rieles que desbordan por diseño y no necesitan la
 * medición de `travel` para decidir si se anclan.
 *
 * @param {import('vue').Ref<HTMLElement|null>} sectionRef
 * @param {import('vue').Ref<HTMLElement|null>} trackRef
 * @param {{ padding?: number, container?: boolean, factor?: number }} options `container` mide
 *        contra el ancho del contenedor del riel (útil cuando el riel vive
 *        dentro de una columna) en lugar del ancho de la ventana. `factor`
 *        alarga el recorrido vertical: 2 = el riel avanza a la mitad de
 *        velocidad (se necesita el doble de scroll para completarlo).
 */
export function useHorizontalPin(
    sectionRef,
    trackRef,
    { padding = 0, container = false, factor = 1 } = {},
) {
    const progress = ref(0);
    const travel = ref(0);
    const viewportHeight = ref(0);
    const canPin = ref(false);

    let frame = null;
    let resizeObserver = null;
    const cleanups = [];

    const pinned = computed(() => canPin.value && travel.value > 8);

    // Alto total de la sección: un viewport + lo que mide el recorrido
    // multiplicado por el factor de suavizado.
    const sectionHeight = computed(() =>
        pinned.value ? `${viewportHeight.value + travel.value * Math.max(0.1, factor)}px` : undefined,
    );

    const offset = computed(() => -(progress.value * travel.value));

    function measure() {
        if (typeof window === 'undefined') {
            return;
        }

        viewportHeight.value = window.innerHeight;

        const track = trackRef.value;

        if (!track) {
            travel.value = 0;
            return;
        }

        travel.value = Math.max(
            0,
            track.scrollWidth -
                (container ? track.parentElement?.clientWidth ?? window.innerWidth : window.innerWidth) +
                padding,
        );
    }

    function update() {
        frame = null;

        const section = sectionRef.value;

        if (!section) {
            return;
        }

        const scrollable = section.offsetHeight - window.innerHeight;

        if (scrollable <= 0) {
            progress.value = 0;
            return;
        }

        progress.value = clamp(-section.getBoundingClientRect().top / scrollable);
    }

    function onScroll() {
        if (frame !== null) {
            return;
        }

        frame = requestAnimationFrame(update);
    }

    function onResize() {
        measure();
        onScroll();
    }

    function scrollToProgress(value) {
        const section = sectionRef.value;

        if (!section) {
            return;
        }

        const scrollable = section.offsetHeight - window.innerHeight;

        if (scrollable <= 0) {
            return;
        }

        const top = section.getBoundingClientRect().top + window.scrollY + clamp(value) * scrollable;

        window.scrollTo({ top, behavior: 'smooth' });
    }

    function observeTrack() {
        resizeObserver?.disconnect();

        if (!trackRef.value || typeof ResizeObserver === 'undefined') {
            return;
        }

        // Recalcula cuando cargan las imágenes o cambia el contenido del riel.
        resizeObserver = new ResizeObserver(() => {
            measure();
            onScroll();
        });

        resizeObserver.observe(trackRef.value);
    }

    onMounted(() => {
        const largeScreen = window.matchMedia('(min-width: 1024px)');
        const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');

        const sync = () => {
            canPin.value = largeScreen.matches && !reducedMotion.matches;
            measure();
            observeTrack();
            onScroll();
        };

        canPin.value = largeScreen.matches && !reducedMotion.matches;
        measure();
        observeTrack();
        onScroll();

        window.addEventListener('scroll', onScroll, { passive: true });
        window.addEventListener('resize', onResize);
        largeScreen.addEventListener?.('change', sync);
        reducedMotion.addEventListener?.('change', sync);

        watch(
            trackRef,
            () => {
                observeTrack();
                measure();
                onScroll();
            },
            { flush: 'post' },
        );

        cleanups.push(() => {
            window.removeEventListener('scroll', onScroll);
            window.removeEventListener('resize', onResize);
            largeScreen.removeEventListener?.('change', sync);
            reducedMotion.removeEventListener?.('change', sync);
            resizeObserver?.disconnect();

            if (frame !== null) {
                cancelAnimationFrame(frame);
            }
        });
    });

    onBeforeUnmount(() => {
        cleanups.forEach((cleanup) => cleanup());
        cleanups.length = 0;
    });

    return { progress, offset, travel, pinned, sectionHeight, scrollToProgress, measure, canPin };
}
