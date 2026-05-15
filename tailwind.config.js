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
            fontFamily: {
                sans: ['Inter', 'Figtree', ...defaultTheme.fontFamily.sans],
                display: ['"Space Grotesk"', 'Inter', ...defaultTheme.fontFamily.sans],
                mono: ['"JetBrains Mono"', ...defaultTheme.fontFamily.mono],
            },
            colors: {
                // Dark surface palette
                ink: {
                    950: '#05060d',
                    900: '#0a0b16',
                    800: '#10121f',
                    700: '#181a2c',
                    600: '#23263d',
                    500: '#2f3253',
                    400: '#4d527a',
                    300: '#7a7fa3',
                    200: '#a4a8c4',
                    100: '#d6d8e8',
                },
                // Brand neon
                neon: {
                    cyan: '#00e5ff',
                    blue: '#3b82ff',
                    violet: '#a855f7',
                    pink: '#ec4899',
                    lime: '#c2ff45',
                    amber: '#ffb020',
                    red: '#ff3b6b',
                },
            },
            boxShadow: {
                'glow-cyan': '0 0 0 1px rgba(0,229,255,0.25), 0 0 24px -4px rgba(0,229,255,0.45)',
                'glow-violet': '0 0 0 1px rgba(168,85,247,0.25), 0 0 24px -4px rgba(168,85,247,0.45)',
                'glow-pink': '0 0 0 1px rgba(236,72,153,0.25), 0 0 24px -4px rgba(236,72,153,0.45)',
                'glow-lime': '0 0 0 1px rgba(194,255,69,0.25), 0 0 24px -4px rgba(194,255,69,0.45)',
                'glow-red': '0 0 0 1px rgba(255,59,107,0.30), 0 0 24px -4px rgba(255,59,107,0.45)',
                'inner-glow': 'inset 0 1px 0 0 rgba(255,255,255,0.08)',
            },
            backgroundImage: {
                'gradient-radial': 'radial-gradient(var(--tw-gradient-stops))',
                'grid-faint': 'linear-gradient(rgba(255,255,255,0.04) 1px, transparent 1px), linear-gradient(90deg, rgba(255,255,255,0.04) 1px, transparent 1px)',
            },
            backgroundSize: {
                'grid-32': '32px 32px',
            },
            animation: {
                'gradient-slow': 'gradient-shift 14s ease infinite',
                'pulse-glow': 'pulse-glow 2.5s ease-in-out infinite',
                'spin-slow': 'spin 18s linear infinite',
            },
            keyframes: {
                'gradient-shift': {
                    '0%, 100%': { backgroundPosition: '0% 50%' },
                    '50%': { backgroundPosition: '100% 50%' },
                },
                'pulse-glow': {
                    '0%, 100%': { opacity: 0.55, transform: 'scale(1)' },
                    '50%': { opacity: 1, transform: 'scale(1.03)' },
                },
            },
        },
    },

    plugins: [forms],
};
