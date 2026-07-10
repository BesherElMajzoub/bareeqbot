import { Head, router } from '@inertiajs/react';
import React, { useState } from 'react';
import { Check, X } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { useTranslations } from '@/hooks/use-translations';
import billing from '@/routes/billing';

type PlatformScope = 'facebook' | 'facebook_instagram';

type PlanPrice = {
    id: number;
    duration_months: number;
    platform_scope: PlatformScope;
    price: string;
    currency: string;
};

type Plan = {
    id: number;
    name: string;
    slug: string;
    max_pages: number;
    prices: PlanPrice[];
};

type ActiveSubscription = {
    id: number;
    ends_at: string;
    plan: { name: string; max_pages: number };
} | null;

type SubscriptionRequest = {
    id: number;
    status: string;
    created_at: string;
    payer_note?: string | null;
    reject_reason?: string | null;
    plan_price?: { duration_months: number; plan?: { name: string } };
};

type Props = {
    plans: Plan[];
    activeSubscription: ActiveSubscription;
    requests: SubscriptionRequest[];
};

const statusVariant = (
    status: string,
): 'default' | 'secondary' | 'destructive' => {
    if (status === 'approved' || status === 'active') {
        return 'default';
    }

    if (
        status === 'rejected' ||
        status === 'expired' ||
        status === 'cancelled'
    ) {
        return 'destructive';
    }

    return 'secondary';
};

const DURATIONS = [1, 3, 6, 12];
const PLATFORM_SCOPES: PlatformScope[] = ['facebook', 'facebook_instagram'];

export default function BillingIndex({
    plans,
    activeSubscription,
    requests,
}: Props) {
    const { t, locale } = useTranslations();
    const [payerNote, setPayerNote] = useState('');
    const [confirmingPrice, setConfirmingPrice] = useState<{
        priceId: number;
        planName: string;
        duration: number;
        scope: PlatformScope;
        priceVal: string;
        currency: string;
    } | null>(null);

    const hasPendingRequest = requests.some((r) => r.status === 'pending');
    const canSubscribe = !activeSubscription && !hasPendingRequest;

    const handlePriceClick = (plan: Plan, price: PlanPrice) => {
        setConfirmingPrice({
            priceId: price.id,
            planName: plan.name,
            duration: price.duration_months,
            scope: price.platform_scope,
            priceVal: price.price,
            currency: price.currency,
        });
    };

    const subscribe = () => {
        if (!confirmingPrice) return;
        router.post(
            billing.requests.store().url,
            {
                plan_price_id: confirmingPrice.priceId,
                payer_note: payerNote,
            },
            {
                onSuccess: () => {
                    setConfirmingPrice(null);
                    setPayerNote('');
                },
            },
        );
    };

    const cancelSubscription = () => {
        if (!confirm(t('billing.cancel_confirm'))) {
            return;
        }

        router.post(billing.subscription.cancel().url);
    };

    const formatChannels = (count: number, lang: string) => {
        if (lang === 'ar') {
            if (count === 1) return 'قناة واحدة';
            if (count <= 10) return `${count} قنوات`;
            return `${count} قناة`;
        }
        return `${count} ${count === 1 ? 'channel' : 'channels'}`;
    };

    const getFeatureValue = (featureKey: string, plan: Plan) => {
        const slug = plan.slug;
        switch (featureKey) {
            case 'allowed_pages':
                return formatChannels(plan.max_pages, locale);
            case 'comments_reply':
                return true;
            case 'private_dms':
                return slug !== 'starter';
            case 'stories_mentions':
                return slug !== 'starter';
            case 'keyword_engine':
                return slug === 'starter'
                    ? t('billing.feature_value_basic')
                    : t('billing.feature_value_advanced');
            case 'analytics':
                if (slug === 'starter') return false;
                if (slug === 'growth') return t('billing.feature_value_medium');
                if (slug === 'business')
                    return t('billing.feature_value_detailed');
                return t('billing.feature_value_custom');
            case 'support':
                if (slug === 'starter') return t('billing.feature_value_email');
                if (slug === 'growth') return t('billing.feature_value_fast');
                if (slug === 'business')
                    return t('billing.feature_value_premium');
                return t('billing.feature_value_dedicated');
            default:
                return false;
        }
    };

    const featuresList = [
        { key: 'allowed_pages', label: t('billing.feature_allowed_pages') },
        { key: 'comments_reply', label: t('billing.feature_comments_reply') },
        { key: 'private_dms', label: t('billing.feature_private_dms') },
        {
            key: 'stories_mentions',
            label: t('billing.feature_stories_mentions'),
        },
        { key: 'keyword_engine', label: t('billing.feature_keyword_engine') },
        { key: 'analytics', label: t('billing.feature_analytics') },
        { key: 'support', label: t('billing.feature_support') },
    ];

    const getPriceForPlan = (
        plan: Plan,
        duration: number,
        scope: PlatformScope,
    ) => {
        return plan.prices.find(
            (p) => p.duration_months === duration && p.platform_scope === scope,
        );
    };

    const formatPrice = (
        price: string | number,
        currency: string,
        lang: string,
    ) => {
        const formattedNum = Number(price).toLocaleString();
        if (currency === 'SYP') {
            return lang === 'ar'
                ? `${formattedNum} ل.س`
                : `${formattedNum} SYP`;
        }
        if (currency === 'USD') {
            return `$${formattedNum}`;
        }
        return `${formattedNum} ${currency}`;
    };

    const renderFeatureIcon = (value: boolean | string) => {
        if (typeof value === 'boolean') {
            return value ? (
                <div className="flex justify-center">
                    <div className="flex size-5 items-center justify-center rounded-full bg-amber-500 text-white dark:bg-amber-600">
                        <Check className="size-3 stroke-[3]" />
                    </div>
                </div>
            ) : (
                <div className="flex justify-center">
                    <div className="flex size-5 items-center justify-center rounded-full bg-muted text-muted-foreground">
                        <X className="size-3 stroke-[3]" />
                    </div>
                </div>
            );
        }
        return <span className="text-sm font-medium">{value}</span>;
    };

    const getConfirmationMessage = () => {
        if (!confirmingPrice) return '';

        const planStr = confirmingPrice.planName;
        const durationStr = confirmingPrice.duration.toString();
        const unitStr =
            locale === 'ar'
                ? confirmingPrice.duration === 1
                    ? 'شهر'
                    : 'أشهر'
                : confirmingPrice.duration === 1
                  ? 'month'
                  : 'months';
        const platformStr = t(`billing.platform_${confirmingPrice.scope}`);
        const priceStr = formatPrice(
            confirmingPrice.priceVal,
            confirmingPrice.currency,
            locale,
        );

        return t('billing.confirm_description')
            .replace(':plan', planStr)
            .replace(':duration', durationStr)
            .replace(':unit', unitStr)
            .replace(':platform', platformStr)
            .replace(':price', priceStr);
    };

    return (
        <>
            <Head title={t('billing.title')} />
            <div className="mx-auto flex w-full max-w-5xl flex-1 flex-col gap-6 p-4">
                <h1 className="font-sans text-2xl font-semibold tracking-tight">
                    {t('billing.title')}
                </h1>

                {/* Current Subscription Status */}
                <Card>
                    <CardHeader>
                        <CardTitle>
                            {t('billing.current_subscription')}
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        {activeSubscription ? (
                            <div className="flex flex-wrap items-center justify-between gap-x-8 gap-y-3">
                                <div className="flex flex-wrap items-center gap-x-8 gap-y-2 text-sm">
                                    <span>
                                        {t('billing.plan')}:{' '}
                                        <strong>
                                            {activeSubscription.plan.name}
                                        </strong>
                                    </span>
                                    <span>
                                        {t('billing.max_pages')}:{' '}
                                        <strong>
                                            {activeSubscription.plan.max_pages}
                                        </strong>
                                    </span>
                                    <span>
                                        {t('billing.ends_at')}:{' '}
                                        <strong>
                                            {new Date(
                                                activeSubscription.ends_at,
                                            ).toLocaleDateString()}
                                        </strong>
                                    </span>
                                </div>
                                <Button
                                    size="sm"
                                    variant="destructive"
                                    onClick={cancelSubscription}
                                >
                                    {t('billing.cancel_subscription')}
                                </Button>
                            </div>
                        ) : (
                            <p className="text-sm text-muted-foreground">
                                {t('billing.no_subscription')}
                            </p>
                        )}
                    </CardContent>
                </Card>

                {/* Choose Plan Section */}
                <div className="space-y-6">
                    <div className="flex flex-col gap-1">
                        <h2 className="text-lg font-medium">
                            {t('billing.choose_plan')}
                        </h2>
                        {!canSubscribe && (
                            <p className="rounded-lg border border-sidebar-border/70 bg-muted/40 p-3 text-sm text-muted-foreground dark:border-sidebar-border">
                                {activeSubscription
                                    ? t('billing.active_subscription_notice')
                                    : t('billing.pending_request_notice')}
                            </p>
                        )}
                    </div>

                    {plans.length > 0 && (
                        <div className="space-y-8">
                            {/* 1. Features Comparison Table */}
                            <div className="flex flex-col gap-3">
                                <h3 className="text-center font-bold text-sky-800 dark:text-sky-400">
                                    {t('billing.features_title_comparison')}
                                </h3>
                                <div className="overflow-x-auto rounded-xl border border-sky-500/30 bg-card shadow-sm dark:border-sky-500/20">
                                    <table className="w-full border-collapse text-start text-sm">
                                        <thead>
                                            <tr className="border-b border-sky-500/30 bg-sky-50/50 dark:border-sky-500/20 dark:bg-sky-950/20">
                                                <th className="w-[220px] p-3 ps-4 text-start font-semibold text-sky-900 dark:text-sky-100">
                                                    {t(
                                                        'billing.features_column',
                                                    )}
                                                </th>
                                                {plans.map((plan) => (
                                                    <th
                                                        key={plan.id}
                                                        className="p-3 text-center font-bold text-sky-900 dark:text-sky-100"
                                                    >
                                                        {plan.name}
                                                    </th>
                                                ))}
                                            </tr>
                                        </thead>
                                        <tbody className="divide-y divide-sky-500/10">
                                            {featuresList.map((feature) => (
                                                <tr
                                                    key={feature.key}
                                                    className="hover:bg-sky-50/20 dark:hover:bg-sky-950/5"
                                                >
                                                    <td className="p-3 ps-4 text-start font-medium text-muted-foreground">
                                                        {feature.label}
                                                    </td>
                                                    {plans.map((plan) => (
                                                        <td
                                                            key={plan.id}
                                                            className="p-3 text-center"
                                                        >
                                                            {renderFeatureIcon(
                                                                getFeatureValue(
                                                                    feature.key,
                                                                    plan,
                                                                ),
                                                            )}
                                                        </td>
                                                    ))}
                                                </tr>
                                            ))}
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            {/* 2. Prices Comparison Table */}
                            <div className="flex flex-col gap-3">
                                <h3 className="text-center font-bold text-sky-800 dark:text-sky-400">
                                    {t('billing.prices_title_comparison')}
                                </h3>
                                <div className="overflow-x-auto rounded-xl border border-sky-500/30 bg-card shadow-sm dark:border-sky-500/20">
                                    <table className="w-full border-collapse text-start text-sm">
                                        <thead>
                                            <tr className="border-b border-sky-500/30 bg-sky-50/50 dark:border-sky-500/20 dark:bg-sky-950/20">
                                                <th
                                                    colSpan={2}
                                                    className="w-[220px] p-3 ps-4 text-start font-semibold text-sky-900 dark:text-sky-100"
                                                >
                                                    {t('billing.prices_column')}
                                                </th>
                                                {plans.map((plan) => (
                                                    <th
                                                        key={plan.id}
                                                        className="p-3 text-center font-bold text-sky-900 dark:text-sky-100"
                                                    >
                                                        {plan.name}
                                                    </th>
                                                ))}
                                            </tr>
                                        </thead>
                                        <tbody className="divide-y divide-sky-500/10">
                                            {DURATIONS.map((duration) => {
                                                return PLATFORM_SCOPES.map(
                                                    (scope, scopeIdx) => {
                                                        const showDurationCell =
                                                            scopeIdx === 0;
                                                        return (
                                                            <React.Fragment
                                                                key={`${duration}-${scope}`}
                                                            >
                                                                <tr className="hover:bg-sky-50/20 dark:hover:bg-sky-950/5">
                                                                    {showDurationCell && (
                                                                        <td
                                                                            rowSpan={
                                                                                2
                                                                            }
                                                                            className="w-[110px] border-e border-sky-500/15 bg-sky-50/20 p-3 ps-4 text-start align-middle font-bold text-foreground dark:bg-sky-950/5"
                                                                        >
                                                                            {t(
                                                                                `billing.duration_month_${duration as 1 | 3 | 6 | 12}`,
                                                                            )}
                                                                        </td>
                                                                    )}
                                                                    <td className="w-[110px] p-3 text-start font-medium text-muted-foreground">
                                                                        {t(
                                                                            `billing.platform_${scope}`,
                                                                        )}
                                                                    </td>
                                                                    {plans.map(
                                                                        (
                                                                            plan,
                                                                        ) => {
                                                                            const price =
                                                                                getPriceForPlan(
                                                                                    plan,
                                                                                    duration,
                                                                                    scope,
                                                                                );
                                                                            return (
                                                                                <td
                                                                                    key={
                                                                                        plan.id
                                                                                    }
                                                                                    className="p-3 text-center"
                                                                                >
                                                                                    {price ? (
                                                                                        <Button
                                                                                            variant="outline"
                                                                                            className="w-full justify-center border-sky-500/20 text-xs font-semibold tabular-nums transition-all duration-200 hover:border-amber-500 hover:bg-amber-500 hover:text-white dark:border-sky-500/10 dark:hover:border-amber-500 dark:hover:bg-amber-600"
                                                                                            disabled={
                                                                                                !canSubscribe
                                                                                            }
                                                                                            onClick={() =>
                                                                                                handlePriceClick(
                                                                                                    plan,
                                                                                                    price,
                                                                                                )
                                                                                            }
                                                                                        >
                                                                                            {formatPrice(
                                                                                                price.price,
                                                                                                price.currency,
                                                                                                locale,
                                                                                            )}
                                                                                        </Button>
                                                                                    ) : (
                                                                                        <span className="text-muted-foreground">
                                                                                            —
                                                                                        </span>
                                                                                    )}
                                                                                </td>
                                                                            );
                                                                        },
                                                                    )}
                                                                </tr>
                                                                {/* Promo banner logic - render after the 3-month scope is fully rendered (FB + IG) */}
                                                                {duration ===
                                                                    3 &&
                                                                    scopeIdx ===
                                                                        1 && (
                                                                        <tr key="promo-banner-row">
                                                                            <td
                                                                                colSpan={
                                                                                    plans.length +
                                                                                    2
                                                                                }
                                                                                className="border-y border-amber-500/25 bg-amber-500/10 px-4 py-2.5 text-center text-xs font-semibold text-amber-600 transition-all duration-200 dark:bg-amber-500/5 dark:text-amber-400"
                                                                            >
                                                                                ✨{' '}
                                                                                {t(
                                                                                    'billing.promo_banner',
                                                                                )}
                                                                            </td>
                                                                        </tr>
                                                                    )}
                                                            </React.Fragment>
                                                        );
                                                    },
                                                );
                                            })}
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    )}
                </div>

                {/* Subscription Requests List */}
                <div>
                    <h2 className="mb-3 font-sans text-lg font-medium">
                        {t('billing.your_requests')}
                    </h2>
                    {requests.length === 0 ? (
                        <p className="text-sm text-muted-foreground">
                            {t('admin.no_requests')}
                        </p>
                    ) : (
                        <div className="overflow-x-auto rounded-xl border border-sidebar-border/70 bg-card dark:border-sidebar-border">
                            <table className="w-full text-start text-sm">
                                <thead className="bg-muted/30 text-muted-foreground">
                                    <tr className="border-b border-sidebar-border/70">
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
                                    </tr>
                                </thead>
                                <tbody>
                                    {requests.map((req) => (
                                        <tr
                                            key={req.id}
                                            className="border-b border-sidebar-border/40 last:border-0 hover:bg-muted/10"
                                        >
                                            <td className="p-3">
                                                {req.plan_price?.plan?.name ??
                                                    '—'}
                                            </td>
                                            <td className="p-3 tabular-nums">
                                                {req.plan_price
                                                    ?.duration_months ??
                                                    '—'}{' '}
                                                {t('billing.months')}
                                            </td>
                                            <td className="p-3">
                                                <div className="flex flex-col gap-1 text-xs">
                                                    {req.payer_note && (
                                                        <div>
                                                            <span className="me-1 font-semibold text-muted-foreground">
                                                                {t(
                                                                    'billing.payer_note',
                                                                )}
                                                                :
                                                            </span>
                                                            <span className="text-foreground">
                                                                {req.payer_note}
                                                            </span>
                                                        </div>
                                                    )}
                                                    {req.status ===
                                                        'rejected' &&
                                                        req.reject_reason && (
                                                            <div>
                                                                <span className="me-1 font-semibold text-destructive">
                                                                    {t(
                                                                        'admin.reject_reason',
                                                                    )}
                                                                    :
                                                                </span>
                                                                <span className="font-medium text-destructive">
                                                                    {
                                                                        req.reject_reason
                                                                    }
                                                                </span>
                                                            </div>
                                                        )}
                                                    {!req.payer_note &&
                                                        !(
                                                            req.status ===
                                                                'rejected' &&
                                                            req.reject_reason
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
                                                        req.status,
                                                    )}
                                                >
                                                    {t(`status.${req.status}`)}
                                                </Badge>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    )}
                </div>
            </div>

            {/* Confirmation Dialog */}
            <Dialog
                open={confirmingPrice !== null}
                onOpenChange={(open) => !open && setConfirmingPrice(null)}
            >
                <DialogContent className="sm:max-w-md">
                    <DialogHeader>
                        <DialogTitle>{t('billing.confirm_title')}</DialogTitle>
                        <DialogDescription className="pt-2 text-sm leading-relaxed text-foreground">
                            {getConfirmationMessage()}
                        </DialogDescription>
                    </DialogHeader>

                    <div className="space-y-2 py-4">
                        <Label
                            htmlFor="modalPayerNote"
                            className="text-xs font-semibold"
                        >
                            {t('billing.payer_note')}
                        </Label>
                        <Input
                            id="modalPayerNote"
                            value={payerNote}
                            onChange={(e) => setPayerNote(e.target.value)}
                            placeholder={t('billing.payer_note_placeholder')}
                            className="w-full"
                        />
                    </div>

                    <DialogFooter className="gap-2 sm:gap-0">
                        <Button
                            type="button"
                            variant="outline"
                            onClick={() => setConfirmingPrice(null)}
                        >
                            {t('common.cancel')}
                        </Button>
                        <Button type="button" onClick={subscribe}>
                            {t('billing.confirm_submit')}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </>
    );
}

BillingIndex.layout = {
    breadcrumbs: [{ title: 'الاشتراك والفوترة', href: billing.index().url }],
};
