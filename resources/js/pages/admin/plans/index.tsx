import { Head, useForm } from '@inertiajs/react';
import React, { useState } from 'react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useTranslations } from '@/hooks/use-translations';
import plansRoutes from '@/routes/admin/plans';

type PlanPrice = {
    id: number;
    duration_months: number;
    platform_scope: 'facebook' | 'facebook_instagram';
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

type Props = {
    plans: Plan[];
};

const DURATIONS = [1, 3, 6, 12];
const PLATFORMS: ('facebook' | 'facebook_instagram')[] = [
    'facebook',
    'facebook_instagram',
];

export default function AdminPlans({ plans }: Props) {
    const { t, locale } = useTranslations();
    const [editingPlan, setEditingPlan] = useState<Plan | null>(null);

    const getPriceValue = (
        plan: Plan | null,
        duration: number,
        scope: 'facebook' | 'facebook_instagram',
    ) => {
        if (!plan) return '';
        const priceObj = plan.prices.find(
            (p) => p.duration_months === duration && p.platform_scope === scope,
        );
        return priceObj ? priceObj.price : '0';
    };

    const { data, setData, put, processing, errors } = useForm({
        name: '',
        max_pages: 0,
        prices: [] as {
            duration_months: number;
            platform_scope: string;
            price: number;
        }[],
    });

    const openEdit = (plan: Plan) => {
        setEditingPlan(plan);
        setData({
            name: plan.name,
            max_pages: plan.max_pages,
            prices: DURATIONS.flatMap((d) =>
                PLATFORMS.map((p) => ({
                    duration_months: d,
                    platform_scope: p,
                    price: parseFloat(getPriceValue(plan, d, p) || '0'),
                })),
            ),
        });
    };

    const handlePriceChange = (
        duration: number,
        scope: string,
        value: string,
    ) => {
        const numericVal = parseFloat(value) || 0;
        setData(
            'prices',
            data.prices.map((p) =>
                p.duration_months === duration && p.platform_scope === scope
                    ? { ...p, price: numericVal }
                    : p,
            ),
        );
    };

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        if (!editingPlan) return;

        put(plansRoutes.update(editingPlan.id).url, {
            onSuccess: () => setEditingPlan(null),
        });
    };

    const formatPriceDisplay = (price: string, currency: string) => {
        const formattedNum = Number(price).toLocaleString();
        if (currency === 'SYP') {
            return locale === 'ar'
                ? `${formattedNum} ل.س`
                : `${formattedNum} SYP`;
        }
        return `${formattedNum} ${currency}`;
    };

    return (
        <>
            <Head title={t('admin.plans')} />
            <div className="mx-auto flex w-full max-w-6xl flex-1 flex-col gap-6 p-4">
                <div className="flex items-center justify-between">
                    <h1 className="text-2xl font-semibold tracking-tight">
                        {t('admin.plans')}
                    </h1>
                </div>

                {plans.length === 0 ? (
                    <p className="text-sm text-muted-foreground">
                        {t('admin.no_requests')}
                    </p>
                ) : (
                    <div className="overflow-x-auto rounded-xl border border-sidebar-border/70 dark:border-sidebar-border">
                        <table className="w-full text-start text-sm">
                            <thead className="text-muted-foreground">
                                <tr className="border-b border-sidebar-border/70">
                                    <th className="p-3 text-start">
                                        {t('admin.plan_name')}
                                    </th>
                                    <th className="p-3 text-start">Slug</th>
                                    <th className="p-3 text-start">
                                        {t('admin.allowed_pages')}
                                    </th>
                                    <th className="p-3 text-start">
                                        {t('admin.prices_per_duration')} (1m /
                                        3m / 6m / 12m)
                                    </th>
                                    <th className="p-3 text-end"></th>
                                </tr>
                            </thead>
                            <tbody>
                                {plans.map((plan) => (
                                    <tr
                                        key={plan.id}
                                        className="border-b border-sidebar-border/40 last:border-0"
                                    >
                                        <td className="p-3 font-medium">
                                            {plan.name}
                                        </td>
                                        <td className="p-3 text-muted-foreground">
                                            {plan.slug}
                                        </td>
                                        <td className="p-3">
                                            <Badge variant="secondary">
                                                {plan.max_pages}
                                            </Badge>
                                        </td>
                                        <td className="p-3">
                                            <div className="flex flex-col gap-1 text-xs">
                                                <div>
                                                    <span className="me-2 font-semibold text-muted-foreground">
                                                        FB:
                                                    </span>
                                                    {DURATIONS.map((d) => (
                                                        <span
                                                            key={d}
                                                            className="me-2 tabular-nums"
                                                        >
                                                            {formatPriceDisplay(
                                                                getPriceValue(
                                                                    plan,
                                                                    d,
                                                                    'facebook',
                                                                ),
                                                                plan.prices[0]
                                                                    ?.currency ??
                                                                    'SYP',
                                                            )}
                                                        </span>
                                                    ))}
                                                </div>
                                                <div>
                                                    <span className="me-2 font-semibold text-muted-foreground">
                                                        FB+IG:
                                                    </span>
                                                    {DURATIONS.map((d) => (
                                                        <span
                                                            key={d}
                                                            className="me-2 tabular-nums"
                                                        >
                                                            {formatPriceDisplay(
                                                                getPriceValue(
                                                                    plan,
                                                                    d,
                                                                    'facebook_instagram',
                                                                ),
                                                                plan.prices[0]
                                                                    ?.currency ??
                                                                    'SYP',
                                                            )}
                                                        </span>
                                                    ))}
                                                </div>
                                            </div>
                                        </td>
                                        <td className="p-3 text-end">
                                            <Button
                                                size="sm"
                                                variant="outline"
                                                onClick={() => openEdit(plan)}
                                            >
                                                {t('admin.edit_plan')}
                                            </Button>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                )}
            </div>

            <Dialog
                open={editingPlan !== null}
                onOpenChange={(open) => !open && setEditingPlan(null)}
            >
                <DialogContent className="sm:max-w-xl">
                    <DialogHeader>
                        <DialogTitle>{t('admin.edit_plan')}</DialogTitle>
                        <DialogDescription>
                            {t('welcome.pricing_subtitle')}
                        </DialogDescription>
                    </DialogHeader>
                    <form onSubmit={submit} className="space-y-6 py-2">
                        <div className="grid grid-cols-2 gap-4">
                            <div className="space-y-1">
                                <Label htmlFor="name">
                                    {t('admin.plan_name')}
                                </Label>
                                <Input
                                    id="name"
                                    value={data.name}
                                    onChange={(e) =>
                                        setData('name', e.target.value)
                                    }
                                    required
                                />
                                {errors.name && (
                                    <p className="text-xs text-destructive">
                                        {errors.name}
                                    </p>
                                )}
                            </div>
                            <div className="space-y-1">
                                <Label htmlFor="max_pages">
                                    {t('admin.allowed_pages')}
                                </Label>
                                <Input
                                    id="max_pages"
                                    type="number"
                                    min="1"
                                    value={data.max_pages}
                                    onChange={(e) =>
                                        setData(
                                            'max_pages',
                                            parseInt(e.target.value) || 0,
                                        )
                                    }
                                    required
                                />
                                {errors.max_pages && (
                                    <p className="text-xs text-destructive">
                                        {errors.max_pages}
                                    </p>
                                )}
                            </div>
                        </div>

                        <div className="space-y-3">
                            <Label className="text-sm font-semibold">
                                {t('admin.prices_grid')}
                            </Label>
                            <div className="overflow-hidden rounded-lg border border-border/70 text-xs">
                                <div className="grid grid-cols-3 border-b border-border/70 bg-muted/50 p-2 font-medium">
                                    <div>{t('billing.months')}</div>
                                    <div>{t('billing.platform_facebook')}</div>
                                    <div>
                                        {t(
                                            'billing.platform_facebook_instagram',
                                        )}
                                    </div>
                                </div>
                                <div className="divide-y divide-border/50">
                                    {DURATIONS.map((d) => (
                                        <div
                                            key={d}
                                            className="grid grid-cols-3 items-center gap-2 p-2"
                                        >
                                            <div className="font-medium">
                                                {d} {t('billing.months')}
                                            </div>
                                            <div>
                                                <Input
                                                    type="number"
                                                    min="0"
                                                    step="0.01"
                                                    className="h-8 py-1 text-xs tabular-nums"
                                                    value={
                                                        data.prices.find(
                                                            (p) =>
                                                                p.duration_months ===
                                                                    d &&
                                                                p.platform_scope ===
                                                                    'facebook',
                                                        )?.price ?? 0
                                                    }
                                                    onChange={(e) =>
                                                        handlePriceChange(
                                                            d,
                                                            'facebook',
                                                            e.target.value,
                                                        )
                                                    }
                                                    required
                                                />
                                            </div>
                                            <div>
                                                <Input
                                                    type="number"
                                                    min="0"
                                                    step="0.01"
                                                    className="h-8 py-1 text-xs tabular-nums"
                                                    value={
                                                        data.prices.find(
                                                            (p) =>
                                                                p.duration_months ===
                                                                    d &&
                                                                p.platform_scope ===
                                                                    'facebook_instagram',
                                                        )?.price ?? 0
                                                    }
                                                    onChange={(e) =>
                                                        handlePriceChange(
                                                            d,
                                                            'facebook_instagram',
                                                            e.target.value,
                                                        )
                                                    }
                                                    required
                                                />
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            </div>
                        </div>

                        <DialogFooter className="gap-2">
                            <Button
                                type="button"
                                variant="outline"
                                onClick={() => setEditingPlan(null)}
                            >
                                {t('common.cancel')}
                            </Button>
                            <Button type="submit" disabled={processing}>
                                {processing
                                    ? t('auth.logging_in')
                                    : t('common.submit')}
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>
        </>
    );
}

AdminPlans.layout = {
    breadcrumbs: [{ title: 'إدارة الباقات', href: plansRoutes.index().url }],
};
