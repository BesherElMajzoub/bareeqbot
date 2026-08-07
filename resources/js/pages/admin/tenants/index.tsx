import { Head, router, useForm, usePage } from '@inertiajs/react';
import { useState } from 'react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useTranslations } from '@/hooks/use-translations';
import tenants from '@/routes/admin/tenants';

type SubscriptionRow = {
    id: number;
    ends_at: string;
    status: string;
    plan?: { name: string } | null;
};

type Row = {
    id: number;
    name: string;
    slug: string;
    status: string;
    channel_connections_count: number;
    owner?: { name: string; email: string } | null;
    subscriptions?: SubscriptionRow[];
};

type Props = {
    tenants: { data: Row[] };
};

export default function AdminTenants({ tenants: tenantList }: Props) {
    const { t } = useTranslations();
    const { flash } = usePage<{ flash?: { whatsapp_url?: string; whatsapp_text?: string } }>().props;

    const [showCreateForm, setShowCreateForm] = useState(false);

    const form = useForm({
        name: '',
        email: '',
        password: '',
        phone: '',
    });

    const suspend = (id: number) => router.post(tenants.suspend(id).url);
    const activate = (id: number) => router.post(tenants.activate(id).url);

    const handleCreate = (e: React.FormEvent) => {
        e.preventDefault();
        form.post('/admin/tenants', {
            onSuccess: () => {
                form.reset();
                setShowCreateForm(false);
            },
        });
    };

    const getWhatsAppLink = (name: string, email: string, phone?: string) => {
        const text = `أهلاً بك ${name} في منصة بريق للبوت والرد التلقائي! 🎉\n\nبيانات دخول حسابك هي:\nالبريد الإلكتروني: ${email}\n\nرابط تسجيل الدخول:\nhttps://bareeqplatform.site/login`;
        const cleanPhone = (phone || '').replace(/\D/g, '');
        return `https://wa.me/${cleanPhone}?text=${encodeURIComponent(text)}`;
    };

    return (
        <>
            <Head title={t('admin.tenants')} />
            <div className="mx-auto flex w-full max-w-6xl flex-1 flex-col gap-6 p-4">
                <div className="flex items-center justify-between">
                    <h1 className="text-2xl font-semibold tracking-tight">
                        {t('admin.tenants')}
                    </h1>
                    <Button onClick={() => setShowCreateForm(!showCreateForm)}>
                        {showCreateForm ? 'إلغاء' : '➕ إنشاء حساب متجر جديد'}
                    </Button>
                </div>

                {/* WhatsApp Share Alert Banner if just created */}
                {flash?.whatsapp_url && (
                    <div className="rounded-2xl border border-emerald-500/30 bg-emerald-500/10 p-4 space-y-2">
                        <div className="flex items-center justify-between">
                            <span className="font-bold text-emerald-700 dark:text-emerald-300 text-sm">
                                ✅ تم إنشاء الحساب بنجاح! يمكن إرسال بيانات الدخول عبر الواتساب فوراً:
                            </span>
                            <a
                                href={flash.whatsapp_url}
                                target="_blank"
                                rel="noopener noreferrer"
                                className="inline-flex items-center gap-2 rounded-xl bg-emerald-600 px-4 py-2 text-xs font-bold text-white shadow-md hover:bg-emerald-700"
                            >
                                💬 فتح وتراسل عبر الواتساب
                            </a>
                        </div>
                    </div>
                )}

                {/* Create Tenant Form */}
                {showCreateForm && (
                    <Card className="border-primary/30">
                        <CardHeader>
                            <CardTitle className="text-base">إنشاء حساب عميل ومتجر جديد (بواسطة الأدمن)</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <form onSubmit={handleCreate} className="grid gap-4 md:grid-cols-2">
                                <label className="flex flex-col gap-1 text-sm">
                                    <Label>اسم المتجر / العميل</Label>
                                    <Input
                                        value={form.data.name}
                                        onChange={(e) => form.setData('name', e.target.value)}
                                        placeholder="متجر الأناقة"
                                        required
                                    />
                                </label>

                                <label className="flex flex-col gap-1 text-sm">
                                    <Label>البريد الإلكتروني للعميل</Label>
                                    <Input
                                        type="email"
                                        value={form.data.email}
                                        onChange={(e) => form.setData('email', e.target.value)}
                                        placeholder="customer@example.com"
                                        required
                                    />
                                </label>

                                <label className="flex flex-col gap-1 text-sm">
                                    <Label>كلمة المرور الحساب</Label>
                                    <Input
                                        type="text"
                                        value={form.data.password}
                                        onChange={(e) => form.setData('password', e.target.value)}
                                        placeholder="كلمة مرور قوية (مثال: ClientPass@2026)"
                                        required
                                    />
                                </label>

                                <label className="flex flex-col gap-1 text-sm">
                                    <Label>رقم الواتساب للعميل (اختياري مع رمز الدولة)</Label>
                                    <Input
                                        type="text"
                                        value={form.data.phone}
                                        onChange={(e) => form.setData('phone', e.target.value)}
                                        placeholder="963912345678"
                                    />
                                </label>

                                <div className="md:col-span-2 flex justify-end gap-2 pt-2">
                                    <Button type="button" variant="outline" onClick={() => setShowCreateForm(false)}>
                                        إلغاء
                                    </Button>
                                    <Button type="submit" disabled={form.processing}>
                                        حفظ وأنشاء الحساب
                                    </Button>
                                </div>
                            </form>
                        </CardContent>
                    </Card>
                )}

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
                                    <th className="p-3 text-start">انتهاء الاشتراك</th>
                                    <th className="p-3 text-end">خيارات والإرسال</th>
                                </tr>
                            </thead>
                            <tbody>
                                {tenantList.data.map((row) => (
                                    <tr
                                        key={row.id}
                                        className="border-b border-sidebar-border/40 last:border-0"
                                    >
                                        <td className="p-3 font-semibold">{row.name}</td>
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
                                        <td className="p-3">
                                            {row.subscriptions && row.subscriptions.length > 0 ? (
                                                <div className="flex flex-col gap-0.5">
                                                    <span className="font-semibold text-xs text-foreground">
                                                        {new Date(row.subscriptions[0].ends_at).toISOString().split('T')[0]}
                                                    </span>
                                                    <span className="text-[11px] text-muted-foreground">
                                                        {row.subscriptions[0].plan?.name ?? 'اشتراك'}
                                                    </span>
                                                </div>
                                            ) : (
                                                <span className="text-xs text-muted-foreground">لا يوجد اشتراك نشط</span>
                                            )}
                                        </td>
                                        <td className="p-3 text-end flex justify-end gap-2">
                                            {row.owner?.email && (
                                                <a
                                                    href={getWhatsAppLink(row.owner.name || row.name, row.owner.email)}
                                                    target="_blank"
                                                    rel="noopener noreferrer"
                                                    className="inline-flex items-center gap-1 rounded-lg bg-emerald-600/10 text-emerald-600 px-2.5 py-1 text-xs font-bold hover:bg-emerald-600/20"
                                                >
                                                    💬 واتساب
                                                </a>
                                            )}
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
