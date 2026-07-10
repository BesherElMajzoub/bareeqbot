import { Head, router } from '@inertiajs/react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { useTranslations } from '@/hooks/use-translations';
import subscriptionRequests from '@/routes/admin/subscription-requests';

type Row = {
    id: number;
    status: string;
    created_at: string;
    payer_note: string | null;
    reject_reason: string | null;
    tenant?: { name: string } | null;
    plan_price?: {
        duration_months: number;
        price: string;
        currency: string;
        plan?: { name: string };
    } | null;
};

type Props = {
    requests: { data: Row[] };
};

const statusVariant = (
    status: string,
): 'default' | 'secondary' | 'destructive' => {
    if (status === 'approved') {
        return 'default';
    }

    if (status === 'rejected') {
        return 'destructive';
    }

    return 'secondary';
};

export default function AdminSubscriptionRequests({ requests }: Props) {
    const { t } = useTranslations();

    const approve = (id: number) => {
        router.post(subscriptionRequests.approve(id).url);
    };

    const reject = (id: number) => {
        const reason = window.prompt(t('admin.reject_reason'));

        if (reason) {
            router.post(subscriptionRequests.reject(id).url, { reason });
        }
    };

    return (
        <>
            <Head title={t('admin.subscription_requests')} />
            <div className="mx-auto flex w-full max-w-6xl flex-1 flex-col gap-6 p-4">
                <h1 className="text-2xl font-semibold tracking-tight">
                    {t('admin.subscription_requests')}
                </h1>

                {requests.data.length === 0 ? (
                    <p className="text-sm text-muted-foreground">
                        {t('admin.no_requests')}
                    </p>
                ) : (
                    <div className="overflow-x-auto rounded-xl border border-sidebar-border/70 dark:border-sidebar-border">
                        <table className="w-full text-start text-sm">
                            <thead className="text-muted-foreground">
                                <tr className="border-b border-sidebar-border/70">
                                    <th className="p-3 text-start">
                                        {t('admin.tenant')}
                                    </th>
                                    <th className="p-3 text-start">
                                        {t('billing.plan')}
                                    </th>
                                    <th className="p-3 text-start">
                                        {t('billing.months')}
                                    </th>
                                    <th className="p-3 text-start">
                                        {t('billing.notes')}
                                    </th>
                                    <th className="p-3 text-start">
                                        {t('billing.status')}
                                    </th>
                                    <th className="p-3 text-end"></th>
                                </tr>
                            </thead>
                            <tbody>
                                {requests.data.map((row) => (
                                    <tr
                                        key={row.id}
                                        className="border-b border-sidebar-border/40 last:border-0"
                                    >
                                        <td className="p-3">
                                            {row.tenant?.name ?? '—'}
                                        </td>
                                        <td className="p-3">
                                            {row.plan_price?.plan?.name ?? '—'}
                                        </td>
                                        <td className="p-3">
                                            {row.plan_price?.duration_months ??
                                                '—'}
                                        </td>
                                        <td className="p-3">
                                            <div className="flex flex-col gap-1 text-xs">
                                                {row.payer_note && (
                                                    <div>
                                                        <span className="me-1 font-semibold text-muted-foreground">
                                                            {t(
                                                                'billing.payer_note',
                                                            )}
                                                            :
                                                        </span>
                                                        <span className="text-foreground">
                                                            {row.payer_note}
                                                        </span>
                                                    </div>
                                                )}
                                                {row.status === 'rejected' &&
                                                    row.reject_reason && (
                                                        <div>
                                                            <span className="me-1 font-semibold text-destructive">
                                                                {t(
                                                                    'admin.reject_reason',
                                                                )}
                                                                :
                                                            </span>
                                                            <span className="font-medium text-destructive">
                                                                {
                                                                    row.reject_reason
                                                                }
                                                            </span>
                                                        </div>
                                                    )}
                                                {!row.payer_note &&
                                                    !(
                                                        row.status ===
                                                            'rejected' &&
                                                        row.reject_reason
                                                    ) && (
                                                        <span className="text-muted-foreground">
                                                            —
                                                        </span>
                                                    )}
                                            </div>
                                        </td>
                                        <td className="p-3">
                                            <Badge
                                                variant={statusVariant(
                                                    row.status,
                                                )}
                                            >
                                                {t(`status.${row.status}`)}
                                            </Badge>
                                        </td>
                                        <td className="p-3 text-end">
                                            {row.status === 'pending' && (
                                                <div className="flex justify-end gap-2">
                                                    <Button
                                                        size="sm"
                                                        onClick={() =>
                                                            approve(row.id)
                                                        }
                                                    >
                                                        {t('admin.approve')}
                                                    </Button>
                                                    <Button
                                                        size="sm"
                                                        variant="destructive"
                                                        onClick={() =>
                                                            reject(row.id)
                                                        }
                                                    >
                                                        {t('admin.reject')}
                                                    </Button>
                                                </div>
                                            )}
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

AdminSubscriptionRequests.layout = {
    breadcrumbs: [
        { title: 'طلبات الاشتراك', href: subscriptionRequests.index().url },
    ],
};
