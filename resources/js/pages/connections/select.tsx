import { Head, router } from '@inertiajs/react';
import { useState } from 'react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { useTranslations } from '@/hooks/use-translations';
import connections from '@/routes/connections';

type PageAsset = {
    id: string;
    name: string;
    access_token: string;
    platform: 'facebook';
    instagram_business_account_id: string | null;
    instagram_username: string | null;
};

type Props = {
    pages: PageAsset[];
};

type SelectedAsset = {
    id: string;
    name: string;
    access_token: string;
    platform: 'facebook' | 'instagram';
    instagram_business_account_id?: string | null;
    instagram_username?: string | null;
};

export default function ConnectionsSelect({ pages }: Props) {
    const { t } = useTranslations();
    const [selected, setSelected] = useState<SelectedAsset[]>([]);
    const [submitting, setSubmitting] = useState(false);

    const toggle = (asset: SelectedAsset) => {
        setSelected((prev) => {
            const exists = prev.some(
                (a) => a.id === asset.id && a.platform === asset.platform,
            );

            return exists
                ? prev.filter(
                      (a) =>
                          !(a.id === asset.id && a.platform === asset.platform),
                  )
                : [...prev, asset];
        });
    };

    const isChecked = (id: string, platform: string) =>
        selected.some((a) => a.id === id && a.platform === platform);

    const submit = () => {
        if (selected.length === 0) {
            return;
        }

        setSubmitting(true);
        router.post(
            connections.store().url,
            { selected_assets: selected },
            {
                onFinish: () => setSubmitting(false),
            },
        );
    };

    return (
        <>
            <Head title={t('connections.select_pages')} />
            <div className="mx-auto flex w-full max-w-2xl flex-1 flex-col gap-6 p-4">
                <h1 className="text-2xl font-semibold tracking-tight">
                    {t('connections.select_pages')}
                </h1>

                {pages.length === 0 ? (
                    <p className="text-sm text-muted-foreground">
                        {t('connections.no_connections')}
                    </p>
                ) : (
                    <div className="flex flex-col gap-3">
                        {pages.map((page) => (
                            <div key={page.id}>
                                {/* Facebook page row */}
                                <Card
                                    className="cursor-pointer"
                                    onClick={() =>
                                        toggle({
                                            id: page.id,
                                            name: page.name,
                                            access_token: page.access_token,
                                            platform: 'facebook',
                                        })
                                    }
                                >
                                    <CardHeader className="pb-2">
                                        <CardTitle className="flex items-center gap-3 text-base">
                                            <Checkbox
                                                checked={isChecked(
                                                    page.id,
                                                    'facebook',
                                                )}
                                                onCheckedChange={() =>
                                                    toggle({
                                                        id: page.id,
                                                        name: page.name,
                                                        access_token:
                                                            page.access_token,
                                                        platform: 'facebook',
                                                    })
                                                }
                                                onClick={(e) =>
                                                    e.stopPropagation()
                                                }
                                            />
                                            <span className="truncate">
                                                {page.name}
                                            </span>
                                            <Badge
                                                variant="secondary"
                                                className="shrink-0"
                                            >
                                                {t(
                                                    'connections.platform_facebook',
                                                )}
                                            </Badge>
                                        </CardTitle>
                                    </CardHeader>
                                </Card>

                                {/* Instagram linked account row (if any) */}
                                {page.instagram_business_account_id && (
                                    <Card
                                        className="ms-6 mt-2 cursor-pointer border-dashed"
                                        onClick={() =>
                                            toggle({
                                                id: page.instagram_business_account_id!,
                                                name:
                                                    page.instagram_username ??
                                                    page.name,
                                                access_token: page.access_token,
                                                platform: 'instagram',
                                                instagram_business_account_id:
                                                    page.instagram_business_account_id,
                                                instagram_username:
                                                    page.instagram_username,
                                            })
                                        }
                                    >
                                        <CardContent className="flex items-center gap-3 py-3">
                                            <Checkbox
                                                checked={isChecked(
                                                    page.instagram_business_account_id,
                                                    'instagram',
                                                )}
                                                onCheckedChange={() =>
                                                    toggle({
                                                        id: page.instagram_business_account_id!,
                                                        name:
                                                            page.instagram_username ??
                                                            page.name,
                                                        access_token:
                                                            page.access_token,
                                                        platform: 'instagram',
                                                        instagram_business_account_id:
                                                            page.instagram_business_account_id,
                                                        instagram_username:
                                                            page.instagram_username,
                                                    })
                                                }
                                                onClick={(e) =>
                                                    e.stopPropagation()
                                                }
                                            />
                                            <span className="text-sm">
                                                {page.instagram_username
                                                    ? `@${page.instagram_username}`
                                                    : page.instagram_business_account_id}
                                            </span>
                                            <Badge
                                                variant="outline"
                                                className="ms-auto shrink-0"
                                            >
                                                {t(
                                                    'connections.platform_instagram',
                                                )}
                                            </Badge>
                                        </CardContent>
                                    </Card>
                                )}
                            </div>
                        ))}
                    </div>
                )}

                <div className="flex items-center gap-3">
                    <Button
                        onClick={submit}
                        disabled={selected.length === 0 || submitting}
                        className="min-w-32"
                    >
                        {submitting ? '...' : t('connections.connect_selected')}
                    </Button>
                    <Button variant="ghost" asChild>
                        <a href={connections.index().url}>
                            {t('common.cancel')}
                        </a>
                    </Button>
                </div>
            </div>
        </>
    );
}

ConnectionsSelect.layout = {
    breadcrumbs: [
        { title: 'القنوات المربوطة', href: connections.index().url },
        { title: 'اختيار الصفحات', href: '#' },
    ],
};
