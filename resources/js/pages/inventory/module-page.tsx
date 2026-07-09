import { Head } from '@inertiajs/react';
import { Plus, Search, SlidersHorizontal } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Input } from '@/components/ui/input';

type ModulePageProps = {
    module: string;
    description: string;
    stats: string[];
    columns: string[];
    rows: string[][];
};

export default function ModulePage({
    module,
    description,
    stats,
    columns,
    rows,
}: ModulePageProps) {
    const createLabel = module
        .replace('Product ', '')
        .replace('Users & Roles', 'user');

    return (
        <>
            <Head title={module} />
            <div className="flex flex-1 flex-col gap-6 bg-white p-4 md:p-6">
                <div className="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                    <div>
                        <h1 className="text-2xl font-semibold tracking-tight">
                            {module}
                        </h1>
                        <p className="max-w-3xl text-sm text-muted-foreground">
                            {description}
                        </p>
                    </div>
                    <Button className="w-full bg-emerald-600 text-white hover:bg-emerald-700 md:w-auto">
                        <Plus className="size-4" />
                        New {createLabel}
                    </Button>
                </div>

                <div className="grid gap-3 md:grid-cols-3">
                    {stats.map((stat) => (
                        <Card
                            key={stat}
                            className="rounded-md border-emerald-100 shadow-none"
                        >
                            <CardContent className="p-4">
                                <p className="text-sm font-medium text-emerald-700">
                                    {stat}
                                </p>
                            </CardContent>
                        </Card>
                    ))}
                </div>

                <Card className="rounded-md border-emerald-100 shadow-none">
                    <CardHeader className="gap-4">
                        <div className="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
                            <div>
                                <CardTitle>{module} List</CardTitle>
                                <CardDescription>
                                    Search, filter, review, and manage records.
                                </CardDescription>
                            </div>
                            <Button
                                variant="outline"
                                className="border-emerald-200 text-emerald-700 hover:bg-emerald-50"
                            >
                                <SlidersHorizontal className="size-4" />
                                Filters
                            </Button>
                        </div>
                        <div className="relative max-w-md">
                            <Search className="absolute left-3 top-1/2 size-4 -translate-y-1/2 text-muted-foreground" />
                            <Input
                                className="pl-9"
                                placeholder={`Search ${module.toLowerCase()}`}
                            />
                        </div>
                    </CardHeader>
                    <CardContent className="overflow-x-auto">
                        <table className="w-full min-w-[720px] text-sm">
                            <thead>
                                <tr className="border-b text-left text-muted-foreground">
                                    {columns.map((column) => (
                                        <th
                                            key={column}
                                            className="py-3 pr-4 font-medium"
                                        >
                                            {column}
                                        </th>
                                    ))}
                                    <th className="py-3 text-right font-medium">
                                        Action
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                {rows.map((row) => (
                                    <tr
                                        key={row.join('-')}
                                        className="border-b last:border-0"
                                    >
                                        {row.map((cell, index) => (
                                            <td
                                                key={`${cell}-${index}`}
                                                className="py-3 pr-4"
                                            >
                                                {index === row.length - 1 ? (
                                                    <Badge
                                                        variant="secondary"
                                                        className="rounded-sm bg-emerald-50 text-emerald-700"
                                                    >
                                                        {cell}
                                                    </Badge>
                                                ) : (
                                                    cell
                                                )}
                                            </td>
                                        ))}
                                        <td className="py-3 text-right">
                                            <Button
                                                variant="ghost"
                                                className="text-emerald-700 hover:bg-emerald-50"
                                            >
                                                View
                                            </Button>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </CardContent>
                </Card>
            </div>
        </>
    );
}
