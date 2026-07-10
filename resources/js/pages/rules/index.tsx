import { Head, router, useForm } from '@inertiajs/react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { useTranslations } from '@/hooks/use-translations';
import rules from '@/routes/rules';

type Connection = { id: number; name: string; platform: string };
type Action = { id: number; action_type: string; message_template: string };
type Rule = {
    id: number;
    name: string;
    trigger_surface: string;
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

export default function RulesIndex({ rules: ruleList, connections }: Props) {
    const { t } = useTranslations();

    const form = useForm({
        channel_connection_id: connections[0]?.id ?? 0,
        name: '',
        trigger_surface: 'post_comment',
        target_scope: 'all',
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
            onSuccess: () => form.reset('name', 'keyword', 'actions'),
        });
    };

    const remove = (id: number) => router.delete(rules.destroy(id).url);

    const setAction = (patch: Partial<Action> & { delay_seconds?: number }) =>
        form.setData('actions', [{ ...form.data.actions[0], ...patch }]);

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
                                        onChange={(e) =>
                                            form.setData(
                                                'channel_connection_id',
                                                Number(e.target.value),
                                            )
                                        }
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

                                <label className="flex flex-col gap-1 text-sm">
                                    {t('rules.actions')}
                                    <select
                                        className={selectClass}
                                        value={form.data.actions[0].action_type}
                                        onChange={(e) =>
                                            setAction({
                                                action_type: e.target.value,
                                            })
                                        }
                                    >
                                        <option value="public_reply">
                                            {t('action.public_reply')}
                                        </option>
                                        <option value="private_reply">
                                            {t('action.private_reply')}
                                        </option>
                                    </select>
                                </label>

                                <label className="flex flex-col gap-1 text-sm">
                                    {t('rules.priority')}
                                    <Input
                                        type="number"
                                        value={form.data.priority}
                                        onChange={(e) =>
                                            form.setData(
                                                'priority',
                                                Number(e.target.value),
                                            )
                                        }
                                    />
                                </label>

                                <label className="flex flex-col gap-1 text-sm md:col-span-2">
                                    {t('action.public_reply')}
                                    <textarea
                                        className="min-h-20 w-full rounded-md border border-input bg-background p-3 text-sm shadow-sm focus:ring-2 focus:ring-ring focus:outline-none"
                                        value={
                                            form.data.actions[0]
                                                .message_template
                                        }
                                        onChange={(e) =>
                                            setAction({
                                                message_template:
                                                    e.target.value,
                                            })
                                        }
                                        placeholder="{{commenter_name}}"
                                        required
                                    />
                                </label>

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
