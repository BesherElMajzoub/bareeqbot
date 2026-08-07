import { Head, router, useForm, useHttp } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { useTranslations } from '@/hooks/use-translations';
import rules from '@/routes/rules';

type Connection = { id: number; name: string; platform: string };
type Action = { id: number; action_type: string; message_template: string };
type Post = { id: string; title: string | null; created_time: string | null };
type Rule = {
    id: number;
    name: string;
    trigger_surface: string;
    target_scope: string;
    target_ref: string | null;
    match_type: string;
    keyword: string | null;
    priority: number;
    is_active: boolean;
    channel_connection?: Connection | null;
    actions: Action[];
};

type Props = { rules: Rule[]; connections: Connection[] };

const selectClass =
    'h-9 w-full rounded-md border border-input bg-background px-3 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-ring';

function truncate(text: string, max: number): string {
    return text.length > max ? `${text.slice(0, max)}…` : text;
}

export default function RulesIndex({ rules: ruleList, connections }: Props) {
    const { t } = useTranslations();
    const { submit: httpSubmit } = useHttp();

    const [posts, setPosts] = useState<Post[]>([]);
    const [postsLoading, setPostsLoading] = useState(false);

    const form = useForm({
        channel_connection_id: connections[0]?.id ?? 0,
        name: '',
        trigger_surface: 'post_comment',
        target_scope: 'all',
        target_ref: '',
        match_type: 'any',
        keyword: '',
        case_sensitive: false,
        priority: 0,
        is_active: true,
        actions: [
            {
                action_type: 'public_reply',
                message_template: '',
                delay_seconds: 0,
            },
        ],
    });

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        form.post(rules.store().url, {
            onSuccess: () =>
                form.reset('name', 'keyword', 'target_ref', 'actions'),
        });
    };

    const remove = (id: number) => router.delete(rules.destroy(id).url);

    const setAction = (patch: Partial<Action> & { delay_seconds?: number }) =>
        form.setData('actions', [{ ...form.data.actions[0], ...patch }]);

    const connectionId = form.data.channel_connection_id;
    const targetScope = form.data.target_scope;

    useEffect(() => {
        if (targetScope !== 'specific' || !connectionId) {
            return;
        }

        let cancelled = false;
        // eslint-disable-next-line react-hooks/set-state-in-effect -- loading flag for the fetch this effect triggers
        setPostsLoading(true);

        httpSubmit(
            rules.posts({ query: { channel_connection_id: connectionId } }),
        )
            .then((data) => {
                if (!cancelled) {
                    setPosts((data as { posts: Post[] }).posts);
                }
            })
            .catch(() => {
                if (!cancelled) {
                    setPosts([]);
                }
            })
            .finally(() => {
                if (!cancelled) {
                    setPostsLoading(false);
                }
            });

        return () => {
            cancelled = true;
        };
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [targetScope, connectionId]);

    return (
        <>
            <Head title={t('rules.title')} />
            <div className="mx-auto flex w-full max-w-5xl flex-1 flex-col gap-6 p-4">
                <h1 className="text-2xl font-semibold tracking-tight">
                    {t('rules.title')}
                </h1>

                <Card>
                    <CardHeader>
                        <CardTitle>{t('rules.add')}</CardTitle>
                    </CardHeader>
                    <CardContent>
                        {connections.length === 0 ? (
                            <p className="text-sm text-muted-foreground">
                                {t('connections.no_connections')}
                            </p>
                        ) : (
                            <form
                                onSubmit={submit}
                                className="grid gap-4 md:grid-cols-2"
                            >
                                <label className="flex flex-col gap-1 text-sm">
                                    {t('rules.connection')}
                                    <select
                                        className={selectClass}
                                        value={form.data.channel_connection_id}
                                        onChange={(e) => {
                                            form.setData({
                                                ...form.data,
                                                channel_connection_id: Number(
                                                    e.target.value,
                                                ),
                                                target_ref: '',
                                            });
                                        }}
                                    >
                                        {connections.map((c) => (
                                            <option key={c.id} value={c.id}>
                                                {c.name} (
                                                {t(
                                                    `connections.platform_${c.platform}`,
                                                )}
                                                )
                                            </option>
                                        ))}
                                    </select>
                                </label>

                                <label className="flex flex-col gap-1 text-sm">
                                    {t('rules.name')}
                                    <Input
                                        value={form.data.name}
                                        onChange={(e) =>
                                            form.setData('name', e.target.value)
                                        }
                                        required
                                    />
                                </label>

                                <label className="flex flex-col gap-1 text-sm">
                                    {t('rules.target_scope')}
                                    <select
                                        className={selectClass}
                                        value={form.data.target_scope}
                                        onChange={(e) =>
                                            form.setData(
                                                'target_scope',
                                                e.target.value,
                                            )
                                        }
                                    >
                                        <option value="all">
                                            {t('rules.target_all')}
                                        </option>
                                        <option value="specific">
                                            {t('rules.target_specific')}
                                        </option>
                                    </select>
                                </label>

                                {form.data.target_scope === 'specific' && (
                                    <label className="flex flex-col gap-1 text-sm">
                                        {t('rules.target_ref')}
                                        <select
                                            className={selectClass}
                                            value={form.data.target_ref}
                                            onChange={(e) =>
                                                form.setData(
                                                    'target_ref',
                                                    e.target.value,
                                                )
                                            }
                                            required
                                            disabled={postsLoading}
                                        >
                                            <option value="" disabled>
                                                {postsLoading
                                                    ? t(
                                                          'rules.target_ref_loading',
                                                      )
                                                    : t(
                                                          'rules.target_ref_placeholder',
                                                      )}
                                            </option>
                                            {posts.map((post) => (
                                                <option
                                                    key={post.id}
                                                    value={post.id}
                                                >
                                                    {post.title
                                                        ? truncate(
                                                              post.title,
                                                              60,
                                                          )
                                                        : post.id}
                                                </option>
                                            ))}
                                        </select>
                                        <span className="text-xs text-muted-foreground">
                                            {!postsLoading &&
                                            posts.length === 0
                                                ? t('rules.target_ref_empty')
                                                : t('rules.target_ref_help')}
                                        </span>
                                    </label>
                                )}

                                <label className="flex flex-col gap-1 text-sm">
                                    {t('rules.match')}
                                    <select
                                        className={selectClass}
                                        value={form.data.match_type}
                                        onChange={(e) =>
                                            form.setData(
                                                'match_type',
                                                e.target.value,
                                            )
                                        }
                                    >
                                        {[
                                            'any',
                                            'exact',
                                            'contains',
                                            'regex',
                                        ].map((m) => (
                                            <option key={m} value={m}>
                                                {t(`match.${m}`)}
                                            </option>
                                        ))}
                                    </select>
                                </label>

                                {form.data.match_type !== 'any' && (
                                    <label className="flex flex-col gap-1 text-sm">
                                        {t('rules.keyword')}
                                        <Input
                                            value={form.data.keyword}
                                            onChange={(e) =>
                                                form.setData(
                                                    'keyword',
                                                    e.target.value,
                                                )
                                            }
                                        />
                                    </label>
                                )}

                                 <div className="grid gap-4 md:col-span-2">
                                    <label className="flex flex-col gap-1 text-sm">
                                        <span className="font-medium text-foreground">
                                            💬 {t('action.public_reply')} (رد عام على التعليق)
                                        </span>
                                        <textarea
                                            className="min-h-20 w-full rounded-md border border-input bg-background p-3 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-ring"
                                            value={
                                                (form.data.actions || []).find(
                                                    (a) => a.action_type === 'public_reply',
                                                )?.message_template ?? ''
                                            }
                                            onChange={(e) => {
                                                const text = e.target.value;
                                                const existingPrivate = (form.data.actions || []).find(
                                                    (a) => a.action_type === 'private_reply',
                                                );
                                                const newActions = [];
                                                if (text.trim()) {
                                                    newActions.push({
                                                        action_type: 'public_reply',
                                                        message_template: text,
                                                        delay_seconds: 0,
                                                    });
                                                }
                                                if (existingPrivate && existingPrivate.message_template?.trim()) {
                                                    newActions.push(existingPrivate);
                                                }
                                                // Fallback if both empty
                                                if (newActions.length === 0) {
                                                    newActions.push({
                                                        action_type: 'public_reply',
                                                        message_template: text,
                                                        delay_seconds: 0,
                                                    });
                                                }
                                                form.setData('actions', newActions);
                                            }}
                                            placeholder="أهلاً بك {{commenter_name}}، تفضل التفاصيل في الرسائل الخاصة..."
                                        />
                                        <span className="text-xs text-muted-foreground">
                                            سيتم نشر هذا النص كتعليق عام تحت تعليق العميل. (استخدم {"{{commenter_name}}"} لطباعة اسم المعلق تلقائياً).
                                        </span>
                                    </label>

                                    <label className="flex flex-col gap-1 text-sm">
                                        <span className="font-medium text-foreground">
                                            ✉️ {t('action.private_reply')} (رسالة خاصة على المسنجر)
                                        </span>
                                        <textarea
                                            className="min-h-20 w-full rounded-md border border-input bg-background p-3 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-ring"
                                            value={
                                                (form.data.actions || []).find(
                                                    (a) => a.action_type === 'private_reply',
                                                )?.message_template ?? ''
                                            }
                                            onChange={(e) => {
                                                const text = e.target.value;
                                                const existingPublic = (form.data.actions || []).find(
                                                    (a) => a.action_type === 'public_reply',
                                                );
                                                const newActions = [];
                                                if (existingPublic && existingPublic.message_template?.trim()) {
                                                    newActions.push(existingPublic);
                                                }
                                                if (text.trim()) {
                                                    newActions.push({
                                                        action_type: 'private_reply',
                                                        message_template: text,
                                                        delay_seconds: 0,
                                                    });
                                                }
                                                if (newActions.length === 0) {
                                                    newActions.push({
                                                        action_type: 'public_reply',
                                                        message_template: '',
                                                        delay_seconds: 0,
                                                    });
                                                }
                                                form.setData('actions', newActions);
                                            }}
                                            placeholder="مرحباً {{commenter_name}}، تفاصيل الأسعار والعروض هي..."
                                        />
                                        <span className="text-xs text-muted-foreground">
                                            سيتم إرسال هذا النص كرسالة خاصة للعميل في المسنجر. (استخدم {"{{commenter_name}}"} لطباعة اسم المعلق تلقائياً).
                                        </span>
                                    </label>
                                </div>

                                {/* Live Preview Widget (المعاينة الحية للرد قبل الحفظ) */}
                                {((form.data.actions || []).some((a) => (a.message_template || '').trim().length > 0)) && (
                                    <div className="md:col-span-2 rounded-2xl border border-primary/20 bg-primary/5 p-4 shadow-inner space-y-4">
                                        <div className="flex items-center justify-between">
                                            <div className="flex items-center gap-2">
                                                <span className="flex size-7 items-center justify-center rounded-lg bg-primary/10 text-primary font-bold text-xs">
                                                    👁️
                                                </span>
                                                <h4 className="text-sm font-bold text-foreground">
                                                    المعاينة الحية للرد (Live Preview)
                                                </h4>
                                            </div>
                                            <span className="text-[11px] text-muted-foreground bg-background px-2.5 py-1 rounded-full border border-border/50">
                                                اسم المعلق التجريبي: <strong className="text-primary">أحمد العلي</strong>
                                            </span>
                                        </div>

                                        <div className="grid gap-4 md:grid-cols-2">
                                            {/* Public Comment Preview */}
                                            {((form.data.actions || []).find((a) => a.action_type === 'public_reply')?.message_template?.trim()) && (
                                                <div className="rounded-xl border border-border/60 bg-card p-3.5 shadow-xs space-y-2">
                                                    <div className="flex items-center justify-between text-xs text-muted-foreground pb-2 border-b border-border/40">
                                                        <span className="font-semibold text-blue-600 flex items-center gap-1.5">
                                                            💬 معاينة الرد العام على الفيسبوك
                                                        </span>
                                                        <span className="size-2 rounded-full bg-emerald-500 animate-pulse" />
                                                    </div>

                                                    <div className="space-y-2 pt-1">
                                                        {/* Simulated Customer Comment */}
                                                        <div className="flex items-start gap-2 text-xs">
                                                            <div className="size-7 rounded-full bg-muted flex items-center justify-center font-bold text-[10px] shrink-0">
                                                                أحمد
                                                            </div>
                                                            <div className="bg-muted/40 rounded-xl p-2 max-w-[90%] text-foreground">
                                                                <span className="font-bold block text-[11px] text-muted-foreground">أحمد العلي</span>
                                                                {form.data.keyword ? form.data.keyword : 'السلام عليكم، كم السعر والتفاصيل؟'}
                                                            </div>
                                                        </div>

                                                        {/* Simulated Bot Reply */}
                                                        <div className="flex items-start gap-2 text-xs ps-4 border-s-2 border-primary/30">
                                                            <div className="size-7 rounded-full bg-primary/10 text-primary flex items-center justify-center font-bold text-[10px] shrink-0">
                                                                🤖
                                                            </div>
                                                            <div className="bg-primary/5 border border-primary/15 rounded-xl p-2 max-w-[90%] text-foreground">
                                                                <span className="font-bold block text-[11px] text-primary">صفحتك الرسمية</span>
                                                                {((form.data.actions || []).find((a) => a.action_type === 'public_reply')?.message_template ?? '').replace(/\{\{\s*commenter_name\s*\}\}/g, 'أحمد العلي')}
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            )}

                                            {/* Private Messenger DM Preview */}
                                            {((form.data.actions || []).find((a) => a.action_type === 'private_reply')?.message_template?.trim()) && (
                                                <div className="rounded-xl border border-border/60 bg-card p-3.5 shadow-xs space-y-2">
                                                    <div className="flex items-center justify-between text-xs text-muted-foreground pb-2 border-b border-border/40">
                                                        <span className="font-semibold text-purple-600 flex items-center gap-1.5">
                                                            ✉️ معاينة الرسالة الخاصة (Messenger DM)
                                                        </span>
                                                        <span className="size-2 rounded-full bg-purple-500 animate-pulse" />
                                                    </div>

                                                    <div className="pt-1">
                                                        <div className="flex items-start gap-2 text-xs">
                                                            <div className="size-7 rounded-full bg-purple-100 dark:bg-purple-950/50 text-purple-600 flex items-center justify-center font-bold text-[10px] shrink-0">
                                                                ✉️
                                                            </div>
                                                            <div className="bg-card border border-purple-500/20 rounded-2xl rounded-ss-none p-2.5 max-w-[90%] shadow-xs text-foreground space-y-1">
                                                                <span className="font-bold block text-[10px] text-purple-600">رسالة خاصة من صفحتك إلى أحمد العلي</span>
                                                                <p className="whitespace-pre-wrap leading-relaxed">
                                                                    {((form.data.actions || []).find((a) => a.action_type === 'private_reply')?.message_template ?? '').replace(/\{\{\s*commenter_name\s*\}\}/g, 'أحمد العلي')}
                                                                </p>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            )}
                                        </div>
                                    </div>
                                )}

                                <div className="md:col-span-2">
                                    <Button
                                        type="submit"
                                        disabled={form.processing}
                                    >
                                        {t('rules.add')}
                                    </Button>
                                </div>
                            </form>
                        )}
                    </CardContent>
                </Card>

                <div className="overflow-x-auto rounded-xl border border-sidebar-border/70 dark:border-sidebar-border">
                    <table className="w-full text-start text-sm">
                        <thead className="text-muted-foreground">
                            <tr className="border-b border-sidebar-border/70">
                                <th className="p-3 text-start">
                                    {t('rules.name')}
                                </th>
                                <th className="p-3 text-start">
                                    {t('rules.connection')}
                                </th>
                                <th className="p-3 text-start">
                                    {t('rules.match')}
                                </th>
                                <th className="p-3 text-start">
                                    {t('rules.priority')}
                                </th>
                                <th className="p-3 text-start">
                                    {t('rules.active')}
                                </th>
                                <th className="p-3 text-end"></th>
                            </tr>
                        </thead>
                        <tbody>
                            {ruleList.length === 0 ? (
                                <tr>
                                    <td
                                        colSpan={6}
                                        className="p-4 text-muted-foreground"
                                    >
                                        {t('rules.no_rules')}
                                    </td>
                                </tr>
                            ) : (
                                ruleList.map((rule) => (
                                    <tr
                                        key={rule.id}
                                        className="border-b border-sidebar-border/40 last:border-0"
                                    >
                                        <td className="p-3">{rule.name}</td>
                                        <td className="p-3">
                                            {rule.channel_connection?.name ??
                                                '—'}
                                        </td>
                                        <td className="p-3">
                                            {t(`match.${rule.match_type}`)}
                                            {rule.keyword
                                                ? `: ${rule.keyword}`
                                                : ''}
                                            {rule.target_scope ===
                                                'specific' && (
                                                <div className="text-xs text-muted-foreground">
                                                    {t('rules.target_specific')}
                                                    : {rule.target_ref}
                                                </div>
                                            )}
                                        </td>
                                        <td className="p-3">{rule.priority}</td>
                                        <td className="p-3">
                                            <Badge
                                                variant={
                                                    rule.is_active
                                                        ? 'default'
                                                        : 'secondary'
                                                }
                                            >
                                                {rule.is_active
                                                    ? t('rules.active')
                                                    : '—'}
                                            </Badge>
                                        </td>
                                        <td className="p-3 text-end">
                                            <Button
                                                size="sm"
                                                variant="destructive"
                                                onClick={() => remove(rule.id)}
                                            >
                                                {t('rules.delete')}
                                            </Button>
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

RulesIndex.layout = {
    breadcrumbs: [{ title: 'قواعد الأتمتة', href: rules.index().url }],
};
