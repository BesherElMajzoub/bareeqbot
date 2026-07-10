import { Link } from '@inertiajs/react';
import AppLogoIcon from '@/components/app-logo-icon';
import { useTranslations } from '@/hooks/use-translations';
import { home } from '@/routes';
import type { AuthLayoutProps } from '@/types';

export default function AuthSimpleLayout({
    children,
    title,
    description,
}: AuthLayoutProps) {
    const { direction } = useTranslations();

    return (
        <div
            className="relative flex min-h-svh flex-col items-center justify-center gap-6 overflow-hidden bg-background dotted-bg p-6 md:p-10"
            dir={direction}
        >
            {/* Background Glow Blobs */}
            <div className="pointer-events-none absolute -top-40 -left-40 h-96 w-96 rounded-full bg-primary/10 blur-3xl dark:bg-primary/20" />
            <div className="pointer-events-none absolute -right-40 -bottom-40 h-96 w-96 rounded-full bg-primary/10 blur-3xl dark:bg-primary/20" />

            <div className="relative z-10 w-full max-w-md animate-fade-up">
                <div className="flex flex-col gap-6">
                    {/* Glassmorphic Card */}
                    <div className="rounded-2xl border border-border/50 bg-card/65 p-8 shadow-soft backdrop-blur-xl glass-card transition-all duration-300 md:p-10">
                        {/* Logo & Header */}
                        <div className="mb-6 flex flex-col items-center gap-4">
                            <Link
                                href={home()}
                                className="flex flex-col items-center gap-2 font-medium"
                            >
                                <div className="mb-1 flex h-14 w-14 items-center justify-center rounded-xl border border-primary/20 bg-white/5 p-1.5 shadow-sm transition-transform duration-300 hover:scale-105">
                                    <AppLogoIcon className="size-full fill-current text-[var(--foreground)] dark:text-white" />
                                </div>
                                <span className="sr-only">{title}</span>
                            </Link>

                            <div className="space-y-1.5 text-center">
                                <h1 className="font-sans text-2xl font-bold tracking-tight text-foreground">
                                    {title}
                                </h1>
                                <p className="text-center text-sm leading-relaxed text-muted-foreground">
                                    {description}
                                </p>
                            </div>
                        </div>

                        {/* Form / Content */}
                        {children}
                    </div>
                </div>
            </div>
        </div>
    );
}
