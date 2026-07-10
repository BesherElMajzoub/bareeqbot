import { Form, Head } from '@inertiajs/react';
import { Mail, LogIn } from 'lucide-react';
import InputError from '@/components/input-error';
import PasskeyVerify from '@/components/passkey-verify';
import PasswordInput from '@/components/password-input';
import TextLink from '@/components/text-link';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { useTranslations } from '@/hooks/use-translations';
import { register } from '@/routes';
import { store } from '@/routes/login';
import { request } from '@/routes/password';

type Props = {
    status?: string;
    canResetPassword: boolean;
};

export default function Login({ status, canResetPassword }: Props) {
    const { t } = useTranslations();

    return (
        <>
            <Head title={t('auth.login', 'Log in')} />

            <PasskeyVerify
                label={t('auth.passkey_sign_in', 'Sign in with a passkey')}
                loadingLabel={t(
                    'auth.passkey_authenticating',
                    'Authenticating...',
                )}
                separator={t(
                    'auth.passkey_separator',
                    'Or continue with email',
                )}
            />

            <Form
                {...store.form()}
                resetOnSuccess={['password']}
                className="flex flex-col gap-6"
            >
                {({ processing, errors }) => (
                    <>
                        <div className="grid gap-4">
                            <div className="grid gap-2">
                                <Label htmlFor="email">
                                    {t('auth.email', 'Email address')}
                                </Label>
                                <div className="relative">
                                    <Mail className="absolute start-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                                    <Input
                                        id="email"
                                        type="email"
                                        name="email"
                                        required
                                        autoFocus
                                        tabIndex={1}
                                        autoComplete="email"
                                        placeholder={t(
                                            'auth.email_placeholder',
                                            'email@example.com',
                                        )}
                                        className="ps-10"
                                    />
                                </div>
                                <InputError message={errors.email} />
                            </div>

                            <div className="grid gap-2">
                                <div className="flex items-center">
                                    <Label htmlFor="password">
                                        {t('auth.password', 'Password')}
                                    </Label>
                                    {canResetPassword && (
                                        <TextLink
                                            href={request()}
                                            className="ms-auto text-sm font-medium"
                                            tabIndex={5}
                                        >
                                            {t(
                                                'auth.forgot_password',
                                                'Forgot your password?',
                                            )}
                                        </TextLink>
                                    )}
                                </div>
                                <PasswordInput
                                    id="password"
                                    name="password"
                                    required
                                    tabIndex={2}
                                    autoComplete="current-password"
                                    placeholder={t(
                                        'auth.password_placeholder',
                                        'Password',
                                    )}
                                />
                                <InputError message={errors.password} />
                            </div>

                            <div className="flex items-center gap-2">
                                <Checkbox
                                    id="remember"
                                    name="remember"
                                    tabIndex={3}
                                />
                                <Label
                                    htmlFor="remember"
                                    className="cursor-pointer font-normal select-none"
                                >
                                    {t('auth.remember_me', 'Remember me')}
                                </Label>
                            </div>

                            <Button
                                type="submit"
                                className="mt-2 w-full gap-2 transition-all duration-200 hover:-translate-y-0.5"
                                tabIndex={4}
                                disabled={processing}
                                data-test="login-button"
                            >
                                {processing ? (
                                    <>
                                        <Spinner />
                                        {t('auth.logging_in', 'Logging in...')}
                                    </>
                                ) : (
                                    <>
                                        <LogIn className="h-4 w-4" />
                                        {t('auth.login', 'Log in')}
                                    </>
                                )}
                            </Button>
                        </div>

                        <div className="text-center text-sm text-muted-foreground">
                            {t('auth.no_account', "Don't have an account?")}{' '}
                            <TextLink href={register()} tabIndex={5}>
                                {t('auth.signup', 'Sign up')}
                            </TextLink>
                        </div>
                    </>
                )}
            </Form>

            {status && (
                <div className="mt-4 text-center text-sm font-medium text-green-600 dark:text-green-400">
                    {status}
                </div>
            )}
        </>
    );
}

Login.layout = {
    title: 'Log in to your account',
    description: 'Enter your email and password below to log in',
};
