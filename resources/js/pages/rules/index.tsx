import { Head, router, useForm, useHttp } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import InputError from '@/components/input-error';
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
    auto_like_comment: boolean;
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

function VariableTemplateToolbar({ onInsert }: { onInsert: (text: string) => void }) {
    const variables = [
        { label: '{customer_name}', title: 'اسم العميل' },
        { label: '{product_name}', title: 'اسم المنتج' },
        { label: '{price}', title: 'السعر' },
        { label: '{phone_number}', title: 'رقم الهاتف' },
    ];

    const templates = [
        { label: '👋 مرحباً {customer_name}', text: 'أهلاً بك {customer_name}! 👋' },
        { label: '🏷️ سعر {product_name}', text: 'سعر {product_name} هو {price}.' },
        { label: '📞 للتواصل معنا', text: 'للتواصل معنا عبر الواتساب: {phone_number}' },
    ];

    return (
        <div className="flex flex-col gap-2 rounded-xl border border-primary/15 bg-primary/5 p-3 text-xs">
            <div className="flex flex-wrap items-center gap-1.5">
                <span className="font-bold text-muted-foreground me-1">💡 متغيرات سريعة:</span>
                {variables.map((v) => (
                    <button
                        key={v.label}
                        type="button"
                        onClick={() => onInsert(v.label)}
                        className="rounded-lg bg-card px-2 py-1 font-mono text-[11px] font-bold text-primary shadow-xs border border-primary/20 hover:bg-primary hover:text-white transition-colors"
                        title={v.title}
                    >
                        + {v.label}
                    </button>
                ))}
            </div>
            <div className="flex flex-wrap items-center gap-1.5">
                <span className="font-bold text-muted-foreground me-1">✨ قوالب جاهزة للرد:</span>
                {templates.map((t) => (
                    <button
                        key={t.label}
                        type="button"
                        onClick={() => onInsert(t.text)}
                        className="rounded-lg bg-card px-2 py-1 font-semibold text-[11px] text-foreground shadow-xs border border-border hover:border-primary hover:text-primary transition-colors"
                    >
                        {t.label}
                    </button>
                ))}
            </div>
        </div>
    );
}

/** Object URL for a locally-picked file, revoked automatically on change/unmount. */
function useObjectUrl(file: File | null): string | null {
    const [url, setUrl] = useState<string | null>(null);

    useEffect(() => {
        if (!file) {
            setUrl(null);
            return;
        }

        const objectUrl = URL.createObjectURL(file);
        setUrl(objectUrl);

        return () => URL.revokeObjectURL(objectUrl);
    }, [file]);

    return url;
}

function ImageAttachmentInput({
    previewUrl,
    onChange,
}: {
    previewUrl: string | null;
    onChange: (file: File | null) => void;
}) {
    const { t } = useTranslations();

    return (
        <div className="flex flex-col gap-1">
            <span className="text-xs font-medium text-muted-foreground">
                {t('rules.image')}
            </span>
            <input
                type="file"
                accept="image/*"
                onChange={(e) => onChange(e.target.files?.[0] ?? null)}
                className="text-xs"
            />
            <span className="text-xs text-muted-foreground">
                {t('rules.image_help')}
            </span>
            {previewUrl && (
                <img
                    src={previewUrl}
                    alt=""
                    className="mt-1 h-20 w-20 rounded-md border border-border object-cover"
                />
            )}
        </div>
    );
}

export default function RulesIndex({ rules: ruleList, connections }: Props) {
    const { t } = useTranslations();
    const { submit: httpSubmit } = useHttp();

    const [posts, setPosts] = useState<Post[]>([]);
    const [postsLoading, setPostsLoading] = useState(false);
    const [editingRuleId, setEditingRuleId] = useState<number | null>(null);

    const [publicReplyText, setPublicReplyText] = useState('');
    const [privateReplyText, setPrivateReplyText] = useState('');
    const [privateReplyImage, setPrivateReplyImage] = useState<File | null>(null);
    const [dmText, setDmText] = useState('');
    const [dmImage, setDmImage] = useState<File | null>(null);

    const privateReplyImageUrl = useObjectUrl(privateReplyImage);
    const dmImageUrl = useObjectUrl(dmImage);

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
        auto_like_comment: false,
        is_active: true,
        actions: [
            {
                action_type: 'public_reply',
                message_template: '',
                delay_seconds: 0,
            },
        ],
    });

    const startEdit = (rule: Rule) => {
        setEditingRuleId(rule.id);
        form.setData({
            channel_connection_id: rule.channel_connection?.id ?? connections[0]?.id ?? 0,
            name: rule.name,
            trigger_surface: rule.trigger_surface,
            target_scope: rule.target_scope,
            target_ref: rule.target_ref ?? '',
            match_type: rule.match_type,
            keyword: rule.keyword ?? '',
            case_sensitive: false,
            priority: rule.priority,
            auto_like_comment: rule.auto_like_comment ?? false,
            is_active: rule.is_active,
            actions: rule.actions.map((a) => ({
                action_type: a.action_type,
                message_template: a.message_template,
                delay_seconds: 0,
            })),
        });

        const pub = rule.actions.find((a) => a.action_type === 'public_reply');
        const priv = rule.actions.find((a) => a.action_type === 'private_reply');
        const dm = rule.actions.find((a) => a.action_type === 'dm');

        setPublicReplyText(pub ? pub.message_template : '');
        setPrivateReplyText(priv ? priv.message_template : '');
        setDmText(dm ? dm.message_template : '');
    };

    const cancelEdit = () => {
        setEditingRuleId(null);
        form.reset();
        resetActionInputs();
    };

    const surface = form.data.trigger_surface;

    const setSurface = (value: string) => {
        form.setData({
            ...form.data,
            trigger_surface: value,
            target_scope: 'all',
            target_ref: '',
        });
    };

    const resetActionInputs = () => {
        setPublicReplyText('');
        setPrivateReplyText('');
        setPrivateReplyImage(null);
        setDmText('');
        setDmImage(null);
    };

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        form.clearErrors();

        if (form.data.match_type !== 'any' && !form.data.keyword?.trim()) {
            form.setError('keyword', 'الكلمة المفتاحية مطلوبة لهذا النوع من المطابقة.');
            return;
        }

        const actions =
            surface === 'message'
                ? [
                      {
                          action_type: 'dm',
                          message_template: dmText,
                          delay_seconds: 0,
                          image: dmImage,
                      },
                  ]
                : [
                      ...(publicReplyText.trim()
                          ? [
                                {
                                    action_type: 'public_reply',
                                    message_template: publicReplyText,
                                    delay_seconds: 0,
                                },
                            ]
                          : []),
                      ...(privateReplyText.trim()
                          ? [
                                {
                                    action_type: 'private_reply',
                                    message_template: privateReplyText,
                                    delay_seconds: 0,
                                    image: privateReplyImage,
                                },
                            ]
                          : []),
                  ];

        if (actions.length === 0) {
            form.setError('actions', 'يرجى كتابة نص الرد العام أو الرسالة الخاصة على الأقل لإضافة القاعدة.');
            return;
        }

        form.setData('actions', actions);
        form.transform((data) => ({ ...data, actions }));

        if (editingRuleId) {
            form.put(rules.update(editingRuleId).url, {
                onSuccess: () => {
                    cancelEdit();
                },
            });
        } else {
            form.post(rules.store().url, {
                onSuccess: () => {
                    form.reset('name', 'keyword', 'target_ref', 'actions');
                    resetActionInputs();
                },
            });
        }
    };

    const toggleRule = (id: number) => router.patch(`/rules/${id}/toggle`, {}, { preserveScroll: true });
    const remove = (id: number) => router.delete(rules.destroy(id).url);

    const connectionId = form.data.channel_connection_id;
    const targetScope = form.data.target_scope;

    useEffect(() => {
        if (surface !== 'post_comment' || targetScope !== 'specific' || !connectionId) {
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
    }, [surface, targetScope, connectionId]);

    return (
        <>
            <Head title={t('rules.title')} />
            <div className="mx-auto flex w-full max-w-5xl flex-1 flex-col gap-6 p-4">
                <h1 className="text-2xl font-semibold tracking-tight">
                    {t('rules.title')}
                </h1>

                <Card>
                    <CardHeader>
                        <CardTitle>
                            {editingRuleId ? '✏️ تعديل قاعدة الرد المؤتمت' : t('rules.add')}
                        </CardTitle>
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
                                {Object.keys(form.errors).length > 0 && (
                                    <div className="md:col-span-2 rounded-xl border border-destructive/30 bg-destructive/10 p-3 text-xs font-bold text-destructive">
                                        ⚠️ تعذر حفظ القاعدة. يرجى مراجعة وتعديل الحقول التالية:
                                        <ul className="list-disc ms-4 mt-1 font-normal">
                                            {Object.entries(form.errors).map(([key, err]) => (
                                                <li key={key}>{err}</li>
                                            ))}
                                        </ul>
                                    </div>
                                )}

                                <label className="flex flex-col gap-1 text-sm md:col-span-2">
                                    {t('rules.trigger_surface')}
                                    <select
                                        className={selectClass}
                                        value={surface}
                                        onChange={(e) =>
                                            setSurface(e.target.value)
                                        }
                                    >
                                        <option value="post_comment">
                                            {t('surface.post_comment')}
                                        </option>
                                        <option value="message">
                                            {t('surface.message')}
                                        </option>
                                    </select>
                                </label>

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
                                    <InputError message={form.errors.channel_connection_id} />
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
                                    <InputError message={form.errors.name} />
                                </label>

                                {surface === 'post_comment' && (
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
                                )}

                                {surface === 'post_comment' &&
                                    form.data.target_scope === 'specific' && (
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
                                            <InputError message={form.errors.target_ref} />
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
                                        <InputError message={form.errors.keyword} />
                                    </label>
                                )}

                                {surface === 'post_comment' && (
                                    <div className="flex items-center gap-2 py-1 md:col-span-2">
                                        <input
                                            type="checkbox"
                                            id="auto_like_comment"
                                            checked={form.data.auto_like_comment}
                                            onChange={(e) =>
                                                form.setData('auto_like_comment', e.target.checked)
                                            }
                                            className="h-4 w-4 rounded border-gray-300 text-primary focus:ring-primary cursor-pointer"
                                        />
                                        <label
                                            htmlFor="auto_like_comment"
                                            className="text-xs font-bold text-foreground cursor-pointer flex items-center gap-1.5"
                                        >
                                            👍 الإعجاب بالتعليق تلقائياً عند الرد
                                        </label>
                                    </div>
                                )}

                                {surface === 'post_comment' ? (
                                    <div className="grid gap-4 md:col-span-2">
                                        <InputError message={form.errors.actions} />
                                        <label className="flex flex-col gap-2 text-sm">
                                            <span className="font-medium text-foreground">
                                                💬 {t('action.public_reply')} (رد عام على التعليق)
                                            </span>
                                            <VariableTemplateToolbar
                                                onInsert={(text) =>
                                                    setPublicReplyText((prev) =>
                                                        prev ? `${prev} ${text}` : text,
                                                    )
                                                }
                                            />
                                            <textarea
                                                className="min-h-20 w-full rounded-md border border-input bg-background p-3 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-ring"
                                                value={publicReplyText}
                                                onChange={(e) =>
                                                    setPublicReplyText(
                                                        e.target.value,
                                                    )
                                                }
                                                placeholder="أهلاً بك {customer_name}، تفضل التفاصيل..."
                                            />
                                            <span className="text-xs text-muted-foreground">
                                                سيتم نشر هذا النص كتعليق عام تحت تعليق العميل. (استخدم {"{{commenter_name}}"} لطباعة اسم المعلق تلقائياً).
                                            </span>
                                        </label>

                                        <label className="flex flex-col gap-2 text-sm">
                                            <span className="font-medium text-foreground">
                                                ✉️ {t('action.private_reply')} (رسالة خاصة على المسنجر)
                                            </span>
                                            <VariableTemplateToolbar
                                                onInsert={(text) =>
                                                    setPrivateReplyText((prev) =>
                                                        prev ? `${prev} ${text}` : text,
                                                    )
                                                }
                                            />
                                            <textarea
                                                className="min-h-20 w-full rounded-md border border-input bg-background p-3 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-ring"
                                                value={privateReplyText}
                                                onChange={(e) =>
                                                    setPrivateReplyText(
                                                        e.target.value,
                                                    )
                                                }
                                                placeholder="مرحباً {customer_name}، تفاصيل الأسعار والعروض هي..."
                                            />
                                            <span className="text-xs text-muted-foreground">
                                                سيتم إرسال هذا النص كرسالة خاصة للعميل في المسنجر. (استخدم {"{{commenter_name}}"} لطباعة اسم المعلق تلقائياً).
                                            </span>
                                            <ImageAttachmentInput
                                                previewUrl={privateReplyImageUrl}
                                                onChange={setPrivateReplyImage}
                                            />
                                        </label>
                                    </div>
                                ) : (
                                    <div className="grid gap-4 md:col-span-2">
                                        <label className="flex flex-col gap-2 text-sm">
                                            <span className="font-medium text-foreground">
                                                📩 {t('action.dm')} ({t('surface.message')})
                                            </span>
                                            <VariableTemplateToolbar
                                                onInsert={(text) =>
                                                    setDmText((prev) =>
                                                        prev ? `${prev} ${text}` : text,
                                                    )
                                                }
                                            />
                                            <textarea
                                                className="min-h-20 w-full rounded-md border border-input bg-background p-3 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-ring"
                                                value={dmText}
                                                onChange={(e) =>
                                                    setDmText(e.target.value)
                                                }
                                                placeholder="أهلاً بك، شكراً لتواصلك معنا. تفاصيل السعر هي..."
                                            />
                                            <span className="text-xs text-muted-foreground">
                                                سيتم إرسال هذا النص رداً على أي رسالة مسنجر مطابقة للكلمة المفتاحية.
                                            </span>
                                            <ImageAttachmentInput
                                                previewUrl={dmImageUrl}
                                                onChange={setDmImage}
                                            />
                                        </label>
                                    </div>
                                )}

                                {/* Live Preview Widget (المعاينة الحية للرد قبل الحفظ) */}
                                {(publicReplyText.trim() ||
                                    privateReplyText.trim() ||
                                    dmText.trim()) && (
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
                                            {publicReplyText.trim() && (
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
                                                                {publicReplyText.replace(/\{\{\s*commenter_name\s*\}\}/g, 'أحمد العلي')}
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            )}

                                            {/* Private Messenger DM Preview */}
                                            {privateReplyText.trim() && (
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
                                                                    {privateReplyText.replace(/\{\{\s*commenter_name\s*\}\}/g, 'أحمد العلي')}
                                                                </p>
                                                                {privateReplyImageUrl && (
                                                                    <img
                                                                        src={privateReplyImageUrl}
                                                                        alt=""
                                                                        className="mt-1 h-24 w-24 rounded-lg object-cover"
                                                                    />
                                                                )}
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            )}

                                            {/* Messenger keyword DM Preview */}
                                            {dmText.trim() && (
                                                <div className="rounded-xl border border-border/60 bg-card p-3.5 shadow-xs space-y-2">
                                                    <div className="flex items-center justify-between text-xs text-muted-foreground pb-2 border-b border-border/40">
                                                        <span className="font-semibold text-purple-600 flex items-center gap-1.5">
                                                            📩 معاينة الرد على رسالة المسنجر
                                                        </span>
                                                        <span className="size-2 rounded-full bg-purple-500 animate-pulse" />
                                                    </div>

                                                    <div className="pt-1">
                                                        <div className="flex items-start gap-2 text-xs">
                                                            <div className="size-7 rounded-full bg-purple-100 dark:bg-purple-950/50 text-purple-600 flex items-center justify-center font-bold text-[10px] shrink-0">
                                                                📩
                                                            </div>
                                                            <div className="bg-card border border-purple-500/20 rounded-2xl rounded-ss-none p-2.5 max-w-[90%] shadow-xs text-foreground space-y-1">
                                                                <span className="font-bold block text-[10px] text-purple-600">رسالة خاصة من صفحتك</span>
                                                                <p className="whitespace-pre-wrap leading-relaxed">
                                                                    {dmText}
                                                                </p>
                                                                {dmImageUrl && (
                                                                    <img
                                                                        src={dmImageUrl}
                                                                        alt=""
                                                                        className="mt-1 h-24 w-24 rounded-lg object-cover"
                                                                    />
                                                                )}
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            )}
                                        </div>
                                    </div>
                                )}

                                <div className="md:col-span-2 flex items-center gap-2">
                                    <Button
                                        type="submit"
                                        disabled={form.processing}
                                    >
                                        {editingRuleId ? 'حفظ التعديلات' : t('rules.add')}
                                    </Button>
                                    {editingRuleId && (
                                        <Button
                                            type="button"
                                            variant="outline"
                                            onClick={cancelEdit}
                                        >
                                            إلغاء التعديل
                                        </Button>
                                    )}
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
                                    {t('rules.trigger')}
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
                                <th className="p-3 text-end">الإجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            {ruleList.length === 0 ? (
                                <tr>
                                    <td
                                        colSpan={7}
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
                                        <td className="p-3 font-semibold">{rule.name}</td>
                                        <td className="p-3">
                                            {rule.channel_connection?.name ??
                                                '—'}
                                        </td>
                                        <td className="p-3">
                                            <Badge variant="outline">
                                                {t(
                                                    `surface.${rule.trigger_surface}`,
                                                )}
                                            </Badge>
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
                                                    : 'معطل'}
                                            </Badge>
                                        </td>
                                        <td className="p-3 text-end flex items-center justify-end gap-1.5">
                                            <Button
                                                size="sm"
                                                variant="outline"
                                                onClick={() => startEdit(rule)}
                                            >
                                                ✏️ تعديل
                                            </Button>
                                            <Button
                                                size="sm"
                                                variant={rule.is_active ? 'secondary' : 'default'}
                                                onClick={() => toggleRule(rule.id)}
                                            >
                                                {rule.is_active ? '⏸️ إيقاف' : '▶️ تفعيل'}
                                            </Button>
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
