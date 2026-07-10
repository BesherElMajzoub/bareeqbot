import { usePage } from '@inertiajs/react';

/**
 * Access the server-shared translation dictionary for the active locale.
 *
 * Usage: const { t } = useTranslations(); t('dashboard.title')
 */
export function useTranslations() {
    const { translations, locale, direction } = usePage().props;

    const t = (key: string, fallback?: string): string =>
        translations[key] ?? fallback ?? key;

    return { t, locale, direction };
}
