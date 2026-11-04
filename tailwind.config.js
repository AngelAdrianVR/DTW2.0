import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';
import typography from '@tailwindcss/typography';

/** @type {import('tailwindcss').Config} */
export default {
    darkMode: 'class', // Habilita el modo oscuro basado en una clase en el HTML

    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './vendor/laravel/jetstream/**/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.vue',
    ],

    theme: {
        extend: {
            fontFamily: {
                // Se conserva Quicksand como fuente base para no alterar el back-office
                sans: ['Quicksand', ...defaultTheme.fontFamily.sans],
                // Tipografías de la nueva landing
                display: ['Manrope', 'Quicksand', ...defaultTheme.fontFamily.sans],
                inter: ['Inter', 'Quicksand', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                // Paleta de identidad DTW (tomada del sitio en producción).
                // Solo se declaran tokens con prefijo `dtw` para no alterar los
                // colores por defecto de Tailwind que usa el back-office.
                dtw: {
                    void: '#050510',
                    night: '#0A0A14',
                    surface: '#0D0D1A',
                    'surface-soft': '#12121F',
                    primary: '#A855F7',
                    'primary-soft': '#C084FC',
                    'primary-deep': '#7C3AED',
                    brand: '#6215C0',
                    cyan: '#17EDF4',
                    blue: '#3B82F6',
                    amber: '#EAB308',
                    ink: '#E2E8F0',
                    muted: '#94A3B8',
                    dim: '#64748B',
                    dark: '#0A0A14',
                    light: '#F7F7FA',
                    success: '#16A36A',
                    danger: '#DC4545',
                },
            },
            boxShadow: {
                'dtw-soft': '0 24px 60px -30px rgba(5, 5, 16, 0.65)',
                'dtw-card': '0 30px 80px -40px rgba(5, 5, 16, 0.9), inset 0 1px 0 rgba(255, 255, 255, 0.05)',
                'dtw-glow': '0 0 90px -30px rgba(168, 85, 247, 0.65)',
                'dtw-cyan': '0 0 90px -35px rgba(23, 237, 244, 0.55)',
            },
            backgroundImage: {
                'dtw-grid':
                    'linear-gradient(to right, rgba(255, 255, 255, 0.05) 1px, transparent 1px), linear-gradient(to bottom, rgba(255, 255, 255, 0.05) 1px, transparent 1px)',
                'dtw-radial':
                    'radial-gradient(60% 60% at 50% 0%, rgba(168, 85, 247, 0.32) 0%, rgba(5, 5, 16, 0) 70%)',
                'dtw-halo':
                    'radial-gradient(45% 45% at 50% 50%, rgba(98, 21, 192, 0.45) 0%, rgba(5, 5, 16, 0) 70%)',
            },
            backgroundSize: {
                'dtw-grid': '56px 56px',
            },
            keyframes: {
                'fade-up': {
                    '0%': { opacity: '0', transform: 'translateY(18px)' },
                    '100%': { opacity: '1', transform: 'translateY(0)' },
                },
                'fade-in': {
                    '0%': { opacity: '0' },
                    '100%': { opacity: '1' },
                },
                float: {
                    '0%, 100%': { transform: 'translateY(0)' },
                    '50%': { transform: 'translateY(-8px)' },
                },
                'grow-bar': {
                    '0%': { transform: 'scaleY(0.05)' },
                    '100%': { transform: 'scaleY(1)' },
                },
                'pulse-ring': {
                    '0%': { transform: 'scale(0.9)', opacity: '0.7' },
                    '70%': { transform: 'scale(1.4)', opacity: '0' },
                    '100%': { transform: 'scale(1.4)', opacity: '0' },
                },
                drift: {
                    '0%, 100%': { transform: 'translate3d(0, 0, 0) scale(1)' },
                    '50%': { transform: 'translate3d(0, -26px, 0) scale(1.05)' },
                },
                'scroll-hint': {
                    '0%': { transform: 'translateY(0)', opacity: '0' },
                    '40%': { opacity: '1' },
                    '100%': { transform: 'translateY(14px)', opacity: '0' },
                },
            },
            animation: {
                'fade-up': 'fade-up 0.6s cubic-bezier(0.22, 1, 0.36, 1) both',
                'fade-in': 'fade-in 0.5s ease-out both',
                float: 'float 6s ease-in-out infinite',
                'grow-bar': 'grow-bar 0.9s cubic-bezier(0.22, 1, 0.36, 1) both',
                'pulse-ring': 'pulse-ring 2.4s ease-out infinite',
                drift: 'drift 16s ease-in-out infinite',
                'scroll-hint': 'scroll-hint 2.2s ease-in-out infinite',
            },
        },
    },

    plugins: [forms, typography],
};
