import { Link, usePage } from '@inertiajs/react';
import {
    BarChart3,
    Bot,
    ClipboardList,
    CreditCard,
    Gauge,
    LayoutGrid,
    Link2,
    Users,
    Webhook,
    Package,
} from 'lucide-react';
import AppLogo from '@/components/app-logo';
import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { useTranslations } from '@/hooks/use-translations';
import { dashboard } from '@/routes';
import { analytics as adminAnalytics, dashboard as adminDashboard } from '@/routes/admin';
import subscriptionRequests from '@/routes/admin/subscription-requests';
import tenants from '@/routes/admin/tenants';
import webhookEvents from '@/routes/admin/webhook-events';
import plans from '@/routes/admin/plans';
import analytics from '@/routes/analytics';
import billing from '@/routes/billing';
import connections from '@/routes/connections';
import rules from '@/routes/rules';
import type { NavItem } from '@/types';

export function AppSidebar() {
    const { t } = useTranslations();
    const { auth } = usePage().props;
    const isStaff = Boolean(auth.user?.is_platform_staff);

    const mainNavItems: NavItem[] = [
        { title: t('nav.dashboard'), href: dashboard(), icon: LayoutGrid },
        { title: t('nav.connections'), href: connections.index(), icon: Link2 },
        { title: t('nav.rules'), href: rules.index(), icon: Bot },
        { title: t('nav.analytics'), href: analytics.logs(), icon: BarChart3 },
        { title: t('nav.billing'), href: billing.index(), icon: CreditCard },
    ];

    const adminNavItems: NavItem[] = [
        { title: t('admin.dashboard'), href: adminDashboard(), icon: LayoutGrid },
        {
            title: t('admin.subscription_requests'),
            href: subscriptionRequests.index(),
            icon: ClipboardList,
        },
        { title: t('admin.tenants'), href: tenants.index(), icon: Users },
        { title: t('admin.plans'), href: plans.index(), icon: Package },
        { title: t('admin.analytics'), href: adminAnalytics(), icon: Gauge },
        {
            title: t('admin.webhook_events'),
            href: webhookEvents.index(),
            icon: Webhook,
        },
    ];

    return (
        <Sidebar collapsible="icon" variant="inset" side="right">
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild>
                            <Link
                                href={
                                    isStaff
                                        ? adminDashboard()
                                        : dashboard()
                                }
                                prefetch
                            >
                                <AppLogo />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent>
                {!isStaff && (
                    <NavMain items={mainNavItems} label={t('nav.main')} />
                )}
                {isStaff && (
                    <NavMain items={adminNavItems} label={t('nav.admin')} />
                )}
            </SidebarContent>

            <SidebarFooter>
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
