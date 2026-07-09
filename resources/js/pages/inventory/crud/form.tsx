import { Head, Link, useForm } from '@inertiajs/react';
import { ArrowLeft, Save } from 'lucide-react';
import { FormEvent } from 'react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

type Field = {
    name: string;
    label: string;
    type:
        | 'text'
        | 'email'
        | 'number'
        | 'date'
        | 'password'
        | 'textarea'
        | 'checkbox'
        | 'select';
    required?: boolean;
    optionsKey?: string;
};

type Module = {
    name?: string;
    singular: string;
    route: string;
    fields: Field[];
};

type Option = {
    id?: number;
    value?: string;
    label?: string;
    name: string;
    sku?: string | null;
    company_name?: string | null;
};

type Props = {
    module: Module;
    record: Record<string, unknown> | null;
    options: Record<string, Option[]>;
};

export default function CrudForm({ module, record, options }: Props) {
    const initial = Object.fromEntries(
        module.fields.map((field) => [
            field.name,
            record?.[field.name] ?? (field.type === 'checkbox' ? true : ''),
        ]),
    );

    const { data, setData, post, put, processing, errors } = useForm(initial);
    const isEditing = Boolean(record?.id);

    function submit(event: FormEvent) {
        event.preventDefault();

        if (isEditing) {
            put(`/${module.route}/${record?.id}`);
            return;
        }

        post(`/${module.route}`);
    }

    return (
        <>
            <Head title={`${isEditing ? 'Edit' : 'Create'} ${module.singular}`} />
            <div className="flex flex-1 flex-col gap-6 bg-white p-4 md:p-6">
                <div className="flex items-center justify-between gap-3">
                    <div>
                        <h1 className="text-2xl font-semibold tracking-tight">
                            {isEditing ? 'Edit' : 'Create'} {module.singular}
                        </h1>
                        <p className="text-sm text-muted-foreground">
                            Fill the fields below and save the record.
                        </p>
                    </div>
                    <Button asChild variant="outline">
                        <Link href={`/${module.route}`}>
                            <ArrowLeft className="size-4" />
                            Back
                        </Link>
                    </Button>
                </div>

                <Card className="max-w-4xl rounded-md border-emerald-100 shadow-none">
                    <CardHeader>
                        <CardTitle>{module.name}</CardTitle>
                        <CardDescription>
                            Required fields are marked by the browser validation.
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <form onSubmit={submit} className="grid gap-5 md:grid-cols-2">
                            {module.fields.map((field) => (
                                <div
                                    key={field.name}
                                    className={field.type === 'textarea' ? 'grid gap-2 md:col-span-2' : 'grid gap-2'}
                                >
                                    {field.type !== 'checkbox' && (
                                        <Label htmlFor={field.name}>{field.label}</Label>
                                    )}

                                    {field.type === 'textarea' && (
                                        <textarea
                                            id={field.name}
                                            value={String(data[field.name] ?? '')}
                                            onChange={(event) => setData(field.name, event.target.value)}
                                            className="min-h-28 rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                                        />
                                    )}

                                    {['text', 'email', 'number', 'date', 'password'].includes(field.type) && (
                                        <Input
                                            id={field.name}
                                            type={field.type}
                                            required={field.required}
                                            step={field.type === 'number' ? '0.001' : undefined}
                                            value={String(data[field.name] ?? '')}
                                            onChange={(event) => setData(field.name, event.target.value)}
                                        />
                                    )}

                                    {field.type === 'select' && (
                                        <select
                                            id={field.name}
                                            required={field.required}
                                            value={String(data[field.name] ?? '')}
                                            onChange={(event) => setData(field.name, event.target.value)}
                                            className="h-9 rounded-md border border-input bg-transparent px-3 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                                        >
                                            <option value="">Select {field.label.toLowerCase()}</option>
                                            {(options[field.optionsKey ?? ''] ?? []).map((option) => (
                                                <option
                                                    key={option.id ?? option.value}
                                                    value={option.id ?? option.value}
                                                >
                                                    {option.label ||
                                                        option.company_name ||
                                                        (option.sku
                                                            ? `${option.name} (${option.sku})`
                                                            : option.name)}
                                                </option>
                                            ))}
                                        </select>
                                    )}

                                    {field.type === 'checkbox' && (
                                        <div className="flex items-center gap-3 pt-7">
                                            <Checkbox
                                                id={field.name}
                                                checked={Boolean(data[field.name])}
                                                onCheckedChange={(checked) => setData(field.name, Boolean(checked))}
                                            />
                                            <Label htmlFor={field.name}>{field.label}</Label>
                                        </div>
                                    )}

                                    <InputError message={errors[field.name]} />
                                </div>
                            ))}

                            <div className="flex gap-2 md:col-span-2">
                                <Button
                                    type="submit"
                                    disabled={processing}
                                    className="bg-emerald-600 text-white hover:bg-emerald-700"
                                >
                                    <Save className="size-4" />
                                    Save {module.singular}
                                </Button>
                                <Button asChild variant="outline">
                                    <Link href={`/${module.route}`}>Cancel</Link>
                                </Button>
                            </div>
                        </form>
                    </CardContent>
                </Card>
            </div>
        </>
    );
}
