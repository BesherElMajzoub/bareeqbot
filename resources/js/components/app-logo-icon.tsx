import type { ComponentProps } from 'react';

export default function AppLogoIcon(props: ComponentProps<'img'>) {
    return (
        <img
            src="/ChatGPT Image 8 مايو 2026، 12_03_15 ص.png"
            alt="بريق"
            {...props}
            className={`rounded-md bg-white object-contain p-0.5 ${props.className ?? ''}`}
        />
    );
}
