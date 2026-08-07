import { router, usePage } from '@inertiajs/react';
import { Globe, Moon, Sun, Laptop } from 'lucide-react';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { useAppearance } from '@/hooks/use-appearance';

export function HeaderControls() {
    const { locale } = usePage().props as unknown as { locale: string };
    const { appearance, updateAppearance } = useAppearance();

    const switchLanguage = (newLocale: string) => {
        if (newLocale === locale) return;
        router.post('/locale', { locale: newLocale }, {
            preserveScroll: true,
            onSuccess: () => {
                window.location.reload();
            },
        });
    };

    return (
        <div className="flex items-center gap-1.5 ms-2">
            {/* Language Switcher */}
            <DropdownMenu>
                <DropdownMenuTrigger asChild>
                    <Button variant="outline" size="sm" className="h-8 gap-1.5 px-2.5 text-xs font-semibold">
                        <Globe className="h-3.5 w-3.5" />
                        <span>{locale === 'ar' ? 'العربية' : 'English'}</span>
                    </Button>
                </DropdownMenuTrigger>
                <DropdownMenuContent align="end" className="min-w-32">
                    <DropdownMenuItem
                        onClick={() => switchLanguage('ar')}
                        className={locale === 'ar' ? 'font-bold text-primary bg-primary/10' : ''}
                    >
                        🇸🇾 العربية (Arabic)
                    </DropdownMenuItem>
                    <DropdownMenuItem
                        onClick={() => switchLanguage('en')}
                        className={locale === 'en' ? 'font-bold text-primary bg-primary/10' : ''}
                    >
                        🇺🇸 English (الإنجليزية)
                    </DropdownMenuItem>
                </DropdownMenuContent>
            </DropdownMenu>

            {/* Theme Switcher */}
            <DropdownMenu>
                <DropdownMenuTrigger asChild>
                    <Button variant="outline" size="icon" className="h-8 w-8">
                        {appearance === 'dark' ? (
                            <Moon className="h-4 w-4 text-sky-400" />
                        ) : appearance === 'light' ? (
                            <Sun className="h-4 w-4 text-amber-500" />
                        ) : (
                            <Laptop className="h-4 w-4" />
                        )}
                        <span className="sr-only">Toggle Theme</span>
                    </Button>
                </DropdownMenuTrigger>
                <DropdownMenuContent align="end">
                    <DropdownMenuItem
                        onClick={() => updateAppearance('light')}
                        className={appearance === 'light' ? 'font-bold text-primary bg-primary/10' : ''}
                    >
                        <Sun className="me-2 h-4 w-4 text-amber-500" />
                        <span>فاتح (Light)</span>
                    </DropdownMenuItem>
                    <DropdownMenuItem
                        onClick={() => updateAppearance('dark')}
                        className={appearance === 'dark' ? 'font-bold text-primary bg-primary/10' : ''}
                    >
                        <Moon className="me-2 h-4 w-4 text-sky-400" />
                        <span>داكن (Dark)</span>
                    </DropdownMenuItem>
                    <DropdownMenuItem
                        onClick={() => updateAppearance('system')}
                        className={appearance === 'system' ? 'font-bold text-primary bg-primary/10' : ''}
                    >
                        <Laptop className="me-2 h-4 w-4" />
                        <span>تلقائي (System)</span>
                    </DropdownMenuItem>
                </DropdownMenuContent>
            </DropdownMenu>
        </div>
    );
}
