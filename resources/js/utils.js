/**
 * Formatea un número como moneda MXN.
 * @param {number} value El número a formatear
 * @returns {string}
 */
export function formatCurrency(value) {
    if (value === null || value === undefined) {
        return '---';
    }
    return new Intl.NumberFormat('es-MX', {
        style: 'currency',
        currency: 'MXN',
    }).format(value);
}