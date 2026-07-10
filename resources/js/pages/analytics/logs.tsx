import { Head } from '@inertiajs/react';
import { Badge } from '@/components/ui/badge';
import { useTranslations } from '@/hooks/use-translations';
import analytics from '@/routes/analytics';

type LogRow = {
    id: number;
    surface: string;
    action_type: string;
    status: string;
    source_object_id: string;
    created_at: string;
    rule?: { name: string } | null;
    channel_connection?: { name: string; platform: string } | null;
};

type Props = {
    logs: { data: LogRow[] };
};

const statusVariant = (
    status: string,
): 'default' | 'secondary' | 'destructive' => {
    if (status === 'sent') {
        return 'default';
    }

    if (status === 'failed') {
        return 'destructive';
    }

    return 'secondary';
};

export default function AnalyticsLogs({ logs }: Props) {
    const { t } = useTranslations();

    return (
        <>
            <Head title={t('analytics.logs_title')} />
            <div className="mx-auto flex w-full max-w-6xl flex-1 flex-col gap-6 p-4">
                <h1 className="text-2xl font-semibold tracking-tight">
                    {t('analytics.logs_title')}
                </h1>

                <div className="overflow-x-auto rounded-xl border border-sidebar-border/70 dark:border-sidebar-border">
                    <table className="w-full text-start text-sm">
                        <thead className="text-muted-foreground">
                            <tr className="border-b border-sidebar-border/70">
                                <th className="p-3 text-start">
                                    {t('rules.connection')}
                                </th>
                                <th className="p-3 text-start">
                                    {t('analytics.action')}
                                </th>
                                <th className="p-3 text-start">
                                    {t('billing.status')}
                                </th>
                                <th className="p-3 text-start">
                                    {t('analytics.object')}
                                </th>
                                <th className="p-3 text-start">
                                    {t('analytics.rule')}
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            {logs.data.length === 0 ? (
                                <tr>
                                    <td
                                        colSpan={5}
                                        className="p-4 text-muted-foreground"
                                    >
                                        {t('dashboard.no_data')}
                                    </td>
                                </tr>
                            ) : (
                                logs.data.map((row) => (
                                    <tr
                                        key={row.id}
                                        className="border-b border-sidebar-border/40 last:border-0"
                                    >
                                        <td className="p-3">
                                            {row.channel_connection?.name ??
                                                '—'}
                                        </td>
                                        <td className="p-3">
                                            {t(`action.${row.action_type}`)}
                                        </td>
                                        <td className="p-3">
                                            <Badge
                                                variant={statusVariant(
                                                    row.status,
                                                )}
                                            >
                                                {t(`replylog.${row.status}`)}
                                            </Badge>
                                        </td>
                                        <td className="p-3 font-mono text-xs">
                                            {row.source_object_id}
                                        </td>
                                        <td className="p-3">
                                            {row.rule?.name ?? '—'}
                                        </td>
                                    </tr>
                                ))
                            )}
                        </tbody>
                    </table>
                </div>
            </div>
        </>
    );
}

AnalyticsLogs.layout = {
    breadcrumbs: [{ title: 'سجل الردود', href: analytics.logs().url }],
};
