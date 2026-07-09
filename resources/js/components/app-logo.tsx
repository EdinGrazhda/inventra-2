import AppLogoIcon from '@/components/app-logo-icon';

export default function AppLogo() {
    return (
        <>
            <div className="flex h-10 w-28 items-center justify-start overflow-hidden">
                <AppLogoIcon className="h-10 w-28" />
            </div>
            <div className="sr-only ml-1 grid flex-1 text-left text-sm">
                <span className="mb-0.5 truncate leading-tight font-semibold">
                    Inventra
                </span>
                <span className="truncate text-xs text-muted-foreground">
                    Stock Control
                </span>
            </div>
        </>
    );
}
