import { useTranslations } from '@/hooks/use-translations';
import AuthLayoutTemplate from '@/layouts/auth/auth-simple-layout';

export default function AuthLayout({
    title = '',
    description = '',
    children,
}: {
    title?: string;
    description?: string;
    children: React.ReactNode;
}) {
    const { t } = useTranslations();

    // Map default English static titles/descriptions to translation keys dynamically
    let translatedTitle = title;
    let translatedDescription = description;

    if (title === 'Log in' || title === 'Log in to your account') {
        translatedTitle = t('auth.login_title', 'تسجيل الدخول إلى حسابك');
        translatedDescription = t(
            'auth.login_description',
            'أدخل البريد الإلكتروني وكلمة المرور أدناه لتسجيل الدخول إلى حسابك',
        );
    } else if (title === 'Create an account') {
        translatedTitle = t('auth.register_title', 'إنشاء حساب جديد');
        translatedDescription = t(
            'auth.register_description',
            'أدخل بياناتك أدناه لإنشاء حسابك والبدء فوراً',
        );
    } else if (title === 'Forgot password') {
        translatedTitle = t('auth.forgot_password_title', 'نسيت كلمة المرور؟');
        translatedDescription = t(
            'auth.forgot_password_description',
            'أدخل بريدك الإلكتروني وسنرسل لك رابطاً لإعادة تعيين كلمة المرور',
        );
    } else if (title === 'Reset password') {
        translatedTitle = t(
            'auth.reset_password_title',
            'إعادة تعيين كلمة المرور',
        );
        translatedDescription = t(
            'auth.reset_password_description',
            'أدخل كلمة المرور الجديدة أدناه لتحديث حسابك',
        );
    } else if (title === 'Email verification') {
        translatedTitle = t(
            'auth.verify_email_title',
            'تأكيد البريد الإلكتروني',
        );
        translatedDescription = t(
            'auth.verify_email_description',
            'يرجى تأكيد بريدك الإلكتروني من خلال الرابط المرسل إليك',
        );
    } else if (title === 'Confirm password') {
        translatedTitle = t('auth.confirm_password_title', 'تأكيد كلمة المرور');
        translatedDescription = t(
            'auth.confirm_password_description',
            'يرجى تأكيد كلمة المرور الخاصة بك قبل المتابعة',
        );
    }

    return (
        <AuthLayoutTemplate
            title={translatedTitle}
            description={translatedDescription}
        >
            {children}
        </AuthLayoutTemplate>
    );
}
