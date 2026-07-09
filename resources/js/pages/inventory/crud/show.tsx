import { Head, Link, router } from '@inertiajs/react';
import { ArrowLeft, Edit, Trash2 } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';

type Column = {
    key: string;
    label: string;
    type?: 'boolean';
};

type Field = {
    name: string;
    label: string;
    type: string;
};

type Module = {
    name: string;
    singular: string;
    route: string;
    columns: Column[];
    fields: Field[];
};

type Props = {
    module: Module;
    record: Record<string, unknown> & { id: number };
};

export default function CrudShow({ module, record }: Props) {
    function destroy() {
        if (!window.confirm(`Delete this ${module.singular}?`)) {
            return;
        }

        router.delete(`/${module.route}/${record.id}`);
    }

    function renderValue(key: string) {
        const value = record[key];

        if (typeof value === 'boolean') {
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

    const fields = module.fields.filter((field) => field.type !== 'checkbox');

    return (
        <>
            <Head title={`${module.singular} details`} />
            <div className="flex flex-1 flex-col gap-6 bg-white p-4 md:p-6">
                <div className="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                    <div>
                        <h1 className="text-2xl font-semibold tracking-tight">
                            {module.singular} details
                        </h1>
                        <p className="text-sm text-muted-foreground">
                            Review this saved database record.
                        </p>
                    </div>
                    <div className="flex gap-2">
                        <Button asChild variant="outline">
                            <Link href={`/${module.route}`}>
                                <ArrowLeft className="size-4" />
                                Back
                            </Link>
                        </Button>
                        <Button asChild className="bg-emerald-600 text-white hover:bg-emerald-700">
                            <Link href={`/${module.route}/${record.id}/edit`}>
                                <Edit className="size-4" />
                                Edit
                            </Link>
                        </Button>
                        <Button
                            variant="outline"
                            className="border-red-200 text-red-600 hover:bg-red-50 hover:text-red-700"
                            onClick={destroy}
                        >
                            <Trash2 className="size-4" />
                            Delete
                        </Button>
                    </div>
                </div>

                <Card className="max-w-4xl rounded-md border-emerald-100 shadow-none">
                    <CardHeader>
                        <CardTitle>{module.name}</CardTitle>
                        <CardDescription>Record ID #{record.id}</CardDescription>
                    </CardHeader>
                    <CardContent className="grid gap-4 md:grid-cols-2">
                        {fields.map((field) => (
                            <div key={field.name} className="rounded-md border border-emerald-50 p-4">
                                <p className="text-xs font-medium uppercase text-muted-foreground">
                                    {field.label}
                                </p>
                                <div className="mt-2 text-sm font-medium">
                                    {renderValue(field.name)}
                                </div>
                            </div>
                        ))}
                    </CardContent>
                </Card>
            </div>
        </>
    );
}
