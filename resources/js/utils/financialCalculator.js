/**
 * Motor de la calculadora de pérdidas financieras de la landing.
 *
 * Todas las funciones son puras y sin dependencias para poder reutilizarlas
 * y probarlas por separado. Los importes SIEMPRE son mensuales y en MXN.
 */

/** Semanas promedio por mes (52 / 12). */
export const WEEKS_PER_MONTH = 4.3333;

const round2 = (value) => Math.round((Number(value) + Number.EPSILON) * 100) / 100;

/**
 * Convierte cualquier entrada (string con formato, null, undefined…) en número.
 * Devuelve 0 cuando el valor no es interpretable.
 */
export function toNumber(value) {
    if (value === null || value === undefined || value === '') {
        return 0;
    }

    if (typeof value === 'string') {
        // Acepta "1,500.50", "1500,50" o "$1 500"
        const cleaned = value.replace(/[^0-9.,-]/g, '').replace(/,/g, '.');
        const parsed = Number.parseFloat(cleaned);
        return Number.isFinite(parsed) ? parsed : 0;
    }

    const parsed = Number(value);
    return Number.isFinite(parsed) ? parsed : 0;
}

/** Limita un número al rango indicado (por defecto: nunca negativo). */
export function clampNumber(value, min = 0, max = Number.MAX_SAFE_INTEGER) {
    const number = toNumber(value);
    if (number < min) return min;
    if (number > max) return max;
    return number;
}

/** Los porcentajes de la calculadora se mueven siempre entre 0 y 100. */
export function clampPercent(value) {
    return clampNumber(value, 0, 100);
}

/** Formato de moneda usado en toda la landing (MXN, locale es-MX). */
export function formatMXN(value) {
    return new Intl.NumberFormat('es-MX', {
        style: 'currency',
        currency: 'MXN',
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    }).format(toNumber(value));
}

/** Formato de moneda sin decimales (para cifras grandes de resumen). */
export function formatMXNCompact(value) {
    return new Intl.NumberFormat('es-MX', {
        style: 'currency',
        currency: 'MXN',
        maximumFractionDigits: 0,
    }).format(toNumber(value));
}

export function formatPercent(value, decimals = 0) {
    return `${new Intl.NumberFormat('es-MX', {
        minimumFractionDigits: decimals,
        maximumFractionDigits: decimals,
    }).format(toNumber(value))}%`;
}

export function formatNumber(value, decimals = 0) {
    return new Intl.NumberFormat('es-MX', {
        minimumFractionDigits: decimals,
        maximumFractionDigits: decimals,
    }).format(toNumber(value));
}

/**
 * Cálculo por escenario. Cada uno devuelve el costo mensual del problema
 * (lo que se pierde hoy) y el monto recuperable (lo que se puede rescatar).
 */
const CALCULATORS = {
    // A) Ventas y seguimiento comercial
    sales: (inputs) => {
        const quotes = Math.max(0, toNumber(inputs.quotes));
        const noFollowUp = clampPercent(inputs.noFollowUpPercent);
        const recovery = clampPercent(inputs.recoveryPercent);
        const profit = Math.max(0, toNumber(inputs.averageProfit));

        const lostQuotes = quotes * (noFollowUp / 100);
        const monthlyCost = lostQuotes * profit;
        const recoverable = monthlyCost * (recovery / 100);

        return { monthlyCost, recoverable };
    },

    // B) Operaciones y tareas manuales
    operations: (inputs) => {
        const employees = Math.max(0, toNumber(inputs.employees));
        const weeklyHours = Math.max(0, toNumber(inputs.weeklyHours));
        const hourlyCost = Math.max(0, toNumber(inputs.hourlyCost));
        const automatable = clampPercent(inputs.automatablePercent);

        const monthlyCost = employees * weeklyHours * WEEKS_PER_MONTH * hourlyCost;
        const recoverable = monthlyCost * (automatable / 100);

        return { monthlyCost, recoverable };
    },

    // C) Inventario y control de existencias
    inventory: (inputs) => {
        const inventoryValue = Math.max(0, toNumber(inputs.inventoryValue));
        const lossPercent = clampPercent(inputs.lossPercent);
        const reduction = clampPercent(inputs.reductionPercent);

        const monthlyCost = inventoryValue * (lossPercent / 100);
        const recoverable = monthlyCost * (reduction / 100);

        return { monthlyCost, recoverable };
    },

    // D) Paros operativos y tiempos muertos
    downtime: (inputs) => {
        const downtimeHours = Math.max(0, toNumber(inputs.downtimeHours));
        const hourlyOperatingCost = Math.max(0, toNumber(inputs.hourlyOperatingCost));
        const reduction = clampPercent(inputs.reductionPercent);

        const monthlyCost = downtimeHours * hourlyOperatingCost;
        const recoverable = monthlyCost * (reduction / 100);

        return { monthlyCost, recoverable };
    },
};

export const SCENARIO_IDS = Object.keys(CALCULATORS);

/**
 * Calcula el resultado completo de un escenario.
 *
 * @param {string} scenarioId  sales | operations | inventory | downtime
 * @param {object} inputs      Valores capturados por el usuario
 * @returns {{scenarioId: string, monthlyCost: number, recoverable: number, annualSaving: number, improvement: number}}
 */
export function computeScenario(scenarioId, inputs = {}) {
    const calculator = CALCULATORS[scenarioId];

    if (!calculator) {
        return {
            scenarioId,
            monthlyCost: 0,
            recoverable: 0,
            annualSaving: 0,
            improvement: 0,
        };
    }

    const { monthlyCost, recoverable } = calculator(inputs || {});

    // La mejora es recuperable / costo del problema, acotada a 100%.
    const improvement = monthlyCost > 0 ? (recoverable / monthlyCost) * 100 : 0;

    return {
        scenarioId,
        monthlyCost: round2(monthlyCost),
        recoverable: round2(recoverable),
        annualSaving: round2(recoverable * 12),
        improvement: round2(Math.min(100, Math.max(0, improvement))),
    };
}

/**
 * Estima el retorno de una inversión de automatización.
 * Se usa en la sección de ROI (ejemplo ilustrativo).
 */
export function computeRoi({ investment = 0, monthlySaving = 0 } = {}) {
    const safeInvestment = Math.max(0, toNumber(investment));
    const safeSaving = Math.max(0, toNumber(monthlySaving));

    const monthsToBreakEven = safeSaving > 0 ? safeInvestment / safeSaving : 0;
    const annualSaving = safeSaving * 12;
    const firstYearNet = annualSaving - safeInvestment;
    const roi = safeInvestment > 0 ? (firstYearNet / safeInvestment) * 100 : 0;

    return {
        investment: round2(safeInvestment),
        monthlySaving: round2(safeSaving),
        annualSaving: round2(annualSaving),
        monthsToBreakEven: round2(monthsToBreakEven),
        firstYearNet: round2(firstYearNet),
        roi: round2(roi),
    };
}
