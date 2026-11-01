/**
 * Definición de los escenarios de la calculadora financiera.
 *
 * IMPORTANTE: aquí solo viven claves de traducción (`*Key`), nunca texto final.
 * El texto real está en lang/es/es.json y lang/en/en.json, así que el mismo
 * componente funciona en español y en inglés.
 */
export const CALCULATOR_SCENARIOS = [
    {
        id: 'sales',
        icon: 'pi pi-chart-line',
        titleKey: 'Missed sales and follow-up',
        shortKey: 'Sales',
        descriptionKey:
            'Quotes that never get a second call, renewals nobody follows up and opportunities that die in a spreadsheet.',
        fields: [
            {
                key: 'quotes',
                type: 'number',
                labelKey: 'Quotes or leads per month',
                hintKey: 'How many quotes or leads does your team generate in a month?',
                default: 40,
                min: 0,
                max: 100000,
                step: 1,
            },
            {
                key: 'noFollowUpPercent',
                type: 'percent',
                labelKey: 'Quotes without follow-up',
                hintKey: 'Most SMEs lose between 30% and 50% of their quotes because nobody follows up.',
                default: 40,
                min: 0,
                max: 100,
                step: 5,
            },
            {
                key: 'recoveryPercent',
                type: 'percent',
                labelKey: 'Quotes you could realistically recover',
                hintKey: 'We always use a conservative scenario: recovering one of every three is already a gain.',
                default: 30,
                min: 0,
                max: 100,
                step: 5,
            },
            {
                key: 'averageProfit',
                type: 'currency',
                labelKey: 'Average profit per sale',
                hintKey: 'Gross margin per closed sale, not total revenue.',
                default: 2500,
                min: 0,
                max: 10000000,
                step: 100,
            },
        ],
        formulaKeys: [
            'Quotes without follow-up = quotes per month × % without follow-up',
            'Monthly cost = quotes without follow-up × average profit',
            'Recoverable = monthly cost × % recovery',
        ],
        assumptionKeys: [
            'All figures are monthly and expressed in Mexican pesos (MXN).',
            'Average profit is gross margin per closed sale, not revenue.',
            'The recovery percentage is deliberately conservative.',
        ],
    },
    {
        id: 'operations',
        icon: 'pi pi-cog',
        titleKey: 'Manual operations and repetitive tasks',
        shortKey: 'Operations',
        descriptionKey:
            'Exports, copy and paste between systems, reports built by hand and approvals that wait for someone to be available.',
        fields: [
            {
                key: 'employees',
                type: 'number',
                labelKey: 'People doing repetitive tasks',
                hintKey: 'Count only the people whose work is affected every week.',
                default: 5,
                min: 0,
                max: 5000,
                step: 1,
            },
            {
                key: 'weeklyHours',
                type: 'number',
                labelKey: 'Repetitive hours per person per week',
                hintKey: 'An honest estimate of hours spent on tasks a system could do.',
                default: 8,
                min: 0,
                max: 60,
                step: 1,
            },
            {
                key: 'hourlyCost',
                type: 'currency',
                labelKey: 'Hourly cost per person',
                hintKey: 'Salary plus employer burden, divided by the hours worked.',
                default: 120,
                min: 0,
                max: 100000,
                step: 10,
            },
            {
                key: 'automatablePercent',
                type: 'percent',
                labelKey: 'Automatable share of those hours',
                hintKey: 'Not everything can be automated: we only count what a system can truly absorb.',
                default: 60,
                min: 0,
                max: 100,
                step: 5,
            },
        ],
        formulaKeys: [
            'Monthly cost = people × weekly hours × 4.33 weeks × hourly cost',
            'Recoverable = monthly cost × % automatable',
        ],
        assumptionKeys: [
            'A month is estimated at 4.33 weeks (52 weeks / 12 months).',
            'Only time that can really be automated is counted.',
            'The hourly cost includes salary plus employer burden.',
        ],
    },
    {
        id: 'inventory',
        icon: 'pi pi-box',
        titleKey: 'Inventory and stock control',
        shortKey: 'Inventory',
        descriptionKey:
            'Stockouts that lose the sale, dead stock that ties up cash and differences nobody can explain.',
        fields: [
            {
                key: 'inventoryValue',
                type: 'currency',
                labelKey: 'Inventory value',
                hintKey: 'Average value of the stock you keep in the warehouse or store.',
                default: 800000,
                min: 0,
                max: 1000000000,
                step: 1000,
            },
            {
                key: 'lossPercent',
                type: 'percent',
                labelKey: 'Monthly loss from stockouts or shrinkage',
                hintKey: 'Include lost sales, expired stock, shrinkage and emergency purchases.',
                default: 4,
                min: 0,
                max: 100,
                step: 0.5,
            },
            {
                key: 'reductionPercent',
                type: 'percent',
                labelKey: 'Loss reduction with automation',
                hintKey: 'Real-time stock visibility and automatic replenishment make the difference.',
                default: 50,
                min: 0,
                max: 100,
                step: 5,
            },
        ],
        formulaKeys: [
            'Monthly cost = inventory value × monthly loss %',
            'Recoverable = monthly cost × % reduction',
        ],
        assumptionKeys: [
            'Losses are expressed as a percentage of the inventory value.',
            'The reduction percentage assumes alerts and automatic replenishment.',
        ],
    },
    {
        id: 'downtime',
        icon: 'pi pi-clock',
        titleKey: 'Downtime and operational interruptions',
        shortKey: 'Downtime',
        descriptionKey:
            'Systems that fail, processes that stop and teams that cannot work while someone fixes it by hand.',
        fields: [
            {
                key: 'downtimeHours',
                type: 'number',
                labelKey: 'Downtime hours per month',
                hintKey: 'Hours in a month when the operation cannot move forward.',
                default: 20,
                min: 0,
                max: 744,
                step: 1,
            },
            {
                key: 'hourlyOperatingCost',
                type: 'currency',
                labelKey: 'Operating cost per hour of downtime',
                hintKey: 'Sales you do not close, staff standing by and extra costs to catch up.',
                default: 900,
                min: 0,
                max: 1000000,
                step: 100,
            },
            {
                key: 'reductionPercent',
                type: 'percent',
                labelKey: 'Reduction with monitoring and alerts',
                hintKey: 'Detecting a failure in minutes instead of hours changes the whole picture.',
                default: 40,
                min: 0,
                max: 100,
                step: 5,
            },
        ],
        formulaKeys: [
            'Monthly cost = downtime hours × operating cost per hour',
            'Recoverable = monthly cost × % reduction',
        ],
        assumptionKeys: [
            'Only hours with a real operational impact are counted.',
            'The reduction percentage assumes monitoring, alerts and fallback plans.',
        ],
    },
];

export const DEFAULT_SCENARIO_ID = 'sales';

export function getScenario(scenarioId) {
    return CALCULATOR_SCENARIOS.find((scenario) => scenario.id === scenarioId) || CALCULATOR_SCENARIOS[0];
}

/**
 * Valores iniciales de un escenario (sirve para el primer render y el reinicio).
 */
export function defaultValuesFor(scenarioId) {
    return getScenario(scenarioId).fields.reduce((carry, field) => {
        carry[field.key] = field.default;
        return carry;
    }, {});
}
