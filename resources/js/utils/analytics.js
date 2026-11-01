/**
 * Analítica ligera de la landing.
 *
 * No se envía ningún dato personal: solo identificadores de sección/escenario
 * y cifras agregadas. Se publica en `window.dataLayer` (compatible con GTM)
 * y además emite un CustomEvent por si se quiere conectar otro destino.
 */
const EVENT_CHANNEL = 'dtw:track';

export function track(event, properties = {}) {
    if (typeof window === 'undefined') {
        return;
    }

    const payload = {
        event,
        ...properties,
        timestamp: new Date().toISOString(),
    };

    window.dataLayer = window.dataLayer || [];
    window.dataLayer.push(payload);
    window.dispatchEvent(new CustomEvent(EVENT_CHANNEL, { detail: payload }));

    if (import.meta.env?.DEV) {
        // eslint-disable-next-line no-console
        console.debug('[DTW analytics]', payload);
    }
}

export function trackCtaClick(location) {
    track('cta_click', { location });
}

export function trackWhatsAppClick(location) {
    track('whatsapp_click', { location });
}

export function trackCalculatorStart(scenario) {
    track('calculator_start', { scenario });
}

export function trackCalculatorResult(result) {
    track('calculator_result', {
        scenario: result?.scenarioId ?? null,
        monthly_cost: Math.round(Number(result?.monthlyCost) || 0),
        recoverable: Math.round(Number(result?.recoverable) || 0),
        annual_saving: Math.round(Number(result?.annualSaving) || 0),
        improvement: Math.round(Number(result?.improvement) || 0),
    });
}

export function trackLeadRequest({ scenario = null, employees = null, language = null } = {}) {
    track('lead_request', { scenario, employees, language });
}
