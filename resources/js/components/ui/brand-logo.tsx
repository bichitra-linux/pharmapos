import { cn } from '@/lib/utils';

export type BrandLogoSize = 'xs' | 'sm' | 'md' | 'lg' | 'xl';
export type BrandLogoVariant = 'full' | 'mark' | 'wordmark';

interface BrandLogoProps {
    size?: BrandLogoSize;
    variant?: BrandLogoVariant;
    className?: string;
    /** Override the alt text (e.g. include a tenant name) */
    alt?: string;
    /** Decorative mark — true hides the alt text for screen readers */
    decorative?: boolean;
}

const SIZE_TO_PX: Record<BrandLogoSize, number> = {
    xs: 20,
    sm: 28,
    md: 36,
    lg: 56,
    xl: 96,
};

const SIZE_TO_CLASS: Record<BrandLogoSize, string> = {
    xs: 'h-5',
    sm: 'h-7',
    md: 'h-9',
    lg: 'h-14',
    xl: 'h-24',
};

const SIZE_TO_TEXT: Record<BrandLogoSize, string> = {
    xs: 'text-sm',
    sm: 'text-base',
    md: 'text-lg',
    lg: 'text-2xl',
    xl: 'text-4xl',
};

const LOGO_SRC = '/pharmapos-logo.png';

export function BrandLogo({
    size = 'md',
    variant = 'full',
    className,
    alt = 'PharmaPOS',
    decorative = false,
}: BrandLogoProps) {
    const px = SIZE_TO_PX[size];
    const sizeClass = SIZE_TO_CLASS[size];
    const textClass = SIZE_TO_TEXT[size];
    const finalAlt = decorative ? '' : alt;

    if (variant === 'wordmark') {
        return (
            <span
                className={cn(
                    'inline-flex items-baseline font-extrabold tracking-tight leading-none',
                    textClass,
                    className,
                )}
            >
                <span className="text-brand-navy-600 dark:text-white">Pharma</span>
                <span className="text-brand-green-600 dark:text-brand-green-400">POS</span>
            </span>
        );
    }

    if (variant === 'mark') {
        return (
            <img
                src={LOGO_SRC}
                alt={finalAlt}
                width={px}
                height={px}
                className={cn('inline-block object-contain', sizeClass, className)}
                decoding="async"
            />
        );
    }

    // full = the complete lockup image (the PNG already contains icon + wordmark)
    return (
        <img
            src={LOGO_SRC}
            alt={finalAlt}
            width={px}
            height={px}
            className={cn('inline-block object-contain', sizeClass, className)}
            decoding="async"
        />
    );
}

export default BrandLogo;