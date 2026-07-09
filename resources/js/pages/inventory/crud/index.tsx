import { Head, Link, router, useForm } from '@inertiajs/react';
import { Edit, Eye, Plus, Search, Trash2 } from 'lucide-react';
import { FormEvent } from 'react';
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

type Column = {
    key: string;
    label: string;
    type?: 'boolean';
};

type Module = {
    name: string;
    singular: string;
    route: string;
    description: string;
    columns: Column[];
};

type RecordRow = {
    id: number;
    [key: string]: unknown;
};

type PaginatedRecords = {
    data: RecordRow[];
    links: { url: string | null; label: string; active: boolean }[];
    from: number | null;
    to: number | null;
    total: number;
};

type Props = {
    module: Module;
    records: PaginatedRecords;
    filters: {
        search?: string;
    };
};

export default function CrudIndex({ module, records, filters }: Props) {
    const { data, setData, get, processing } = useForm({
        search: filters.search ?? '',
    });

    function submit(event: FormEvent) {
        event.preventDefault();
        get(`/${module.route}`, {
            preserveState: true,
            preserveScroll: true,
        });
    }

    function destroy(record: RecordRow) {
        if (!window.confirm(`Delete this ${module.singular}?`)) {
            return;
        }

        router.delete(`/${module.route}/${record.id}`, {
            preserveScroll: true,
        });
    }

    function valueFor(record: RecordRow, column: Column) {
        const value = record[column.key];

        if (column.type === 'boolean') {
            return (
                <Badge
                    variant="secondary"
                    className={
                        value
                            ? 'rounded-sm bg-emerald-50 text-emerald-700'
                            : 'rounded-sm bg-slate-100 text-slate-600'
                    }
                >
                    {value ? 'Active' : 'Inactive'}
                </Badge>
            );
        }

        return value === null || value === undefined || value === ''
            ? '-'
            : String(value);
    }

    return (
        <>
            <Head title={module.name} />
            <div className="flex flex-1 flex-col gap-6 bg-white p-4 md:p-6">
                <div className="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                    <div>
                        <h1 className="text-2xl font-semibold tracking-tight">
                            {module.name}
                        </h1>
                        <p className="max-w-3xl text-sm text-muted-foreground">
                            {module.description}
                        </p>
                    </div>
                    <Button asChild className="bg-emerald-600 text-white hover:bg-emerald-700">
                        <Link href={`/${module.route}/create`}>
                            <Plus className="size-4" />
                            New {module.singular}
                        </Link>
                    </Button>
                </div>

                <Card className="rounded-md border-emerald-100 shadow-none">
                    <CardHeader className="gap-4">
                        <div>
                            <CardTitle>{module.name}</CardTitle>
                            <CardDescription>
                                Create, show, edit, delete, search, and manage records from the database.
                            </CardDescription>
                        </div>
                        <form onSubmit={submit} className="flex max-w-md gap-2">
                            <div className="relative flex-1">
                                <Search className="absolute left-3 top-1/2 size-4 -translate-y-1/2 text-muted-foreground" />
                                <Input
                                    value={data.search}
                                    onChange={(event) => setData('search', event.target.value)}
                                    className="pl-9"
                                    placeholder={`Search ${module.name.toLowerCase()}`}
                                />
                            </div>
                            <Button type="submit" variant="outline" disabled={processing}>
                                Search
                            </Button>
                        </form>
                    </CardHeader>
                    <CardContent className="overflow-x-auto">
                        <table className="w-full min-w-[760px] text-sm">
                            <thead>
                                <tr className="border-b text-left text-muted-foreground">
                                    {module.columns.map((column) => (
                                        <th key={column.key} className="py-3 pr-4 font-medium">
                                            {column.label}
                                        </th>
                                    ))}
                                    <th className="py-3 text-right font-medium">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                {records.data.length === 0 && (
                                    <tr>
                                        <td
                                            colSpan={module.columns.length + 1}
                                            className="py-10 text-center text-muted-foreground"
                                        >
                                            No {module.name.toLowerCase()} found.
                                        </td>
                                    </tr>
                                )}
                                {records.data.map((record) => (
                                    <tr key={record.id} className="border-b last:border-0">
                                        {module.columns.map((column) => (
                                            <td key={column.key} className="py-3 pr-4">
                                                {valueFor(record, column)}
                                            </td>
                                        ))}
                                        <td className="py-3">
                                            <div className="flex justify-end gap-1">
                                                <Button asChild variant="ghost" size="icon">
                                                    <Link href={`/${module.route}/${record.id}`} aria-label="Show">
                                                        <Eye className="size-4" />
                                                    </Link>
                                                </Button>
                                                <Button asChild variant="ghost" size="icon">
                                                    <Link href={`/${module.route}/${record.id}/edit`} aria-label="Edit">
                                                        <Edit className="size-4" />
                                                    </Link>
                                                </Button>
                                                <Button
                                                    variant="ghost"
                                                    size="icon"
                                                    className="text-red-600 hover:bg-red-50 hover:text-red-700"
                                                    onClick={() => destroy(record)}
                                                    aria-label="Delete"
                                                >
                                                    <Trash2 className="size-4" />
                                                </Button>
                                            </div>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>

                        <div className="mt-4 flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                            <p className="text-sm text-muted-foreground">
                                Showing {records.from ?? 0} to {records.to ?? 0} of {records.total}
                            </p>
                            <div className="flex flex-wrap gap-2">
                                {records.links.map((link, index) => (
                                    <Button
                                        key={`${link.label}-${index}`}
                                        asChild={Boolean(link.url)}
                                        variant={link.active ? 'default' : 'outline'}
                                        size="sm"
                                        disabled={!link.url}
                                        className={link.active ? 'bg-emerald-600 text-white' : ''}
                                    >
                                        {link.url ? (
                                            <Link
                                                href={link.url}
                                                dangerouslySetInnerHTML={{ __html: link.label }}
                                            />
                                        ) : (
                                            <span dangerouslySetInnerHTML={{ __html: link.label }} />
                                        )}
                                    </Button>
                                ))}
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </>
    );
}
