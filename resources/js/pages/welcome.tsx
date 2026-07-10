import { Head, Link, usePage } from '@inertiajs/react';
import {
    Check,
    MessageSquare,
    Shield,
    BarChart3,
    Zap,
    Sun,
    Moon,
    ChevronDown,
    ArrowRight,
    Sparkles,
    ArrowLeft,
    Send,
    Instagram,
    Facebook,
    Menu,
    X,
} from 'lucide-react';
import { useState } from 'react';
import { useAppearance } from '@/hooks/use-appearance';
import { useTranslations } from '@/hooks/use-translations';
import { dashboard, login, register } from '@/routes';

export default function Welcome() {
    const { auth } = usePage().props;
    const { t, direction } = useTranslations();
    const { resolvedAppearance, updateAppearance } = useAppearance();

    // State for Billing Cycle (1 = Monthly, 3 = Quarterly, 6 = Semi-Annual, 12 = Annual)
    const [billingCycle, setBillingCycle] = useState<1 | 3 | 6 | 12>(1);

    // State for Interactive Demo
    const [demoComment, setDemoComment] = useState('');
    const [demoState, setDemoState] = useState<'idle' | 'typing' | 'replied'>(
        'idle',
    );
    const [submittedComment, setSubmittedComment] = useState('');

    // State for FAQs (index of open question, null if none)
    const [openFaq, setOpenFaq] = useState<number | null>(null);

    const [mobileMenuOpen, setMobileMenuOpen] = useState(false);

    const toggleFaq = (index: number) => {
        setOpenFaq(openFaq === index ? null : index);
    };

    const handleDemoSubmit = (e: React.FormEvent) => {
        e.preventDefault();

        if (!demoComment.trim()) {
            return;
        }

        setSubmittedComment(demoComment);
        setDemoState('typing');

        setTimeout(() => {
            setDemoState('replied');
        }, 1200);
    };

    const handleDemoReset = () => {
        setDemoComment('');
        setDemoState('idle');
        setSubmittedComment('');
    };

    // Plans data matching PlanSeeder.php pricing and logic
    const plans = [
        {
            name: 'Starter',
            slug: 'starter',
            maxPages: 1,
            baseMonthly: 300000,
            featuresKey: 'welcome.pricing.starter_features',
            color: 'from-blue-500/20 to-indigo-500/20',
            borderColor: 'hover:border-blue-500/50',
            badge: null,
        },
        {
            name: 'Growth',
            slug: 'growth',
            maxPages: 5,
            baseMonthly: 400000,
            featuresKey: 'welcome.pricing.growth_features',
            color: 'from-purple-500/20 to-pink-500/20',
            borderColor: 'hover:border-purple-500/50',
            badge: 'الشائع',
        },
        {
            name: 'Business',
            slug: 'business',
            maxPages: 15,
            baseMonthly: 500000,
            featuresKey: 'welcome.pricing.business_features',
            color: 'from-primary/20 to-accent/20',
            borderColor: 'border-primary/50 shadow-primary/10 shadow-lg',
            badge: 'الأفضل قيمة',
        },
        {
            name: 'Agency',
            slug: 'agency',
            maxPages: 50,
            baseMonthly: 900000,
            featuresKey: 'welcome.pricing.agency_features',
            color: 'from-amber-500/20 to-orange-500/20',
            borderColor: 'hover:border-amber-500/50',
            badge: 'للشركات',
        },
    ];

    const getMultiplier = (months: number) => {
        if (months === 3) {
            return 0.95;
        }

        if (months === 6) {
            return 0.9;
        }

        if (months === 12) {
            return 0.8;
        }

        return 1.0;
    };

    const calculatePrice = (base: number, months: number) => {
        const mult = getMultiplier(months);

        return Math.round(base * months * mult);
    };

    const isRtl = direction === 'rtl';

    return (
        <>
            <Head title={t('app.name') + ' - ' + t('app.tagline')} />
            <div className="flex min-h-screen flex-col items-center bg-background dotted-bg text-foreground transition-colors duration-300 selection:bg-primary/20 selection:text-primary">
                {/* Decorative glowing backdrops */}
                <div className="pointer-events-none absolute top-0 right-1/4 -z-10 size-[300px] rounded-full bg-primary/10 blur-[80px] sm:size-[500px] sm:blur-[120px]" />
                <div className="pointer-events-none absolute top-1/4 left-1/4 -z-10 size-[250px] rounded-full bg-accent/80 opacity-10 blur-[100px] sm:size-[450px] sm:blur-[150px]" />
                <div className="pointer-events-none absolute right-1/3 bottom-1/4 -z-10 size-[350px] rounded-full bg-primary/5 blur-[100px]" />

                {/* Header / Navbar */}
                <header className="fixed inset-x-0 top-4 z-50 mx-auto w-[min(1280px,95%)] transition-all duration-300">
                    <div className="flex items-center justify-between gap-4 rounded-full border border-border/40 bg-background/80 px-5 py-2.5 shadow-[0_18px_45px_rgba(76,29,149,0.08)] backdrop-blur-xl transition-all duration-300 dark:bg-card/85 dark:shadow-[0_18px_45px_rgba(0,0,0,0.3)]">
                        {/* Logo */}
                        <a href="#" className="flex items-center">
                            <div className="flex aspect-square size-12 items-center justify-center overflow-hidden rounded-full border border-border/80 bg-white p-1 shadow-sm">
                                <img
                                    src="/image.png"
                                    alt={t('app.name')}
                                    className="size-full object-contain"
                                />
                            </div>
                        </a>

                        {/* Middle Links - Desktop */}
                        <nav className="hidden items-center gap-1 lg:flex">
                            {[
                                { label: t('welcome.features_title'), href: '#features' },
                                { label: t('welcome.demo_title').replace(' الآن!', ''), href: '#demo' },
                                { label: t('welcome.how_title').replace('؟', ''), href: '#how' },
                                { label: t('welcome.pricing_title'), href: '#pricing' },
                                { label: t('welcome.faq_title'), href: '#faq' },
                            ].map((link) => (
                                <a
                                    key={link.href}
                                    href={link.href}
                                    className="rounded-full px-3.5 py-1.5 text-xs font-bold text-muted-foreground transition hover:bg-primary/5 hover:text-primary dark:hover:bg-primary/10"
                                >
                                    {link.label}
                                </a>
                            ))}
                        </nav>

                        {/* Action buttons + Theme Toggler */}
                        <div className="hidden md:flex items-center gap-2.5">
                            {/* Theme Toggler */}
                            <button
                                onClick={() =>
                                    updateAppearance(
                                        resolvedAppearance === 'dark'
                                            ? 'light'
                                            : 'dark',
                                    )
                                }
                                className="flex size-9 items-center justify-center rounded-full border border-border bg-card text-muted-foreground transition-all hover:bg-muted/50 hover:text-foreground active:scale-95"
                                aria-label="Toggle Theme"
                            >
                                {resolvedAppearance === 'dark' ? (
                                    <Sun className="size-4.5" />
                                ) : (
                                    <Moon className="size-4.5" />
                                )}
                            </button>

                            {auth.user ? (
                                <Link
                                    href={dashboard()}
                                    className="inline-flex h-9 items-center justify-center rounded-full bg-[linear-gradient(135deg,#5B21B6,#7C3AED,#A78BFA)] px-5 text-sm font-bold text-white shadow-[0_14px_32px_rgba(109,40,217,0.2)] transition-all hover:scale-[1.02] hover:-translate-y-0.5 hover:shadow-[0_18px_40px_rgba(109,40,217,0.3)] active:scale-[0.98]"
                                >
                                    {t('nav.dashboard')}
                                </Link>
                            ) : (
                                <>
                                    <Link
                                        href={login()}
                                        className="inline-flex h-9 items-center justify-center rounded-full px-4 text-sm font-semibold text-muted-foreground transition-colors hover:text-foreground"
                                    >
                                        {t('auth.login')}
                                    </Link>
                                    <Link
                                        href={register()}
                                        className="inline-flex h-9 items-center justify-center rounded-full bg-[linear-gradient(135deg,#5B21B6,#7C3AED,#A78BFA)] px-5 text-sm font-bold text-white shadow-[0_14px_32px_rgba(109,40,217,0.2)] transition-all hover:scale-[1.02] hover:-translate-y-0.5 hover:shadow-[0_18px_40px_rgba(109,40,217,0.3)] active:scale-[0.98]"
                                    >
                                        {t('auth.register')}
                                    </Link>
                                </>
                            )}
                        </div>

                        {/* Mobile controls (Menu and Theme) */}
                        <div className="flex items-center gap-2 lg:hidden">
                            {/* Theme Toggler for mobile */}
                            <button
                                onClick={() =>
                                    updateAppearance(
                                        resolvedAppearance === 'dark'
                                            ? 'light'
                                            : 'dark',
                                    )
                                }
                                className="flex size-9 items-center justify-center rounded-full border border-border bg-card text-muted-foreground transition-all hover:bg-muted/50 hover:text-foreground active:scale-95"
                                aria-label="Toggle Theme"
                            >
                                {resolvedAppearance === 'dark' ? (
                                    <Sun className="size-4.5" />
                                ) : (
                                    <Moon className="size-4.5" />
                                )}
                            </button>

                            {/* Hamburger button */}
                            <button
                                onClick={() => setMobileMenuOpen(!mobileMenuOpen)}
                                className="grid size-9 place-items-center rounded-full bg-primary/5 text-primary hover:bg-primary/10 transition-colors"
                                aria-label={mobileMenuOpen ? 'إغلاق القائمة' : 'فتح القائمة'}
                            >
                                {mobileMenuOpen ? (
                                    <X className="size-4.5" />
                                ) : (
                                    <Menu className="size-4.5" />
                                )}
                            </button>
                        </div>
                    </div>

                    {/* Mobile menu panel */}
                    {mobileMenuOpen && (
                        <div className="mt-2 rounded-3xl border border-border/40 bg-background/95 p-4 shadow-[0_18px_45px_rgba(76,29,149,0.08)] backdrop-blur-xl transition-all duration-300 dark:bg-card/95 lg:hidden">
                            <nav className="flex flex-col gap-1.5">
                                {[
                                    { label: t('welcome.features_title'), href: '#features' },
                                    { label: t('welcome.demo_title').replace(' الآن!', ''), href: '#demo' },
                                    { label: t('welcome.how_title').replace('؟', ''), href: '#how' },
                                    { label: t('welcome.pricing_title'), href: '#pricing' },
                                    { label: t('welcome.faq_title'), href: '#faq' },
                                ].map((link) => (
                                    <a
                                        key={link.href}
                                        href={link.href}
                                        onClick={() => setMobileMenuOpen(false)}
                                        className="rounded-xl px-4 py-2 text-sm font-semibold text-muted-foreground transition hover:bg-primary/5 hover:text-primary dark:hover:bg-primary/10"
                                    >
                                        {link.label}
                                    </a>
                                ))}

                                <div className="my-2 h-px bg-border/40" />

                                {auth.user ? (
                                    <Link
                                        href={dashboard()}
                                        onClick={() => setMobileMenuOpen(false)}
                                        className="inline-flex h-10 w-full items-center justify-center rounded-full bg-[linear-gradient(135deg,#5B21B6,#7C3AED,#A78BFA)] px-5 text-sm font-bold text-white shadow-md text-center"
                                    >
                                        {t('nav.dashboard')}
                                    </Link>
                                ) : (
                                    <div className="flex flex-col gap-2">
                                        <Link
                                            href={login()}
                                            onClick={() => setMobileMenuOpen(false)}
                                            className="inline-flex h-10 w-full items-center justify-center rounded-full px-4 text-sm font-semibold text-muted-foreground transition hover:bg-primary/5 hover:text-primary dark:hover:bg-primary/10"
                                        >
                                            {t('auth.login')}
                                        </Link>
                                        <Link
                                            href={register()}
                                            onClick={() => setMobileMenuOpen(false)}
                                            className="inline-flex h-10 w-full items-center justify-center rounded-full bg-[linear-gradient(135deg,#5B21B6,#7C3AED,#A78BFA)] px-5 text-sm font-bold text-white shadow-md text-center"
                                        >
                                            {t('auth.register')}
                                        </Link>
                                    </div>
                                )}
                            </nav>
                        </div>
                    )}
                </header>

                {/* Hero Section */}
                <section className="flex w-full max-w-7xl flex-col items-center px-6 pt-28 pb-20 lg:pt-36 lg:pb-28">
                    <div className="grid w-full items-center gap-12 lg:grid-cols-[1.1fr_1fr]">
                        {/* Hero Text Content */}
                        <div className="animate-fade-up space-y-8 text-center lg:text-start">
                            {/* Title with Gradient Accent */}
                            <h1 className="text-4xl leading-[1.15] font-black text-foreground sm:text-5xl sm:leading-[1.1] lg:text-6xl">
                                {t('welcome.hero_title')}{' '}
                                <br className="hidden sm:inline" />
                                <span className="relative inline-block bg-gradient-to-r from-primary via-purple-500 to-accent bg-clip-text text-transparent">
                                    {t('connections.platform_facebook')} &{' '}
                                    {t('connections.platform_instagram')}
                                    <span className="absolute right-0 -bottom-1 h-1 w-full rounded-full bg-[linear-gradient(90deg,var(--primary),oklch(0.72_0.18_300))] opacity-35" />
                                </span>
                            </h1>

                            {/* Subtitle */}
                            <p className="mx-auto max-w-2xl text-base leading-relaxed text-muted-foreground sm:text-lg lg:mx-0">
                                {t('welcome.hero_subtitle')}
                            </p>

                            {/* CTAs */}
                            <div className="flex flex-wrap justify-center gap-4 lg:justify-start">
                                {auth.user ? (
                                    <Link
                                        href={dashboard()}
                                        className="inline-flex h-13 items-center justify-center rounded-2xl bg-[linear-gradient(135deg,#4C1D95_0%,#7C3AED_58%,#B98CFF_100%)] px-8 text-base font-bold text-white shadow-lg shadow-purple-500/25 transition-all hover:-translate-y-0.5 hover:shadow-purple-500/35 active:scale-[0.98]"
                                    >
                                        <span>{t('nav.dashboard')}</span>
                                        {isRtl ? (
                                            <ArrowLeft className="ms-2.5 size-5" />
                                        ) : (
                                            <ArrowRight className="ms-2.5 size-5" />
                                        )}
                                    </Link>
                                ) : (
                                    <>
                                        <Link
                                            href={register()}
                                            className="inline-flex h-13 items-center justify-center rounded-2xl bg-[linear-gradient(135deg,#4C1D95_0%,#7C3AED_58%,#B98CFF_100%)] px-8 text-base font-bold text-white shadow-lg shadow-purple-500/25 transition-all hover:-translate-y-0.5 hover:shadow-purple-500/35 active:scale-[0.98]"
                                        >
                                            <span>
                                                {t('welcome.get_started')}
                                            </span>
                                            {isRtl ? (
                                                <ArrowLeft className="ms-2.5 size-5" />
                                            ) : (
                                                <ArrowRight className="ms-2.5 size-5" />
                                            )}
                                        </Link>
                                        <Link
                                            href={login()}
                                            className="inline-flex h-13 items-center justify-center rounded-2xl border-2 border-primary/40 bg-card px-8 text-base font-bold text-primary transition-all hover:-translate-y-0.5 hover:border-primary/60 hover:bg-primary/5 active:scale-[0.98]"
                                        >
                                            {t('auth.login')}
                                        </Link>
                                    </>
                                )}
                            </div>

                            {/* Quick Stats Banner */}
                            <div className="flex items-center justify-center gap-6 pt-4 text-xs text-muted-foreground sm:text-sm lg:justify-start">
                                <div>
                                    <span className="text-base font-extrabold text-foreground sm:text-lg">
                                        500K+
                                    </span>{' '}
                                    {t('welcome.stat.replies')}
                                </div>
                                <div className="h-4 w-px bg-border" />
                                <div>
                                    <span className="text-base font-extrabold text-foreground sm:text-lg">
                                        99.9%
                                    </span>{' '}
                                    {t('welcome.stat.uptime')}
                                </div>
                                <div className="h-4 w-px bg-border" />
                                <div>
                                    <span className="text-base font-extrabold text-foreground sm:text-lg">
                                        1,200+
                                    </span>{' '}
                                    {t('welcome.stat.customers')}
                                </div>
                            </div>
                        </div>

                        {/* Hero Graphic: Logo Showcase */}
                        <div className="relative mx-auto flex w-full max-w-[550px] items-center justify-center pt-10 lg:max-w-none lg:pt-0">
                            {/* Radial glow behind logo */}
                            <div className="pointer-events-none absolute inset-0 flex items-center justify-center">
                                <div className="size-[280px] rounded-full bg-primary/15 blur-[80px] sm:size-[380px] sm:blur-[100px]" />
                            </div>
                            <div className="pointer-events-none absolute inset-0 flex items-center justify-center">
                                <div className="size-[180px] rounded-full bg-purple-500/10 blur-[60px] sm:size-[250px] sm:blur-[80px]" />
                            </div>

                            {/* Decorative orbit rings */}
                            <div className="pointer-events-none absolute inset-0 flex items-center justify-center">
                                <div className="size-[300px] animate-[spin_40s_linear_infinite] rounded-full border border-dashed border-primary/15 sm:size-[420px]" />
                            </div>
                            <div className="pointer-events-none absolute inset-0 flex items-center justify-center">
                                <div className="size-[220px] animate-[spin_30s_linear_infinite_reverse] rounded-full border border-primary/10 sm:size-[320px]" />
                            </div>

                            {/* Orbiting small dots */}
                            <div className="pointer-events-none absolute inset-0 flex items-center justify-center">
                                <div className="size-[300px] animate-[spin_25s_linear_infinite] sm:size-[420px]">
                                    <span className="absolute top-0 left-1/2 size-2.5 -translate-x-1/2 rounded-full bg-primary shadow-[0_0_8px_var(--primary)]" />
                                    <span className="absolute bottom-0 left-1/2 size-2 -translate-x-1/2 rounded-full bg-purple-400 shadow-[0_0_8px_oklch(0.72_0.18_300)]" />
                                </div>
                            </div>
                            <div className="pointer-events-none absolute inset-0 flex items-center justify-center">
                                <div className="size-[220px] animate-[spin_20s_linear_infinite_reverse] sm:size-[320px]">
                                    <span className="absolute top-1/2 left-0 size-2 -translate-y-1/2 rounded-full bg-accent" />
                                    <span className="absolute top-1/2 right-0 size-1.5 -translate-y-1/2 rounded-full bg-pink-400" />
                                </div>
                            </div>

                            {/* Main Logo Container */}
                            <div className="relative z-10 flex min-h-[340px] items-center justify-center sm:min-h-[440px]">
                                <div className="animate-float rounded-[2rem] border border-white/20 bg-white/80 p-3 shadow-2xl shadow-primary/20 backdrop-blur-sm dark:border-white/10 dark:bg-white/10 sm:rounded-[2.5rem] sm:p-4">
                                    <img
                                        src="/image.png"
                                        alt={t('app.name')}
                                        className="size-32 rounded-[1.5rem] object-contain sm:size-44 sm:rounded-[2rem]"
                                    />
                                </div>
                            </div>

                            {/* Floating Widget 1: Comment auto-reply (Top Right) */}
                            <div className="absolute -top-2 right-2 z-20 animate-float rounded-2xl px-4 py-3 shadow-[var(--shadow-soft)] glass-card sm:-top-4 sm:-right-6">
                                <div className="flex items-center gap-2.5">
                                    <span className="grid h-9 w-9 place-items-center rounded-xl bg-primary/10 text-primary">
                                        <MessageSquare className="h-4 w-4" />
                                    </span>
                                    <div className="text-right">
                                        <div className="text-[9px] font-bold text-muted-foreground">
                                            الرد التلقائي
                                        </div>
                                        <div className="text-xs font-extrabold text-foreground">
                                            تعليق مؤتمت ✓
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {/* Floating Widget 2: Private DM Sent (Middle Left) */}
                            <div className="absolute top-[35%] -left-2 z-20 animate-[float_10s_ease-in-out_infinite] rounded-2xl px-4 py-3 shadow-[var(--shadow-soft)] glass-card sm:-left-8">
                                <div className="flex items-center gap-2.5">
                                    <span className="grid h-9 w-9 place-items-center rounded-xl bg-pink-500/10 text-pink-600">
                                        <Send className="h-4 w-4" />
                                    </span>
                                    <div className="text-right">
                                        <div className="text-[9px] font-bold text-muted-foreground">
                                            رسالة خاصة DM
                                        </div>
                                        <div className="text-xs font-extrabold text-foreground">
                                            تفاصيل السعر 💬
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {/* Floating Widget 3: Live Performance (Bottom Center) */}
                            <div className="absolute right-1/4 bottom-2 z-20 animate-[float_12s_ease-in-out_infinite] rounded-2xl px-4 py-3 shadow-[var(--shadow-soft)] glass-card sm:bottom-8">
                                <div className="flex items-center gap-2.5">
                                    <span className="grid h-9 w-9 place-items-center rounded-xl bg-emerald-500/10 text-emerald-600">
                                        <BarChart3 className="h-4 w-4" />
                                    </span>
                                    <div className="text-right">
                                        <div className="text-[9px] font-bold text-muted-foreground">
                                            معدل التفاعل
                                        </div>
                                        <div className="text-xs font-extrabold text-foreground">
                                            +48.2% 📈
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {/* Floating Widget 4: Official Security API (Middle Right) */}
                            <div className="absolute top-[65%] -right-2 z-20 animate-[float_9s_ease-in-out_infinite] rounded-2xl px-4 py-3 shadow-[var(--shadow-soft)] glass-card sm:-right-10">
                                <div className="flex items-center gap-2.5">
                                    <span className="grid h-9 w-9 place-items-center rounded-xl bg-blue-500/10 text-blue-600">
                                        <Shield className="h-4 w-4" />
                                    </span>
                                    <div className="text-right">
                                        <div className="text-[9px] font-bold text-muted-foreground">
                                            الأمان والموثوقية
                                        </div>
                                        <div className="text-xs font-extrabold text-foreground">
                                            ربط رسمي Meta 🔒
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                {/* Features Grid Section */}
                <section
                    id="features"
                    className="w-full border-y border-border/30 bg-card/25 py-20 transition-colors duration-300 lg:py-28"
                >
                    <div className="mx-auto flex max-w-7xl flex-col items-center px-6">
                        <div className="mb-16 max-w-3xl text-center sm:mb-20">
                            <span className="mb-2 block text-xs font-bold tracking-wider text-primary uppercase">
                                ميزات متكاملة
                            </span>
                            <h2 className="mb-4 text-3xl font-extrabold tracking-tight sm:text-5xl">
                                {t('welcome.features_title')}
                            </h2>
                            <p className="text-lg text-muted-foreground">
                                {t('welcome.features_subtitle')}
                            </p>
                        </div>

                        <div className="grid w-full gap-8 sm:grid-cols-2 lg:grid-cols-3">
                            {/* Feature 1 */}
                            <div className="group relative flex flex-col items-start rounded-3xl border border-border/40 bg-card p-8 shadow-xs transition-all duration-300 hover:-translate-y-1 hover:border-primary/35 hover:shadow-xl">
                                <div className="mb-6 flex size-12 items-center justify-center rounded-2xl bg-primary/10 text-primary transition-transform group-hover:scale-110">
                                    <MessageSquare className="size-6 fill-primary/5" />
                                </div>
                                <h3 className="mb-3 text-xl font-bold">
                                    {t('welcome.feature.comments.title')}
                                </h3>
                                <p className="text-start text-sm leading-relaxed text-muted-foreground">
                                    {t('welcome.feature.comments.desc')}
                                </p>
                            </div>

                            {/* Feature 2 */}
                            <div className="group relative flex flex-col items-start rounded-3xl border border-border/40 bg-card p-8 shadow-xs transition-all duration-300 hover:-translate-y-1 hover:border-primary/35 hover:shadow-xl">
                                <div className="mb-6 flex size-12 items-center justify-center rounded-2xl bg-primary/10 text-primary transition-transform group-hover:scale-110">
                                    <Send className="size-6" />
                                </div>
                                <h3 className="mb-3 text-xl font-bold">
                                    {t('welcome.feature.dms.title')}
                                </h3>
                                <p className="text-start text-sm leading-relaxed text-muted-foreground">
                                    {t('welcome.feature.dms.desc')}
                                </p>
                            </div>

                            {/* Feature 3 */}
                            <div className="group relative flex flex-col items-start rounded-3xl border border-border/40 bg-card p-8 shadow-xs transition-all duration-300 hover:-translate-y-1 hover:border-primary/35 hover:shadow-xl">
                                <div className="mb-6 flex size-12 items-center justify-center rounded-2xl bg-primary/10 text-primary transition-transform group-hover:scale-110">
                                    <Instagram className="size-6" />
                                </div>
                                <h3 className="mb-3 text-xl font-bold">
                                    {t('welcome.feature.stories.title')}
                                </h3>
                                <p className="text-start text-sm leading-relaxed text-muted-foreground">
                                    {t('welcome.feature.stories.desc')}
                                </p>
                            </div>

                            {/* Feature 4 */}
                            <div className="group relative flex flex-col items-start rounded-3xl border border-border/40 bg-card p-8 shadow-xs transition-all duration-300 hover:-translate-y-1 hover:border-primary/35 hover:shadow-xl">
                                <div className="mb-6 flex size-12 items-center justify-center rounded-2xl bg-primary/10 text-primary transition-transform group-hover:scale-110">
                                    <Sparkles className="size-6 fill-primary/5" />
                                </div>
                                <h3 className="mb-3 text-xl font-bold">
                                    {t('welcome.feature.rules.title')}
                                </h3>
                                <p className="text-start text-sm leading-relaxed text-muted-foreground">
                                    {t('welcome.feature.rules.desc')}
                                </p>
                            </div>

                            {/* Feature 5 */}
                            <div className="group relative flex flex-col items-start rounded-3xl border border-border/40 bg-card p-8 shadow-xs transition-all duration-300 hover:-translate-y-1 hover:border-primary/35 hover:shadow-xl">
                                <div className="mb-6 flex size-12 items-center justify-center rounded-2xl bg-primary/10 text-primary transition-transform group-hover:scale-110">
                                    <BarChart3 className="size-6" />
                                </div>
                                <h3 className="mb-3 text-xl font-bold">
                                    {t('welcome.feature.analytics.title')}
                                </h3>
                                <p className="text-start text-sm leading-relaxed text-muted-foreground">
                                    {t('welcome.feature.analytics.desc')}
                                </p>
                            </div>

                            {/* Feature 6 */}
                            <div className="group relative flex flex-col items-start rounded-3xl border border-border/40 bg-card p-8 shadow-xs transition-all duration-300 hover:-translate-y-1 hover:border-primary/35 hover:shadow-xl">
                                <div className="mb-6 flex size-12 items-center justify-center rounded-2xl bg-primary/10 text-primary transition-transform group-hover:scale-110">
                                    <Shield className="size-6" />
                                </div>
                                <h3 className="mb-3 text-xl font-bold">
                                    {t('welcome.feature.secure.title')}
                                </h3>
                                <p className="text-start text-sm leading-relaxed text-muted-foreground">
                                    {t('welcome.feature.secure.desc')}
                                </p>
                            </div>
                        </div>
                    </div>
                </section>

                {/* Interactive Demo Sandbox Section */}
                <section
                    id="demo"
                    className="flex w-full max-w-7xl flex-col items-center px-6 py-20 lg:py-28"
                >
                    <div className="mb-14 max-w-3xl text-center">
                        <span className="mb-2 block text-xs font-bold tracking-wider text-primary uppercase">
                            تجربة تفاعلية (Interactive Simulator)
                        </span>
                        <h2 className="mb-4 text-3xl font-extrabold tracking-tight sm:text-5xl">
                            {t('welcome.demo_title')}
                        </h2>
                        <p className="text-lg text-muted-foreground">
                            {t('welcome.demo_subtitle')}
                        </p>
                    </div>

                    {/* Simulation Wrapper */}
                    <div className="grid w-full max-w-5xl items-stretch gap-10 lg:grid-cols-5">
                        {/* Simulation Input Block (Left/Right depending on direction) */}
                        <div className="flex flex-col justify-between rounded-3xl border border-border/50 bg-card p-6 shadow-md sm:p-8 lg:col-span-2">
                            <div>
                                <h3 className="mb-4 flex items-center gap-2 text-lg font-bold">
                                    <MessageSquare className="size-5 text-primary" />
                                    <span>
                                        {t('welcome.demo.comment_label')}
                                    </span>
                                </h3>

                                <form
                                    onSubmit={handleDemoSubmit}
                                    className="space-y-4"
                                >
                                    <div className="relative">
                                        <input
                                            type="text"
                                            value={demoComment}
                                            onChange={(e) =>
                                                setDemoComment(e.target.value)
                                            }
                                            placeholder={t(
                                                'welcome.demo.input_placeholder',
                                            )}
                                            disabled={demoState === 'typing'}
                                            className="h-12 w-full rounded-xl border border-border bg-background px-4 text-sm focus:ring-2 focus:ring-primary/45 focus:outline-hidden disabled:opacity-60"
                                            maxLength={80}
                                        />
                                    </div>

                                    <div className="flex gap-2">
                                        <button
                                            type="submit"
                                            disabled={
                                                !demoComment.trim() ||
                                                demoState === 'typing'
                                            }
                                            className="h-12 flex-1 rounded-xl bg-primary text-sm font-bold text-primary-foreground shadow-sm transition-all hover:scale-[1.01] hover:bg-primary/95 active:scale-[0.99] disabled:opacity-50"
                                        >
                                            {t('welcome.demo.send_btn')}
                                        </button>

                                        {demoState !== 'idle' && (
                                            <button
                                                type="button"
                                                onClick={handleDemoReset}
                                                className="h-12 rounded-xl border border-border bg-card px-4 text-sm font-semibold transition-colors hover:bg-muted/40"
                                            >
                                                إعادة تعيين
                                            </button>
                                        )}
                                    </div>
                                </form>
                            </div>

                            {/* Quick keywords helper tags */}
                            <div className="mt-8 border-t border-border/50 pt-6">
                                <span className="mb-3 block text-xs font-bold text-muted-foreground">
                                    انقر على كلمة مفتاحية شائعة لتجربتها:
                                </span>
                                <div className="flex flex-wrap gap-2">
                                    {[
                                        'بكم السعر؟',
                                        'تفاصيل الخدمة',
                                        'موقعكم',
                                        'مهتم',
                                    ].map((keyword) => (
                                        <button
                                            key={keyword}
                                            onClick={() => {
                                                if (demoState !== 'typing') {
                                                    setDemoComment(keyword);
                                                }
                                            }}
                                            disabled={demoState === 'typing'}
                                            className="rounded-lg border border-border bg-muted/40 px-3 py-1.5 text-xs font-semibold transition-colors hover:border-primary/30 hover:bg-primary/5 disabled:opacity-40"
                                        >
                                            {keyword}
                                        </button>
                                    ))}
                                </div>
                            </div>
                        </div>

                        {/* Interactive UI Screens Block (Meta Simulator Visuals) */}
                        <div className="flex flex-col justify-center gap-6 lg:col-span-3">
                            {/* Box 1: Facebook / Instagram Comment Reply Simulation */}
                            <div className="flex flex-col overflow-hidden rounded-2xl border border-border/60 bg-card shadow-md">
                                <div className="flex items-center justify-between border-b border-border/50 bg-muted/50 px-4 py-3">
                                    <div className="flex items-center gap-2">
                                        <Facebook className="size-4 fill-blue-600 text-blue-600" />
                                        <span className="text-xs font-bold text-muted-foreground">
                                            صندوق التعليقات على فيسبوك
                                        </span>
                                    </div>
                                    <span className="size-2 animate-pulse rounded-full bg-emerald-500" />
                                </div>

                                <div className="flex min-h-[120px] flex-col justify-center space-y-4 p-4">
                                    {demoState === 'idle' ? (
                                        <p className="text-center text-sm text-muted-foreground italic">
                                            بانتظار كتابة تعليق وتجربة
                                            الإرسال...
                                        </p>
                                    ) : (
                                        <div className="space-y-3">
                                            {/* User Comment */}
                                            <div className="flex items-start gap-3 text-start">
                                                <div className="flex size-8 shrink-0 items-center justify-center rounded-full border border-border bg-muted/90 text-xs font-bold">
                                                    أنت
                                                </div>
                                                <div className="max-w-[85%] rounded-xl border border-border/40 bg-muted/40 px-3 py-2 text-sm">
                                                    <span className="mb-0.5 block text-xs font-bold">
                                                        عميل افتراضي
                                                    </span>
                                                    {submittedComment}
                                                </div>
                                            </div>

                                            {/* Typing indicator */}
                                            {demoState === 'typing' && (
                                                <div className="flex animate-pulse items-start gap-3 text-start">
                                                    <div className="flex size-8 shrink-0 items-center justify-center rounded-full border border-primary/20 bg-primary/10 text-xs font-bold">
                                                        🤖
                                                    </div>
                                                    <div className="flex items-center gap-2 rounded-xl border border-primary/10 bg-primary/5 px-3 py-2 text-xs font-semibold text-primary">
                                                        <span className="size-1.5 animate-bounce rounded-full bg-primary" />
                                                        <span className="size-1.5 animate-bounce rounded-full bg-primary delay-150" />
                                                        <span className="size-1.5 animate-bounce rounded-full bg-primary delay-300" />
                                                        <span>
                                                            {t(
                                                                'welcome.demo.bot_typing',
                                                            )}
                                                        </span>
                                                    </div>
                                                </div>
                                            )}

                                            {/* Bot Comment Reply */}
                                            {demoState === 'replied' && (
                                                <div className="animate-fade-in flex items-start gap-3 text-start">
                                                    <div className="flex size-8 shrink-0 items-center justify-center overflow-hidden rounded-full border border-primary/20 bg-primary/10">
                                                        <img
                                                            src="/image.png"
                                                            alt="Bariq Bot"
                                                            className="size-full bg-white object-contain p-0.5"
                                                        />
                                                    </div>
                                                    <div className="max-w-[85%] rounded-xl border border-primary/20 bg-primary/5 px-3 py-2 text-sm">
                                                        <span className="mb-0.5 block text-xs font-bold text-primary">
                                                            {t('app.name')} (بوت
                                                            الأتمتة)
                                                        </span>
                                                        {t(
                                                            'welcome.demo.bot_comment',
                                                        )}
                                                    </div>
                                                </div>
                                            )}
                                        </div>
                                    )}
                                </div>
                            </div>

                            {/* Box 2: Instagram Direct Message (DM) Simulation */}
                            <div className="flex flex-col overflow-hidden rounded-2xl border border-border/60 bg-card shadow-md">
                                <div className="flex items-center justify-between border-b border-border/50 bg-muted/50 px-4 py-3">
                                    <div className="flex items-center gap-2">
                                        <Instagram className="size-4 text-pink-600" />
                                        <span className="text-xs font-bold text-muted-foreground">
                                            الرسائل الخاصة في إنستغرام (DM)
                                        </span>
                                    </div>
                                    {demoState === 'replied' && (
                                        <span className="animate-bounce rounded-md bg-primary/10 px-2 py-0.5 text-[10px] font-black text-primary">
                                            رسالة جديدة!
                                        </span>
                                    )}
                                </div>

                                <div className="flex min-h-[100px] flex-col justify-center p-4">
                                    {demoState !== 'replied' ? (
                                        <p className="text-center text-sm text-muted-foreground italic">
                                            {demoState === 'typing'
                                                ? 'يتم إرسال رسالة خاصة في الخلفية...'
                                                : 'ستصلك رسالة خاصة هنا بعد الرد على التعليق...'}
                                        </p>
                                    ) : (
                                        <div className="animate-fade-in flex items-start gap-3 text-start">
                                            <div className="flex size-8 shrink-0 items-center justify-center overflow-hidden rounded-full border border-primary/20 bg-primary/10">
                                                <img
                                                    src="/image.png"
                                                    alt="Bariq Bot"
                                                    className="size-full bg-white object-contain p-0.5"
                                                />
                                            </div>
                                            <div className="max-w-[80%] rounded-2xl rounded-ss-none border border-border bg-card px-4 py-2.5 text-sm shadow-xs">
                                                <span className="mb-0.5 block text-[10px] font-bold text-pink-600">
                                                    الحساب التجاري
                                                </span>
                                                {t('welcome.demo.bot_dm')}
                                            </div>
                                        </div>
                                    )}
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                {/* Steps Section */}
                <section
                    id="how"
                    className="w-full border-y border-border/30 bg-card/10 py-20 transition-colors duration-300 lg:py-28"
                >
                    <div className="mx-auto flex max-w-7xl flex-col items-center px-6">
                        <div className="mb-16 max-w-3xl text-center">
                            <span className="mb-2 block text-xs font-bold tracking-wider text-primary uppercase">
                                آلية العمل
                            </span>
                            <h2 className="mb-4 text-3xl font-extrabold tracking-tight sm:text-5xl">
                                {t('welcome.how_title')}
                            </h2>
                            <p className="text-lg text-muted-foreground">
                                {t('welcome.how_subtitle')}
                            </p>
                        </div>

                        <div className="relative grid w-full max-w-5xl gap-10 md:grid-cols-3">
                            {/* Decorative line connecting steps */}
                            <div className="absolute top-[44px] right-[15%] left-[15%] -z-10 hidden h-0.5 bg-gradient-to-r from-primary/10 via-primary/40 to-primary/10 md:block" />

                            {/* Step 1 */}
                            <div className="group flex flex-col items-center text-center">
                                <div className="mb-6 flex size-16 items-center justify-center rounded-2xl border border-border bg-card text-xl font-bold text-primary shadow-xs transition-all duration-300 group-hover:scale-105 group-hover:border-primary/50 group-hover:shadow-md">
                                    1
                                </div>
                                <h3 className="mb-3 text-xl font-bold">
                                    {t('welcome.how.step1.title')}
                                </h3>
                                <p className="max-w-xs text-sm leading-relaxed text-muted-foreground">
                                    {t('welcome.how.step1.desc')}
                                </p>
                            </div>

                            {/* Step 2 */}
                            <div className="group flex flex-col items-center text-center">
                                <div className="mb-6 flex size-16 items-center justify-center rounded-2xl border border-border bg-card text-xl font-bold text-primary shadow-xs transition-all duration-300 group-hover:scale-105 group-hover:border-primary/50 group-hover:shadow-md">
                                    2
                                </div>
                                <h3 className="mb-3 text-xl font-bold">
                                    {t('welcome.how.step2.title')}
                                </h3>
                                <p className="max-w-xs text-sm leading-relaxed text-muted-foreground">
                                    {t('welcome.how.step2.desc')}
                                </p>
                            </div>

                            {/* Step 3 */}
                            <div className="group flex flex-col items-center text-center">
                                <div className="mb-6 flex size-16 items-center justify-center rounded-2xl border border-border bg-card text-xl font-bold text-primary shadow-xs transition-all duration-300 group-hover:scale-105 group-hover:border-primary/50 group-hover:shadow-md">
                                    3
                                </div>
                                <h3 className="mb-3 text-xl font-bold">
                                    {t('welcome.how.step3.title')}
                                </h3>
                                <p className="max-w-xs text-sm leading-relaxed text-muted-foreground">
                                    {t('welcome.how.step3.desc')}
                                </p>
                            </div>
                        </div>
                    </div>
                </section>

                {/* Pricing Plans Section */}
                <section
                    id="pricing"
                    className="w-full py-20 transition-colors duration-300 lg:py-28"
                >
                    <div className="mx-auto flex max-w-7xl flex-col items-center px-6">
                        <div className="mb-12 max-w-3xl text-center">
                            <span className="mb-2 block text-xs font-bold tracking-wider text-primary uppercase">
                                باقات اشتراك مرنة
                            </span>
                            <h2 className="mb-4 text-3xl font-extrabold tracking-tight sm:text-5xl">
                                {t('welcome.pricing_title')}
                            </h2>
                            <p className="text-lg text-muted-foreground">
                                {t('welcome.pricing_subtitle')}
                            </p>
                        </div>

                        {/* Billing Cycle Switch Tabs */}
                        <div className="mb-16 flex items-center gap-1.5 rounded-2xl border border-border/80 bg-card/60 p-1 backdrop-blur-md">
                            {[
                                { val: 1, label: 'شهرياً' },
                                { val: 3, label: '3 أشهر', disc: '5%' },
                                { val: 6, label: '6 أشهر', disc: '10%' },
                                { val: 12, label: '12 شهراً', disc: '20%' },
                            ].map((tab) => (
                                <button
                                    key={tab.val}
                                    onClick={() =>
                                        setBillingCycle(
                                            tab.val as 1 | 3 | 6 | 12,
                                        )
                                    }
                                    className={`relative rounded-xl px-4 py-2 text-xs font-bold transition-all duration-200 active:scale-95 sm:text-sm ${
                                        billingCycle === tab.val
                                            ? 'bg-primary text-primary-foreground shadow-sm'
                                            : 'text-muted-foreground hover:bg-muted/40 hover:text-foreground'
                                    }`}
                                >
                                    <span>{tab.label}</span>
                                    {tab.disc && (
                                        <span
                                            className={`ms-1.5 rounded px-1 py-0.5 text-[8px] font-black sm:text-[9px] ${
                                                billingCycle === tab.val
                                                    ? 'bg-white/20 text-white'
                                                    : 'bg-primary/10 text-primary'
                                            }`}
                                        >
                                            -{tab.disc}
                                        </span>
                                    )}
                                </button>
                            ))}
                        </div>

                        {/* Pricing Cards Grid */}
                        <div className="mb-16 grid w-full items-stretch gap-8 md:grid-cols-2 lg:grid-cols-4">
                            {plans.map((plan) => {
                                const currentPrice = calculatePrice(
                                    plan.baseMonthly,
                                    billingCycle,
                                );
                                const isBestValue = plan.slug === 'business';

                                return (
                                    <div
                                        key={plan.slug}
                                        className={`relative flex flex-col justify-between rounded-3xl border border-border/50 bg-card p-8 transition-all duration-300 hover:shadow-lg ${plan.borderColor}`}
                                    >
                                        {/* Badge top right/left */}
                                        {plan.badge && (
                                            <span
                                                className={`absolute -top-3.5 ${isRtl ? 'left-6' : 'right-6'} rounded-full bg-primary px-3 py-1 text-[10px] font-black tracking-wider text-primary-foreground uppercase shadow-xs`}
                                            >
                                                {plan.badge}
                                            </span>
                                        )}

                                        <div>
                                            <div className="mb-4 flex items-center gap-2">
                                                <h3 className="text-xl font-bold">
                                                    {plan.name}
                                                </h3>
                                            </div>

                                            <div className="mb-6">
                                                <span className="text-4xl font-black text-foreground tabular-nums sm:text-5xl">
                                                    {currentPrice.toLocaleString()}
                                                </span>
                                                <span className="ms-1 text-xs font-bold text-muted-foreground">
                                                    {isRtl ? 'ل.س' : 'SYP'}{' '}
                                                    {billingCycle > 1
                                                        ? isRtl
                                                            ? ` / ${billingCycle} أشهر`
                                                            : ` / ${billingCycle} months`
                                                        : isRtl
                                                          ? ' / شهرياً'
                                                          : ' / month'}
                                                </span>
                                            </div>

                                            <p className="mb-6 text-xs font-bold text-primary">
                                                {t(
                                                    'welcome.pricing.channel_limit',
                                                ).replace(
                                                    ':count',
                                                    plan.maxPages.toString(),
                                                )}
                                            </p>

                                            <div className="my-6 border-t border-border/40" />

                                            <ul className="mb-8 space-y-3.5">
                                                <li className="flex items-start gap-2.5 text-start text-sm">
                                                    <div className="flex size-5 shrink-0 items-center justify-center rounded-full bg-primary/10 text-primary">
                                                        <Check className="size-3" />
                                                    </div>
                                                    <span className="text-xs leading-relaxed text-muted-foreground">
                                                        {t(plan.featuresKey)}
                                                    </span>
                                                </li>
                                                <li className="flex items-start gap-2.5 text-start text-sm">
                                                    <div className="flex size-5 shrink-0 items-center justify-center rounded-full bg-primary/10 text-primary">
                                                        <Check className="size-3" />
                                                    </div>
                                                    <span className="text-xs text-muted-foreground">
                                                        ربط مباشر وآمن Meta
                                                        Business
                                                    </span>
                                                </li>
                                                <li className="flex items-start gap-2.5 text-start text-sm">
                                                    <div className="flex size-5 shrink-0 items-center justify-center rounded-full bg-primary/10 text-primary">
                                                        <Check className="size-3" />
                                                    </div>
                                                    <span className="text-xs text-muted-foreground">
                                                        دعم فني كامل عبر المنصة
                                                    </span>
                                                </li>
                                            </ul>
                                        </div>

                                        <Link
                                            href={register()}
                                            className={`inline-flex h-11 items-center justify-center rounded-xl text-xs font-bold shadow-xs transition-all hover:scale-[1.01] active:scale-[0.99] ${
                                                isBestValue
                                                    ? 'bg-primary text-primary-foreground shadow-md hover:bg-primary/95'
                                                    : 'border border-border bg-card text-foreground hover:bg-muted/40'
                                            }`}
                                        >
                                            {t('welcome.pricing.btn_choose')}
                                        </Link>
                                    </div>
                                );
                            })}
                        </div>

                        {/* Manual billing note */}
                        <div className="flex w-full max-w-4xl flex-col items-center gap-4 rounded-3xl border border-border/40 bg-muted/30 p-6 text-start sm:flex-row">
                            <div className="flex size-12 shrink-0 items-center justify-center rounded-2xl bg-primary/10 text-primary">
                                <Shield className="size-6" />
                            </div>
                            <div className="space-y-1">
                                <h4 className="text-sm font-bold text-foreground">
                                    طريقة الدفع والتفعيل في بريق
                                </h4>
                                <p className="text-xs leading-relaxed text-muted-foreground">
                                    {t('welcome.pricing.manual_info')}
                                </p>
                            </div>
                        </div>
                    </div>
                </section>

                {/* FAQ Section */}
                <section
                    id="faq"
                    className="w-full border-t border-border/30 bg-card/25 py-20 transition-colors duration-300 lg:py-28"
                >
                    <div className="mx-auto flex max-w-3xl flex-col items-center px-6">
                        <div className="mb-16 max-w-2xl text-center">
                            <h2 className="mb-4 text-3xl font-extrabold tracking-tight sm:text-5xl">
                                {t('welcome.faq_title')}
                            </h2>
                            <p className="text-lg text-muted-foreground">
                                {t('welcome.faq_subtitle')}
                            </p>
                        </div>

                        {/* Accordion Questions List */}
                        <div className="w-full space-y-4">
                            {[1, 2, 3, 4, 5].map((num, i) => {
                                const isOpen = openFaq === i;
                                const question = t(`welcome.faq.q${num}`);
                                const answer = t(`welcome.faq.a${num}`);

                                return (
                                    <div
                                        key={num}
                                        className="overflow-hidden rounded-2xl border border-border/50 bg-card transition-all duration-200"
                                    >
                                        <button
                                            onClick={() => toggleFaq(i)}
                                            className="flex w-full items-center justify-between px-6 py-5 text-start text-sm font-bold text-foreground transition-colors hover:bg-muted/30 sm:text-base"
                                        >
                                            <span>{question}</span>
                                            <ChevronDown
                                                className={`size-4 text-muted-foreground transition-transform duration-300 ${isOpen ? 'rotate-180 text-primary' : ''}`}
                                            />
                                        </button>

                                        {/* Answer with smooth collapse logic */}
                                        <div
                                            className={`overflow-hidden transition-all duration-300 ease-in-out ${
                                                isOpen
                                                    ? 'max-h-72 border-t border-border/30'
                                                    : 'max-h-0'
                                            }`}
                                        >
                                            <p className="p-6 text-start text-sm leading-relaxed text-muted-foreground">
                                                {answer}
                                            </p>
                                        </div>
                                    </div>
                                );
                            })}
                        </div>
                    </div>
                </section>

                {/* Final Call to Action Section */}
                <section className="relative w-full overflow-hidden py-20 text-center lg:py-28">
                    <div className="absolute inset-0 -z-10 bg-primary/5" />
                    <div className="absolute top-1/2 left-1/2 -z-10 size-[350px] -translate-x-1/2 -translate-y-1/2 rounded-full bg-accent/10 blur-[80px]" />

                    <div className="mx-auto flex max-w-4xl flex-col items-center px-6">
                        <h2 className="mb-6 max-w-2xl text-3xl leading-tight font-black sm:text-5xl">
                            {t('welcome.cta_title')}
                        </h2>
                        <p className="mb-10 max-w-xl text-base leading-relaxed text-muted-foreground sm:text-lg">
                            {t('welcome.cta_subtitle')}
                        </p>
                        <Link
                            href={register()}
                            className="inline-flex h-13 items-center justify-center rounded-2xl bg-[linear-gradient(135deg,#4C1D95_0%,#7C3AED_58%,#B98CFF_100%)] px-8 text-base font-bold text-white shadow-lg shadow-purple-500/25 transition-all hover:-translate-y-0.5 hover:shadow-purple-500/35 active:scale-[0.98]"
                        >
                            <span>{t('welcome.cta_btn')}</span>
                            {isRtl ? (
                                <ArrowLeft className="ms-2.5 size-5" />
                            ) : (
                                <ArrowRight className="ms-2.5 size-5" />
                            )}
                        </Link>
                    </div>
                </section>

                {/* Footer */}
                <footer className="w-full border-t border-border/50 bg-card/45 py-12 transition-colors duration-300">
                    <div className="mx-auto flex max-w-7xl flex-col items-center justify-between gap-6 px-6 text-sm text-muted-foreground md:flex-row">
                        {/* Left Side: Logo & Brand Name */}
                        <div className="flex items-center gap-2.5">
                            <div className="flex aspect-square size-8 items-center justify-center overflow-hidden rounded-lg border border-border/80 bg-white p-0.5">
                                <img
                                    src="/image.png"
                                    alt={t('app.name')}
                                    className="size-full object-contain"
                                />
                            </div>
                            <span className="font-extrabold tracking-tight text-foreground">
                                {t('app.name')} - {t('app.tagline')}
                            </span>
                        </div>

                        {/* Copyright Info */}
                        <div className="text-xs sm:text-sm">
                            <span>
                                &copy; {new Date().getFullYear()}{' '}
                                {t('app.name')}. جميع الحقوق محفوظة.
                            </span>
                        </div>

                        {/* Right Side: Links */}
                        <div className="flex flex-wrap gap-4 sm:gap-6 font-semibold">
                            <a
                                href="/terms"
                                className="transition-colors hover:text-foreground"
                            >
                                شروط الخدمة
                            </a>
                            <a
                                href="/privacy"
                                className="transition-colors hover:text-foreground"
                            >
                                سياسة الخصوصية
                            </a>
                            <a
                                href="/data-deletion"
                                className="transition-colors hover:text-foreground"
                            >
                                حذف البيانات
                            </a>
                        </div>
                    </div>
                </footer>
            </div>
        </>
    );
}
