import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { useT } from './useT';

/**
 * Datos de contacto y enlaces listos para usar (WhatsApp / correo).
 *
 * Los toma de la prop `contact` que comparte LandingController en todas las
 * vistas públicas, así ningún componente repite la construcción de la URL.
 */
export function useContact() {
    const page = usePage();
    const t = useT();

    const contact = computed(() => page.props.contact || {});
    const whatsapp = computed(() => contact.value.whatsapp || '');
    const whatsappDisplay = computed(() => contact.value.whatsapp_display || '');
    const email = computed(() => contact.value.email || '');

    const whatsappUrl = computed(() => {
        if (!whatsapp.value) {
            return '';
        }

        const message = t('Hello, I want a free diagnosis for my company.');

        return `https://wa.me/${whatsapp.value}?text=${encodeURIComponent(message)}`;
    });

    const emailUrl = computed(() => {
        if (!email.value) {
            return '';
        }

        const subject = t('Free diagnosis for my company');

        return `mailto:${email.value}?subject=${encodeURIComponent(subject)}`;
    });

    return { contact, whatsapp, whatsappDisplay, email, whatsappUrl, emailUrl };
}
