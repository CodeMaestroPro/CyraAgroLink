import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    darkMode: 'class',
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.js',
    ],

    theme: {
        extend: {
            colors: {
                cyra: {
                    // RGB channels via CSS variables — supports light/dark themes.
                    forest: 'rgb(var(--cyra-forest) / <alpha-value>)',
                    green: 'rgb(var(--cyra-green) / <alpha-value>)',
                    leaf: 'rgb(var(--cyra-leaf) / <alpha-value>)',
                    mint: 'rgb(var(--cyra-mint) / <alpha-value>)',
                    soft: 'rgb(var(--cyra-soft) / <alpha-value>)',
                    sun: 'rgb(var(--cyra-sun) / <alpha-value>)',
                    amber: 'rgb(var(--cyra-amber) / <alpha-value>)',
                    soil: 'rgb(var(--cyra-soil) / <alpha-value>)',
                    ink: 'rgb(var(--cyra-ink) / <alpha-value>)',
                    muted: 'rgb(var(--cyra-muted) / <alpha-value>)',
                    cream: 'rgb(var(--cyra-cream) / <alpha-value>)',
                    line: 'rgb(var(--cyra-line) / <alpha-value>)',
                    surface: 'rgb(var(--cyra-surface) / <alpha-value>)',
                    panel: 'rgb(var(--cyra-panel) / <alpha-value>)',
                    card: 'rgb(var(--cyra-card) / <alpha-value>)',
                },
            },
            fontFamily: {
                sans: ['Plus Jakarta Sans', ...defaultTheme.fontFamily.sans],
                display: ['Outfit', 'Plus Jakarta Sans', ...defaultTheme.fontFamily.sans],
            },
            boxShadow: {
                stats: '0 18px 50px rgba(16, 133, 63, 0.12)',
                soft: '0 10px 30px rgba(16, 133, 63, 0.10)',
            },
            maxWidth: {
                '8xl': '88rem',
            },
        },
    },

    plugins: [forms],
};
