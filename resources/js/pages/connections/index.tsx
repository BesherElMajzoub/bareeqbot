import { Head, router } from '@inertiajs/react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { useTranslations } from '@/hooks/use-translations';
import connections from '@/routes/connections';

type ChannelConnection = {
    id: number;
    platform: 'facebook' | 'instagram';
    provider_account_id: string;
    name: string;
    username: string | null;
    webhook_subscribed: boolean;
    status: 'active' | 'revoked' | 'error';
    created_at: string;
};

type Props = {
    connections: ChannelConnection[];
    quota: { used: number; max: number };
};

const platformLabel = (
    platform: 'facebook' | 'instagram',
    t: (k: string) => string,
) =>
    platform === 'facebook'
        ? t('connections.platform_facebook')
        : t('connections.platform_instagram');

const statusVariant = (
    status: string,
): 'default' | 'secondary' | 'destructive' => {
    if (status === 'active') {
        return 'default';
    }

    if (status === 'error') {
        return 'destructive';
    }

    return 'secondary';
};

const statusLabel = (status: string, t: (k: string) => string) =>
    t(`connections.status_${status}`) ?? status;

export default function ConnectionsIndex({ connections: items, quota }: Props) {
    const { t } = useTranslations();

    const disconnect = (id: number) => {
        if (!confirm(t('connections.disconnect') + '?')) {
            return;
        }

        router.delete(connections.destroy({ channelConnection: id }).url, {
            preserveScroll: true,
        });
    };

    const quotaPercent =
        quota.max > 0 ? Math.round((quota.used / quota.max) * 100) : 0;

    return (
        <>
            <Head title={t('connections.title')} />
            <div className="mx-auto flex w-full max-w-4xl flex-1 flex-col gap-6 p-4">
                <div className="flex items-center justify-between">
                    <h1 className="text-2xl font-semibold tracking-tight">
                        {t('connections.title')}
                    </h1>
                    <Button asChild>
                        <a href={connections.facebook.redirect().url}>
                            {t('connections.connect_facebook')}
                        </a>
                    </Button>
                </div>

                {/* Quota bar */}
                <Card>
                    <CardContent className="pt-4">
                        <div className="mb-1 flex items-center justify-between text-sm">
                            <span className="text-muted-foreground">
                                {t('connections.quota')
                                    .replace(':used', String(quota.used))
                                    .replace(':max', String(quota.max))}
                            </span>
                            <span className="font-medium">{quotaPercent}%</span>
                        </div>
                        <div className="h-2 overflow-hidden rounded-full bg-muted">
                            <div
                                className="h-full rounded-full bg-primary transition-all"
                                style={{
                                    width: `${Math.min(quotaPercent, 100)}%`,
                                }}
                            />
                        </div>
                    </CardContent>
                </Card>

                {/* Connections list */}
                {items.length === 0 ? (
                    <p className="text-sm text-muted-foreground">
                        {t('connections.no_connections')}
                    </p>
                ) : (
                    <div className="grid gap-3 sm:grid-cols-2">
                        {items.map((conn) => (
                            <Card key={conn.id}>
                                <CardHeader className="pb-2">
                                    <CardTitle className="flex items-start justify-between gap-2 text-base">
                                        <span className="truncate">
                                            {conn.name}
                                        </span>
                                        <Badge
                                            variant={statusVariant(conn.status)}
                                            className="shrink-0"
                                        >
                                            {statusLabel(conn.status, t)}
                                        </Badge>
                                    </CardTitle>
                                </CardHeader>
                                <CardContent className="flex items-center justify-between gap-2 text-sm">
                                    <div className="flex flex-col gap-1 text-muted-foreground">
                                        <span>
                                            {platformLabel(conn.platform, t)}
                                        </span>
                                        {conn.username && (
                                            <span>@{conn.username}</span>
                                        )}
                                        <span
                                            className={
                                                conn.webhook_subscribed
                                                    ? 'text-green-600'
                                                    : 'text-destructive'
                                            }
                                        >
                                            {conn.webhook_subscribed
                                                ? t(
                                                      'connections.webhook_subscribed',
                                                  )
                                                : t(
                                                      'connections.webhook_not_subscribed',
                                                  )}
                                        </span>
                                    </div>
                                    {conn.status !== 'revoked' && (
                                        <Button
                                            variant="outline"
                                            size="sm"
                                            onClick={() => disconnect(conn.id)}
                                            className="shrink-0"
                                        >
                                            {t('connections.disconnect')}
                                        </Button>
                                    )}
                                </CardContent>
                            </Card>
                        ))}
                    </div>
                )}
            </div>
        </>
    );
}

ConnectionsIndex.layout = {
    breadcrumbs: [{ title: 'القنوات المربوطة', href: connections.index().url }],
};
