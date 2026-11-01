import { computed, unref } from 'vue';
import { usePage } from '@inertiajs/vue3';

/**
 * Formatea una fecha "de calendario" (event_date, ventana de registro) sin
 * convertir de zona horaria.
 *
 * El backend serializa `2026-11-02T00:00:00.000000Z`; si se pasara por
 * `new Date(...)` en México se mostraría el día anterior. Aquí se toma el año,
 * mes y día tal como vienen escritos.
 *
 * @param {string|null|undefined} value
 * @param {'es'|'en'|string} locale
 */
export function formatEventDate(value, locale = 'es') {
    if (!value) {
        return '';
    }

    const match = String(value).match(/^(\d{4})-(\d{2})-(\d{2})/);

    if (!match) {
        return '';
    }

    const date = new Date(Number(match[1]), Number(match[2]) - 1, Number(match[3]));

    return date.toLocaleDateString(locale === 'en' ? 'en-US' : 'es-MX', {
        day: 'numeric',
        month: 'long',
        year: 'numeric',
    });
}

/**
 * Elige el texto del idioma activo y cae al otro si falta (el back-office no
 * obliga a capturar los dos).
 */
function pick(spanish, english, locale) {
    return locale === 'en' ? english || spanish || '' : spanish || english || '';
}

/**
 * Normaliza un evento del backend para la landing: textos en el idioma activo,
 * fechas legibles y ganadores ya traducidos.
 *
 * @param {Record<string, any>|null} event
 * @param {'es'|'en'|string} locale
 */
export function translateEvent(event, locale = 'es') {
    if (!event) {
        return null;
    }

    return {
        id: event.id,
        slug: event.slug,
        href: `/eventos/${event.slug}`,
        title: pick(event.spanish_title, event.english_title, locale),
        description: pick(event.spanish_description, event.english_description, locale),
        challenge: pick(event.spanish_challenge, event.english_challenge, locale),
        prizesSummary: pick(event.spanish_prizes_summary, event.english_prizes_summary, locale),
        image: event.image_url || null,
        status: event.status || 'draft',
        canRegister: Boolean(event.can_register),
        hasWinners: Boolean(event.has_published_winners),
        eventDate: event.event_date || null,
        eventDateLabel: formatEventDate(event.event_date, locale),
        registrationStartsAt: event.registration_starts_at || null,
        registrationEndsAt: event.registration_ends_at || null,
        registrationEndsLabel: formatEventDate(event.registration_ends_at, locale),
        registrationsCount: Number(event.registrations_count || 0),
        customField: {
            label: pick(event.custom_field_label_es, event.custom_field_label_en, locale),
            placeholder: pick(
                event.custom_field_placeholder_es,
                event.custom_field_placeholder_en,
                locale,
            ),
            required: Boolean(event.custom_field_required),
        },
        winners: (event.published_winners || []).map((winner) => ({
            id: winner.id,
            name: winner.name,
            businessName: winner.business_name || '',
            prize: pick(winner.spanish_prize, winner.english_prize, locale),
            note: pick(winner.spanish_note, winner.english_note, locale),
        })),
    };
}

/**
 * Lista de eventos ya normalizada.
 *
 * @param {Array|import('vue').Ref<Array>|null} source Opcional; si se omite se
 *        usa la prop `events` que comparte LandingController.
 */
export function useEvents(source = null) {
    const page = usePage();

    return computed(() => {
        const list = source ? unref(source) : page.props.events;
        const locale = page.props.locale || 'es';

        return (list || []).map((event) => translateEvent(event, locale));
    });
}
