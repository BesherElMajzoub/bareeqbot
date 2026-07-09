import { Head, router } from '@inertiajs/react';
import { useState } from 'react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { useTranslations } from '@/hooks/use-translations';
import billing from '@/routes/billing';

type PlanPrice = { id: number; duration_months: number; price: string; currency: string };
type Plan = { id: number; name: string; slug: string; max_pages: number; prices: PlanPrice[] };
type ActiveSubscription = { id: number; ends_at: string; plan: { name: string; max_pages: number } } | null;
type SubscriptionRequest = {
    id: number;
    status: string;
    created_at: string;
    plan_price?: { duration_months: number; plan?: { name: string } };
};

type Props = {
    plans: Plan[];
    activeSubscription: ActiveSubscription;
    requests: SubscriptionRequest[];
};

const statusVariant = (status: string): 'default' | 'secondary' | 'destructive' => {
    if (status === 'approved' || status === 'active') return 'default';
    if (status === 'rejected' || status === 'expired' || status === 'cancelled') return 'destructive';
    return 'secondary';
};

export default function BillingIndex({ plans, activeSubscription, requests }: Props) {
    const { t } = useTranslations();
    const [payerNote, setPayerNote] = useState('');

    const subscribe = (planPriceId: number) => {
        router.post(billing.requests.store().url, { plan_price_id: planPriceId, payer_note: payerNote });
    };

    return (
        <>
            <Head title={t('billing.title')} />
            <div className="mx-auto flex w-full max-w-5xl flex-1 flex-col gap-6 p-4">
                <h1 className="text-2xl font-semibold tracking-tight">{t('billing.title')}</h1>

                <Card>
                    <CardHeader>
                        <CardTitle>{t('billing.current_subscription')}</CardTitle>
                    </CardHeader>
                    <CardContent>
                        {activeSubscription ? (
                            <div className="flex flex-wrap items-center gap-x-8 gap-y-2 text-sm">
                                <span>
                                    {t('billing.plan')}: <strong>{activeSubscription.plan.name}</strong>
                                </span>
                                <span>
                                    {t('billing.max_pages')}: <strong>{activeSubscription.plan.max_pages}</strong>
                                </span>
                                <span>
                                    {t('billing.ends_at')}:{' '}
                                    <strong>{new Date(activeSubscription.ends_at).toLocaleDateString()}</strong>
                                </span>
                            </div>
                        ) : (
                            <p className="text-sm text-muted-foreground">{t('billing.no_subscription')}</p>
                        )}
                    </CardContent>
                </Card>

                <div>
                    <h2 className="mb-3 text-lg font-medium">{t('billing.choose_plan')}</h2>
                    <div className="mb-4 max-w-md">
                        <Input
                            value={payerNote}
                            onChange={(e) => setPayerNote(e.target.value)}
                            placeholder={t('billing.payer_note')}
                        />
                    </div>
                    <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                        {plans.map((plan) => (
                            <Card key={plan.id}>
                                <CardHeader>
                                    <CardTitle className="flex items-center justify-between">
                                        <span>{plan.name}</span>
                                        <Badge variant="secondary">
                                            {plan.max_pages} {t('billing.max_pages')}
                                        </Badge>
                                    </CardTitle>
                                </CardHeader>
                                <CardContent className="flex flex-col gap-2">
                                    {plan.prices.map((price) => (
                                        <Button
                                            key={price.id}
                                            variant="outline"
                                            className="justify-between"
                                            onClick={() => subscribe(price.id)}
                                        >
                                            <span>
                                                {price.duration_months} {t('billing.months')}
                                            </span>
                                            <span className="tabular-nums">
                                                {price.price} {price.currency}
                                            </span>
                                        </Button>
                                    ))}
                                </CardContent>
                            </Card>
                        ))}
                    </div>
                </div>

                <div>
                    <h2 className="mb-3 text-lg font-medium">{t('billing.your_requests')}</h2>
                    {requests.length === 0 ? (
                        <p className="text-sm text-muted-foreground">{t('admin.no_requests')}</p>
                    ) : (
                        <div className="overflow-x-auto rounded-xl border border-sidebar-border/70 dark:border-sidebar-border">
                            <table className="w-full text-start text-sm">
                                <thead className="text-muted-foreground">
                                    <tr className="border-b border-sidebar-border/70">
                                        <th className="p-3 text-start">{t('billing.plan')}</th>
                                        <th className="p-3 text-start">{t('billing.months')}</th>
                                        <th className="p-3 text-start">{t('billing.status')}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {requests.map((req) => (
                                        <tr key={req.id} className="border-b border-sidebar-border/40 last:border-0">
                                            <td className="p-3">{req.plan_price?.plan?.name ?? '—'}</td>
                                            <td className="p-3">{req.plan_price?.duration_months ?? '—'}</td>
                                            <td className="p-3">
                                                <Badge variant={statusVariant(req.status)}>{t(`status.${req.status}`)}</Badge>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    )}
                </div>
            </div>
        </>
    );
}

BillingIndex.layout = {
    breadcrumbs: [{ title: 'الاشتراك والفوترة', href: billing.index().url }],
};
