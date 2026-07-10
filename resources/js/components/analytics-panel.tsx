import {
    Layers,
    MessageSquare,
    Send,
    AlertCircle,
    TrendingUp,
    Sparkles,
    BarChart3,
} from 'lucide-react';
import { useTranslations } from '@/hooks/use-translations';

export type AnalyticsSummary = {
    events_received: number;
    replies_sent: number;
    dms_sent: number;
    failures: number;
    success_rate: number;
};

export type AnalyticsSeriesPoint = {
    date: string;
    replies: number;
    dms: number;
    failures: number;
};
export type AnalyticsTopRule = { name: string; sent: number };

type Props = {
    summary: AnalyticsSummary;
    series: AnalyticsSeriesPoint[];
    topRules: AnalyticsTopRule[];
};

export function AnalyticsPanel({ summary, series, topRules }: Props) {
    const { t } = useTranslations();

    // Custom stylings for each stats card
    const tiles = [
        {
            key: 'dashboard.events_received',
            value: summary.events_received,
            icon: Layers,
            bgClass:
                'bg-blue-500/5 dark:bg-blue-500/10 text-blue-600 dark:text-blue-400',
            borderClass: 'hover:border-blue-500/30',
        },
        {
            key: 'dashboard.replies_sent',
            value: summary.replies_sent,
            icon: MessageSquare,
            bgClass:
                'bg-violet-500/5 dark:bg-violet-500/10 text-violet-600 dark:text-violet-400',
            borderClass: 'hover:border-violet-500/30',
        },
        {
            key: 'dashboard.dms_sent',
            value: summary.dms_sent,
            icon: Send,
            bgClass:
                'bg-pink-500/5 dark:bg-pink-500/10 text-pink-600 dark:text-pink-400',
            borderClass: 'hover:border-pink-500/30',
        },
        {
            key: 'dashboard.failures',
            value: summary.failures,
            icon: AlertCircle,
            bgClass:
                'bg-rose-500/5 dark:bg-rose-500/10 text-rose-600 dark:text-rose-400',
            borderClass: 'hover:border-rose-500/30',
        },
        {
            key: 'dashboard.success_rate',
            value: `${summary.success_rate}%`,
            icon: TrendingUp,
            bgClass:
                'bg-emerald-500/5 dark:bg-emerald-500/10 text-emerald-600 dark:text-emerald-400',
            borderClass: 'hover:border-emerald-500/30',
        },
    ];

    const maxReplies = Math.max(
        1,
        ...series.map((point) => point.replies + point.dms),
    );
    const totalRuleUsage =
        topRules.reduce((acc, curr) => acc + curr.sent, 0) || 1;

    return (
        <div className="flex flex-col gap-6">
            {/* Stats Cards Grid */}
            <div className="grid w-full gap-4 sm:grid-cols-2 lg:grid-cols-5">
                {tiles.map((tile) => {
                    const Icon = tile.icon;

                    return (
                        <div
                            key={tile.key}
                            className={`group relative flex flex-col justify-between rounded-2xl border border-border bg-card/45 p-5 backdrop-blur-md transition-all duration-300 hover:-translate-y-1 hover:border-primary/20 hover:shadow-soft`}
                        >
                            <div className="flex items-center justify-between gap-4">
                                <span className="text-xs font-semibold text-muted-foreground sm:text-sm">
                                    {t(tile.key)}
                                </span>
                                <span
                                    className={`flex size-9 items-center justify-center rounded-xl transition-transform group-hover:scale-105 ${tile.bgClass}`}
                                >
                                    <Icon className="size-4.5" />
                                </span>
                            </div>
                            <p className="mt-4 text-3xl font-black tracking-tight text-foreground tabular-nums">
                                {tile.value}
                            </p>
                        </div>
                    );
                })}
            </div>

            {/* Charts & Rules Grid */}
            <div className="grid w-full gap-6 lg:grid-cols-3">
                {/* Replies Over Time Chart */}
                <div className="relative flex min-h-[320px] flex-col justify-between overflow-hidden rounded-2xl border border-border/50 bg-card/45 p-6 shadow-xs backdrop-blur-md glass-card transition-all duration-300 hover:shadow-soft lg:col-span-2">
                    <div className="pointer-events-none absolute inset-0 bg-[radial-gradient(var(--primary)_1px,transparent_1px)] bg-[size:22px_22px] opacity-[0.03]" />

                    <div className="relative z-10 mb-6 flex items-center justify-between border-b border-border/40 pb-3">
                        <h2 className="flex items-center gap-2 text-sm font-bold text-foreground">
                            <BarChart3 className="size-4 animate-pulse text-primary" />
                            <span>{t('dashboard.replies_over_time')}</span>
                        </h2>
                    </div>

                    <div
                        className="relative z-10 flex min-h-[180px] flex-1 items-end gap-2.5 pb-2"
                        role="img"
                        aria-label={t('dashboard.replies_over_time')}
                    >
                        {series.length === 0 ? (
                            <div className="absolute inset-0 flex items-center justify-center text-xs text-muted-foreground italic">
                                {t('dashboard.no_data')}
                            </div>
                        ) : (
                            series.map((point) => {
                                const total = point.replies + point.dms;
                                const height = Math.round(
                                    (total / maxReplies) * 100,
                                );

                                return (
                                    <div
                                        key={point.date}
                                        className="group relative flex flex-1 cursor-pointer flex-col items-center"
                                    >
                                        {/* Hover Tooltip */}
                                        <div className="absolute bottom-full z-50 mb-2 rounded-xl bg-gray-950 px-2.5 py-1.5 text-[10px] font-bold whitespace-nowrap text-white opacity-0 shadow-soft transition-opacity duration-200 group-hover:opacity-100 dark:bg-white dark:text-gray-950">
                                            {point.date}: {total} (
                                            {point.replies} R, {point.dms} DM)
                                        </div>

                                        <div
                                            className="w-full rounded-t-xl bg-gradient-to-t from-primary/65 to-primary shadow-xs transition-all group-hover:opacity-90 group-hover:shadow-[0_4px_12px_-2px_rgba(124,58,237,0.4)]"
                                            style={{
                                                height: `${Math.max(height, 4)}%`,
                                            }}
                                        />
                                        <span className="mt-2.5 hidden max-w-[45px] truncate text-[9px] font-bold text-muted-foreground sm:block">
                                            {point.date}
                                        </span>
                                    </div>
                                );
                            })
                        )}
                    </div>
                </div>

                {/* Top Rules List */}
                <div className="flex flex-col justify-between rounded-2xl border border-border/50 bg-card/45 p-6 shadow-xs backdrop-blur-md glass-card transition-all duration-300 hover:shadow-soft">
                    <div>
                        <div className="mb-6 flex items-center justify-between border-b border-border/40 pb-3">
                            <h2 className="flex items-center gap-2 text-sm font-bold text-foreground">
                                <Sparkles className="size-4 animate-pulse text-primary" />
                                <span>{t('dashboard.top_rules')}</span>
                            </h2>
                        </div>

                        {topRules.length === 0 ? (
                            <p className="py-10 text-center text-xs text-muted-foreground italic">
                                {t('dashboard.no_data')}
                            </p>
                        ) : (
                            <ul className="flex flex-col gap-4.5 text-sm">
                                {topRules.map((rule) => {
                                    const percent = Math.round(
                                        (rule.sent / totalRuleUsage) * 100,
                                    );

                                    return (
                                        <li
                                            key={rule.name}
                                            className="flex flex-col gap-2"
                                        >
                                            <div className="flex items-center justify-between gap-2">
                                                <span className="truncate font-bold text-foreground">
                                                    {rule.name}
                                                </span>
                                                <span className="text-xs font-black text-primary tabular-nums">
                                                    {rule.sent}
                                                </span>
                                            </div>
                                            {/* Progress Bar representation */}
                                            <div className="h-2 w-full overflow-hidden rounded-full bg-muted dark:bg-muted/30">
                                                <div
                                                    className="h-full rounded-full bg-gradient-to-r from-primary via-purple-500 to-accent transition-all duration-500"
                                                    style={{
                                                        width: `${percent}%`,
                                                    }}
                                                />
                                            </div>
                                        </li>
                                    );
                                })}
                            </ul>
                        )}
                    </div>
                </div>
            </div>
        </div>
    );
}
