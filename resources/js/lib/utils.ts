import type { InertiaLinkProps } from '@inertiajs/react';
import { clsx } from 'clsx';
import type { ClassValue } from 'clsx';
import { twMerge } from 'tailwind-merge';

export function cn(...inputs: ClassValue[]) {
    return twMerge(clsx(inputs));
}

export function toUrl(url: NonNullable<InertiaLinkProps['href']>): string {
    return typeof url === 'string' ? url : url.url;
}

const DEFAULT_LOCALE =
    typeof navigator !== 'undefined' && navigator.language
        ? navigator.language
        : 'en-US';

function parseDate(value?: string | null): Date | null {
    if (!value) {
        return null;
    }

    const trimmed = value.trim();

    if (/^\d{4}-\d{2}-\d{2}$/.test(trimmed)) {
        return new Date(`${trimmed}T00:00:00`);
    }

    const parsed = new Date(trimmed);

    return Number.isNaN(parsed.getTime()) ? null : parsed;
}

export function formatDate(value?: string | null): string {
    const date = parseDate(value);

    if (!date) {
        return '—';
    }

    return new Intl.DateTimeFormat(DEFAULT_LOCALE, {
        dateStyle: 'medium',
    }).format(date);
}

export function formatDateTime(value?: string | null): string {
    const date = parseDate(value);

    if (!date) {
        return '—';
    }

    return new Intl.DateTimeFormat(DEFAULT_LOCALE, {
        dateStyle: 'medium',
        timeStyle: 'short',
    }).format(date);
}

export function formatDateRange(start?: string | null, end?: string | null): string {
    if (!start && !end) {
        return '—';
    }

    if (!start) {
        return `— – ${formatDate(end)}`;
    }

    if (!end) {
        return `${formatDate(start)} – —`;
    }

    return `${formatDate(start)} – ${formatDate(end)}`;
}

export function formatRelative(value?: string | null): string {
    const date = parseDate(value);

    if (!date) {
        return '—';
    }

    const now = Date.now();
    const diffSeconds = Math.round((date.getTime() - now) / 1000);
    const absSeconds = Math.abs(diffSeconds);

    const ranges: Array<[Intl.RelativeTimeFormatUnit, number]> = [
        ['year', 60 * 60 * 24 * 365],
        ['month', 60 * 60 * 24 * 30],
        ['week', 60 * 60 * 24 * 7],
        ['day', 60 * 60 * 24],
        ['hour', 60 * 60],
        ['minute', 60],
        ['second', 1],
    ];

    const [unit, secondsInUnit] = ranges.find(([, seconds]) => absSeconds >= seconds) ?? [
        'second',
        1,
    ];

    const valueInUnit = Math.round(diffSeconds / secondsInUnit);

    return new Intl.RelativeTimeFormat(DEFAULT_LOCALE, {
        numeric: 'auto',
    }).format(valueInUnit, unit);
}
