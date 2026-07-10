import { Head, Link } from '@inertiajs/react';
import { Badge } from '@/components/ui/badge';
import { useTranslations } from '@/hooks/use-translations';
import webhookEvents from '@/routes/admin/webhook-events';

type Row = {
    id: number;
    platform: string;
    object_type: string | null;
    object_id: string | null;
    status: string;
    signature_valid: boolean;
    received_at: string;
};

type Props = {
    events: { data: Row[] };
};

const statusVariant = (
    status: string,
): 'default' | 'secondary' | 'destructive' => {
    if (status === 'processed') {
        return 'default';
    }

    if (status === 'failed') {
        return 'destructive';
    }

    return 'secondary';
};

export default function AdminWebhookEvents({ events }: Props) {
    const { t } = useTranslations();

    return (
        <>
            <Head title={t('admin.webhook_events')} />
            <div className="mx-auto flex w-full max-w-6xl flex-1 flex-col gap-6 p-4">
                <h1 className="text-2xl font-semibold tracking-tight">
                    {t('admin.webhook_events')}
                </h1>

                {events.data.length === 0 ? (
                    <p className="text-sm text-muted-foreground">
                        {t('admin.no_events')}
                    </p>
                ) : (
                    <div className="overflow-x-auto rounded-xl border border-sidebar-border/70 dark:border-sidebar-border">
                        <table className="w-full text-start text-sm">
                            <thead className="text-muted-foreground">
                                <tr className="border-b border-sidebar-border/70">
                                    <th className="p-3 text-start">
                                        {t('admin.received_at')}
                                    </th>
                                    <th className="p-3 text-start">
                                        {t('rules.connection')}
                                    </th>
                                    <th className="p-3 text-start">
                                        {t('analytics.object')}
                                    </th>
                                    <th className="p-3 text-start">
                                        {t('billing.status')}
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                {events.data.map((row) => (
                                    <tr
                                        key={row.id}
                                        className="border-b border-sidebar-border/40 last:border-0"
                                    >
                                        <td className="p-3">
                                            <Link
                                                href={
                                                    webhookEvents.show(row.id)
                                                        .url
                                                }
                                                className="text-primary hover:underline"
                                            >
                                                {new Date(
                                                    row.received_at,
                                                ).toLocaleString()}
                                            </Link>
                                        </td>
                                        <td className="p-3">{row.platform}</td>
                                        <td className="p-3 font-mono text-xs">
                                            {row.object_id ?? '—'}
                                        </td>
                                        <td className="p-3">
                                            <Badge
                                                variant={statusVariant(
                                                    row.status,
                                                )}
                                            >
                                                {t(
                                                    `webhookevent.${row.status}`,
                                                )}
                                            </Badge>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                )}
            </div>
        </>
    );
}

AdminWebhookEvents.layout = {
    breadcrumbs: [{ title: 'أحداث الويبهوك', href: webhookEvents.index().url }],
};
