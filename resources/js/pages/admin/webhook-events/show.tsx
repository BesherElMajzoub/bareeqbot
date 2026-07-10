import { Head } from '@inertiajs/react';
import { Badge } from '@/components/ui/badge';
import { useTranslations } from '@/hooks/use-translations';
import webhookEvents from '@/routes/admin/webhook-events';

type EventDetail = {
    id: number;
    platform: string;
    object_type: string | null;
    object_id: string | null;
    status: string;
    signature_valid: boolean;
    received_at: string;
    processed_at: string | null;
    raw_payload: unknown;
};

type Props = {
    event: EventDetail;
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

export default function AdminWebhookEventShow({ event }: Props) {
    const { t } = useTranslations();

    return (
        <>
            <Head title={t('admin.webhook_event_detail')} />
            <div className="mx-auto flex w-full max-w-4xl flex-1 flex-col gap-6 p-4">
                <div className="flex items-center gap-3">
                    <h1 className="text-2xl font-semibold tracking-tight">
                        {t('admin.webhook_event_detail')}
                    </h1>
                    <Badge variant={statusVariant(event.status)}>
                        {t(`webhookevent.${event.status}`)}
                    </Badge>
                </div>

                <div className="grid gap-2 text-sm">
                    <div>
                        <span className="text-muted-foreground">
                            {t('admin.received_at')}:{' '}
                        </span>
                        {new Date(event.received_at).toLocaleString()}
                    </div>
                    <div>
                        <span className="text-muted-foreground">
                            {t('rules.connection')}:{' '}
                        </span>
                        {event.platform}
                    </div>
                    <div>
                        <span className="text-muted-foreground">
                            {t('analytics.object')}:{' '}
                        </span>
                        <span className="font-mono">
                            {event.object_id ?? '—'}
                        </span>
                    </div>
                </div>

                <div>
                    <h2 className="mb-2 text-sm font-medium text-muted-foreground">
                        {t('admin.raw_payload')}
                    </h2>
                    <pre className="overflow-x-auto rounded-xl border border-sidebar-border/70 bg-muted/30 p-4 text-xs dark:border-sidebar-border">
                        {JSON.stringify(event.raw_payload, null, 2)}
                    </pre>
                </div>
            </div>
        </>
    );
}

AdminWebhookEventShow.layout = {
    breadcrumbs: [{ title: 'أحداث الويبهوك', href: webhookEvents.index().url }],
};
