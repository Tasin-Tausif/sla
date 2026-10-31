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
                sans: ['var(--font-sans)'],
            },
            colors: {
                bg: 'rgb(var(--color-bg) / <alpha-value>)',
                surface: 'rgb(var(--color-surface) / <alpha-value>)',
                'surface-muted': 'rgb(var(--color-surface-muted) / <alpha-value>)',
                line: 'rgb(var(--color-border) / <alpha-value>)',
                content: 'rgb(var(--color-text) / <alpha-value>)',
                'content-muted': 'rgb(var(--color-text-muted) / <alpha-value>)',
                primary: 'rgb(var(--color-primary) / <alpha-value>)',
                'primary-hover': 'rgb(var(--color-primary-hover) / <alpha-value>)',
                'primary-soft': 'rgb(var(--color-primary-soft) / <alpha-value>)',
                'on-primary': 'rgb(var(--color-on-primary) / <alpha-value>)',
                secondary: 'rgb(var(--color-secondary) / <alpha-value>)',
                danger: 'rgb(var(--color-danger) / <alpha-value>)',
                'danger-soft': 'rgb(var(--color-danger-soft) / <alpha-value>)',
                success: 'rgb(var(--color-success) / <alpha-value>)',
                'success-soft': 'rgb(var(--color-success-soft) / <alpha-value>)',
                warning: 'rgb(var(--color-warning) / <alpha-value>)',
                'warning-soft': 'rgb(var(--color-warning-soft) / <alpha-value>)',
                info: 'rgb(var(--color-info) / <alpha-value>)',
                'info-soft': 'rgb(var(--color-info-soft) / <alpha-value>)',
                'focus-ring': 'rgb(var(--color-focus-ring) / <alpha-value>)',
                open: 'rgb(var(--status-open) / <alpha-value>)',
                'open-soft': 'rgb(var(--status-open-soft) / <alpha-value>)',
                'in-progress': 'rgb(var(--status-in-progress) / <alpha-value>)',
                'in-progress-soft': 'rgb(var(--status-in-progress-soft) / <alpha-value>)',
                waiting: 'rgb(var(--status-waiting) / <alpha-value>)',
                'waiting-soft': 'rgb(var(--status-waiting-soft) / <alpha-value>)',
                resolved: 'rgb(var(--status-resolved) / <alpha-value>)',
                'resolved-soft': 'rgb(var(--status-resolved-soft) / <alpha-value>)',
                closed: 'rgb(var(--status-closed) / <alpha-value>)',
                'closed-soft': 'rgb(var(--status-closed-soft) / <alpha-value>)',
                low: 'rgb(var(--priority-low) / <alpha-value>)',
                'low-soft': 'rgb(var(--priority-low-soft) / <alpha-value>)',
                medium: 'rgb(var(--priority-medium) / <alpha-value>)',
                'medium-soft': 'rgb(var(--priority-medium-soft) / <alpha-value>)',
                high: 'rgb(var(--priority-high) / <alpha-value>)',
                'high-soft': 'rgb(var(--priority-high-soft) / <alpha-value>)',
                critical: 'rgb(var(--priority-critical) / <alpha-value>)',
                'critical-soft': 'rgb(var(--priority-critical-soft) / <alpha-value>)',
                within: 'rgb(var(--sla-within) / <alpha-value>)',
                'within-soft': 'rgb(var(--sla-within-soft) / <alpha-value>)',
                approaching: 'rgb(var(--sla-approaching) / <alpha-value>)',
                'approaching-soft': 'rgb(var(--sla-approaching-soft) / <alpha-value>)',
                breached: 'rgb(var(--sla-breached) / <alpha-value>)',
                'breached-soft': 'rgb(var(--sla-breached-soft) / <alpha-value>)',
                'sla-resolved': 'rgb(var(--sla-resolved) / <alpha-value>)',
                'sla-resolved-soft': 'rgb(var(--sla-resolved-soft) / <alpha-value>)',
            },
            borderRadius: {
                sm: 'var(--radius-sm)',
                DEFAULT: 'var(--radius-md)',
                lg: 'var(--radius-lg)',
            },
            boxShadow: {
                card: 'var(--shadow-card)',
                pop: 'var(--shadow-pop)',
            },
            backgroundImage: {
                brand: 'var(--gradient-brand)',
            },
            maxWidth: {
                shell: 'var(--container-max)',
            },
        },
    },

    plugins: [forms],
};
