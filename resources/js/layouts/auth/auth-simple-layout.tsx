import { Link } from '@inertiajs/react';
import { BarChart3, Boxes, ShieldCheck } from 'lucide-react';
import AppLogoIcon from '@/components/app-logo-icon';
import { home } from '@/routes';
import type { AuthLayoutProps } from '@/types';

export default function AuthSimpleLayout({
    children,
    title,
    description,
}: AuthLayoutProps) {
    const highlights = [
        { icon: Boxes, label: 'Live warehouse stock' },
        { icon: BarChart3, label: 'Sales and purchase visibility' },
        { icon: ShieldCheck, label: 'Role-based admin access' },
    ];

    return (
        <div className="grid min-h-svh bg-white text-slate-950 lg:grid-cols-[minmax(0,1fr)_480px]">
            <section className="relative hidden overflow-hidden border-r border-emerald-100 bg-emerald-700 text-white lg:block">
                <div className="absolute inset-0 bg-[radial-gradient(circle_at_20%_20%,rgba(255,255,255,0.2),transparent_28%),linear-gradient(135deg,rgba(5,150,105,0.9),rgba(6,95,70,1))]" />
                <div className="relative flex min-h-full flex-col justify-between p-12">
                    <div className="flex items-center gap-3">
                        <p className="font-serif text-3xl font-black tracking-wide text-white">
                            Inventra
                        </p>
                    </div>

                    <div className="max-w-xl space-y-8">
                        <div className="space-y-4">
                            <p className="text-sm font-medium uppercase tracking-[0.18em] text-emerald-100">
                                Stock management
                            </p>
                            <h1 className="max-w-lg text-5xl font-semibold leading-tight">
                                Know what is in stock, where it is, and what
                                needs attention.
                            </h1>
                            <p className="max-w-md text-base leading-7 text-emerald-50">
                                Manage products, suppliers, warehouses,
                                movements, purchases, sales, and reports from
                                one clean workspace.
                            </p>
                        </div>

                        <div className="grid max-w-lg gap-3">
                            {highlights.map(({ icon: Icon, label }) => (
                                <div
                                    key={label}
                                    className="flex items-center gap-3 rounded-md border border-white/15 bg-white/10 px-4 py-3"
                                >
                                    <Icon className="size-5" />
                                    <span className="text-sm font-medium">
                                        {label}
                                    </span>
                                </div>
                            ))}
                        </div>
                    </div>

                    <p className="text-sm text-emerald-100">
                        Built for teams that need accurate stock without
                        clutter.
                    </p>
                </div>
            </section>

            <section className="flex min-h-svh items-center justify-center bg-white p-6 md:p-10">
                <div className="w-full max-w-sm">
                    <div className="flex flex-col gap-8">
                        <div className="flex flex-col items-center gap-4">
                            <Link
                                href={home()}
                                className="flex flex-col items-center gap-2 font-medium"
                            >
                                <div className="mb-1 flex h-16 w-40 items-center justify-center overflow-hidden">
                                    <AppLogoIcon className="h-16 w-40" />
                                </div>
                                <span className="sr-only">{title}</span>
                            </Link>

                            <div className="space-y-2 text-center">
                                <h1 className="text-2xl font-semibold">
                                    {title}
                                </h1>
                                <p className="text-center text-sm text-muted-foreground">
                                    {description}
                                </p>
                            </div>
                        </div>
                        {children}
                    </div>
                </div>
            </section>
        </div>
    );
}
