import type { ComponentProps } from 'react';

export default function AppLogoIcon(props: ComponentProps<'img'>) {
    return (
        <img
            src="/image.png"
            alt="بريق"
            {...props}
            className={`rounded-md bg-white object-contain p-0.5 ${props.className ?? ''}`}
        />
    );
}
