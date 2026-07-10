import { Head, router } from '@inertiajs/react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { useTranslations } from '@/hooks/use-translations';
import tenants from '@/routes/admin/tenants';

type Row = {
    id: number;
    name: string;
    slug: string;
    status: string;
    channel_connections_count: number;
    owner?: { name: string; email: string } | null;
};

type Props = {
    tenants: { data: Row[] };
};

export default function AdminTenants({ tenants: tenantList }: Props) {
    const { t } = useTranslations();

    const suspend = (id: number) => router.post(tenants.suspend(id).url);
    const activate = (id: number) => router.post(tenants.activate(id).url);

    return (
        <>
            <Head title={t('admin.tenants')} />
            <div className="mx-auto flex w-full max-w-6xl flex-1 flex-col gap-6 p-4">
                <h1 className="text-2xl font-semibold tracking-tight">
                    {t('admin.tenants')}
                </h1>

                {tenantList.data.length === 0 ? (
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
                                        {t('admin.owner')}
                                    </th>
                                    <th className="p-3 text-start">
                                        {t('admin.connections_count')}
                                    </th>
                                    <th className="p-3 text-start">
                                        {t('billing.status')}
                                    </th>
                                    <th className="p-3 text-end"></th>
                                </tr>
                            </thead>
                            <tbody>
                                {tenantList.data.map((row) => (
                                    <tr
                                        key={row.id}
                                        className="border-b border-sidebar-border/40 last:border-0"
                                    >
                                        <td className="p-3">{row.name}</td>
                                        <td className="p-3">
                                            {row.owner?.name ?? '—'}
                                            {row.owner?.email && (
                                                <span className="ms-1 text-xs text-muted-foreground">
                                                    ({row.owner.email})
                                                </span>
                                            )}
                                        </td>
                                        <td className="p-3">
                                            {row.channel_connections_count}
                                        </td>
                                        <td className="p-3">
                                            <Badge
                                                variant={
                                                    row.status === 'active'
                                                        ? 'default'
                                                        : 'destructive'
                                                }
                                            >
                                                {t(`status.${row.status}`)}
                                            </Badge>
                                        </td>
                                        <td className="p-3 text-end">
                                            {row.status === 'active' ? (
                                                <Button
                                                    size="sm"
                                                    variant="destructive"
                                                    onClick={() =>
                                                        suspend(row.id)
                                                    }
                                                >
                                                    {t('admin.suspend')}
                                                </Button>
                                            ) : (
                                                <Button
                                                    size="sm"
                                                    onClick={() =>
                                                        activate(row.id)
                                                    }
                                                >
                                                    {t('admin.activate')}
                                                </Button>
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

AdminTenants.layout = {
    breadcrumbs: [{ title: 'المستأجرون', href: tenants.index().url }],
};
