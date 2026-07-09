import type { ImgHTMLAttributes } from 'react';

export default function AppLogoIcon({
    alt = 'Inventra',
    ...props
}: ImgHTMLAttributes<HTMLImageElement>) {
    return (
        <img
            {...props}
            alt={alt}
            src="/inventra-logo.png"
            className={`object-contain ${props.className ?? ''}`}
        />
    );
}
