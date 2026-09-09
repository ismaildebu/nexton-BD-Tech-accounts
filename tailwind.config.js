import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],

    theme: {
        extend: {
            colors: {
                blue: {
                    50: 'var(--nexton-50)',
                    100: 'var(--nexton-100)',
                    200: 'var(--nexton-200)',
                    300: 'var(--nexton-300)',
                    400: 'var(--nexton-400)',
                    500: 'var(--nexton-500)',
                    600: 'var(--nexton-600)',
                    700: 'var(--nexton-700)',
                    800: 'var(--nexton-800)',
                    900: 'var(--nexton-900)',
                    950: 'var(--nexton-950)',
                },
                'nexton-teal': {
                    50: 'var(--nexton-50)',
                    100: 'var(--nexton-100)',
                    200: 'var(--nexton-200)',
                    300: 'var(--nexton-300)',
                    400: 'var(--nexton-400)',
                    500: 'var(--nexton-500)',
                    600: 'var(--nexton-600)',
                    700: 'var(--nexton-700)',
                    800: 'var(--nexton-800)',
                    900: 'var(--nexton-900)',
                    950: 'var(--nexton-950)',
                },
            },
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
            },
        },
    },

    plugins: [forms],
};
