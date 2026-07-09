import InputError from '@/components/input-error';
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
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Head, Link, router, useForm } from '@inertiajs/react';
import {
    Minus,
    Plus,
    ReceiptText,
    Search,
    ShoppingCart,
    Trash2,
} from 'lucide-react';
import { FormEvent, useMemo, useState } from 'react';

type Product = {
    id: number;
    name: string;
    sku: string;
    barcode: string | null;
    unit_type: string;
    selling_price: string;
    stock_quantity: string;
    minimum_stock_quantity: string;
    is_active: boolean;
    status: 'available' | 'low' | 'out';
};

type CartItem = {
    product: Product;
    quantity: number;
};

type SaleItem = {
    id: number;
    product_name: string | null;
    product_sku: string | null;
    quantity: string;
    unit_price: string;
    subtotal: string;
};

type Sale = {
    id: number;
    sale_number: string;
    customer_name: string | null;
    warehouse_name: string | null;
    sale_date: string | null;
    total_amount: string;
    status: string;
    items: SaleItem[];
};

type Warehouse = {
    id: number;
    name: string;
};

type ProductLinks = {
    url: string | null;
    label: string;
    active: boolean;
};

type PaginatedProducts = {
    data: Product[];
    links: ProductLinks[];
    from: number | null;
    to: number | null;
    total: number;
};

type SaleForm = {
    warehouse_id: string;
    customer_name: string;
    customer_phone: string;
    notes: string;
    items: {
        product_id: number;
        quantity: number;
    }[];
};

type Props = {
    products: PaginatedProducts;
    recentSales: Sale[];
    warehouses: Warehouse[];
    filters: {
        search?: string;
    };
};

const moneyFormatter = new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: 'USD',
});

const numberFormatter = new Intl.NumberFormat('en-US', {
    maximumFractionDigits: 3,
});

function cartPayload(cart: CartItem[]) {
    return cart.map((item) => ({
        product_id: item.product.id,
        quantity: item.quantity,
    }));
}

function maxSellableQuantity(product: Product) {
    return Math.max(0, Math.floor(Number(product.stock_quantity)));
}

export default function SalesIndex({
    products,
    recentSales,
    warehouses,
    filters,
}: Props) {
    const [search, setSearch] = useState(filters.search ?? '');
    const [cart, setCart] = useState<CartItem[]>([]);

    const { data, setData, post, processing, errors, reset } =
        useForm<SaleForm>({
            warehouse_id: warehouses[0] ? String(warehouses[0].id) : '',
            customer_name: '',
            customer_phone: '',
            notes: '',
            items: [],
        });

    const total = useMemo(
        () =>
            cart.reduce(
                (sum, item) =>
                    sum + item.quantity * Number(item.product.selling_price),
                0,
            ),
        [cart],
    );

    const cartQuantity = cart.reduce((sum, item) => sum + item.quantity, 0);

    function syncCart(nextCart: CartItem[]) {
        setCart(nextCart);
        setData('items', cartPayload(nextCart));
    }

    function addProduct(product: Product) {
        const maxQuantity = maxSellableQuantity(product);

        if (maxQuantity < 1 || !product.is_active) {
            return;
        }

        const existingItem = cart.find(
            (item) => item.product.id === product.id,
        );

        if (existingItem) {
            changeQuantity(product.id, existingItem.quantity + 1);
            return;
        }

        syncCart([...cart, { product, quantity: 1 }]);
    }

    function changeQuantity(productId: number, nextQuantity: number) {
        const nextCart = cart.map((item) => {
            if (item.product.id !== productId) {
                return item;
            }

            const quantity = Math.min(
                Math.max(1, nextQuantity),
                maxSellableQuantity(item.product),
            );

            return { ...item, quantity };
        });

        syncCart(nextCart);
    }

    function removeProduct(productId: number) {
        syncCart(cart.filter((item) => item.product.id !== productId));
    }

    function clearCart() {
        syncCart([]);
    }

    function submit(event: FormEvent) {
        event.preventDefault();

        post('/sales', {
            preserveScroll: true,
            onSuccess: () => {
                clearCart();
                reset('customer_name', 'customer_phone', 'notes');
            },
        });
    }

    function searchProducts(event: FormEvent) {
        event.preventDefault();

        router.get(
            '/sales',
            { search },
            {
                preserveState: true,
                preserveScroll: true,
            },
        );
    }

    return (
        <>
            <Head title="Sales" />
            <div className="flex flex-1 flex-col gap-6 bg-white p-4 md:p-6">
                <div className="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                    <div>
                        <h1 className="text-2xl font-semibold tracking-tight">
                            Sales
                        </h1>
                        <p className="max-w-3xl text-sm text-muted-foreground">
                            Search products, add multiple items to the cart, and complete one customer sale.
                        </p>
                    </div>
                    <div className="grid grid-cols-2 gap-2 text-sm md:min-w-72">
                        <div className="rounded-md border border-emerald-100 p-3">
                            <p className="text-muted-foreground">Catalog</p>
                            <p className="font-semibold">
                                {products.total} products
                            </p>
                        </div>
                        <div className="rounded-md border border-emerald-100 p-3">
                            <p className="text-muted-foreground">Cart</p>
                            <p className="font-semibold">
                                {cartQuantity} items
                            </p>
                        </div>
                    </div>
                </div>

                <div className="grid gap-6 xl:grid-cols-[minmax(0,1fr)_420px]">
                    <div className="flex flex-col gap-4">
                        <Card className="rounded-md border-emerald-100 shadow-none">
                            <CardHeader className="gap-4">
                                <div>
                                    <CardTitle>Find Products</CardTitle>
                                    <CardDescription>
                                        Search by product name, SKU, or barcode. Only one page of results is loaded at a time.
                                    </CardDescription>
                                </div>
                                <form
                                    onSubmit={searchProducts}
                                    className="flex max-w-xl gap-2"
                                >
                                    <div className="relative flex-1">
                                        <Search className="absolute left-3 top-1/2 size-4 -translate-y-1/2 text-muted-foreground" />
                                        <Input
                                            value={search}
                                            onChange={(event) =>
                                                setSearch(event.target.value)
                                            }
                                            className="pl-9"
                                            placeholder="Search name, SKU, or barcode"
                                        />
                                    </div>
                                    <Button type="submit" variant="outline">
                                        Search
                                    </Button>
                                </form>
                            </CardHeader>
                            <CardContent className="overflow-x-auto">
                                <table className="w-full min-w-[820px] text-sm">
                                    <thead>
                                        <tr className="border-b text-left text-muted-foreground">
                                            <th className="py-3 pr-4 font-medium">
                                                Product
                                            </th>
                                            <th className="py-3 pr-4 font-medium">
                                                SKU
                                            </th>
                                            <th className="py-3 pr-4 font-medium">
                                                Stock
                                            </th>
                                            <th className="py-3 pr-4 font-medium">
                                                Price
                                            </th>
                                            <th className="py-3 pr-4 font-medium">
                                                Status
                                            </th>
                                            <th className="py-3 text-right font-medium">
                                                Action
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {products.data.length === 0 && (
                                            <tr>
                                                <td
                                                    colSpan={6}
                                                    className="py-10 text-center text-muted-foreground"
                                                >
                                                    No products found.
                                                </td>
                                            </tr>
                                        )}
                                        {products.data.map((product) => {
                                            const inCart = cart.find(
                                                (item) =>
                                                    item.product.id ===
                                                    product.id,
                                            );
                                            const disabled =
                                                product.status === 'out' ||
                                                !product.is_active ||
                                                (inCart?.quantity ?? 0) >=
                                                    maxSellableQuantity(
                                                        product,
                                                    );

                                            return (
                                                <tr
                                                    key={product.id}
                                                    className="border-b last:border-0"
                                                >
                                                    <td className="py-3 pr-4">
                                                        <p className="font-medium">
                                                            {product.name}
                                                        </p>
                                                        <p className="text-xs text-muted-foreground">
                                                            {product.barcode ??
                                                                'No barcode'}
                                                        </p>
                                                    </td>
                                                    <td className="py-3 pr-4">
                                                        {product.sku}
                                                    </td>
                                                    <td className="py-3 pr-4">
                                                        {numberFormatter.format(
                                                            Number(
                                                                product.stock_quantity,
                                                            ),
                                                        )}{' '}
                                                        {product.unit_type}
                                                    </td>
                                                    <td className="py-3 pr-4">
                                                        {moneyFormatter.format(
                                                            Number(
                                                                product.selling_price,
                                                            ),
                                                        )}
                                                    </td>
                                                    <td className="py-3 pr-4">
                                                        <Badge
                                                            variant="secondary"
                                                            className={
                                                                product.status ===
                                                                'out'
                                                                    ? 'rounded-sm bg-red-50 text-red-700'
                                                                    : product.status ===
                                                                        'low'
                                                                      ? 'rounded-sm bg-amber-50 text-amber-700'
                                                                      : 'rounded-sm bg-emerald-50 text-emerald-700'
                                                            }
                                                        >
                                                            {product.status ===
                                                            'out'
                                                                ? 'Out'
                                                                : product.status ===
                                                                    'low'
                                                                  ? 'Low'
                                                                  : 'Ready'}
                                                        </Badge>
                                                    </td>
                                                    <td className="py-3 text-right">
                                                        <Button
                                                            type="button"
                                                            size="sm"
                                                            variant="outline"
                                                            disabled={disabled}
                                                            onClick={() =>
                                                                addProduct(
                                                                    product,
                                                                )
                                                            }
                                                        >
                                                            <Plus className="size-4" />
                                                            Add
                                                        </Button>
                                                    </td>
                                                </tr>
                                            );
                                        })}
                                    </tbody>
                                </table>

                                <div className="mt-4 flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                                    <p className="text-sm text-muted-foreground">
                                        Showing {products.from ?? 0} to{' '}
                                        {products.to ?? 0} of {products.total}
                                    </p>
                                    <div className="flex flex-wrap gap-2">
                                        {products.links.map((link, index) => (
                                            <Button
                                                key={`${link.label}-${index}`}
                                                asChild={Boolean(link.url)}
                                                variant={
                                                    link.active
                                                        ? 'default'
                                                        : 'outline'
                                                }
                                                size="sm"
                                                disabled={!link.url}
                                                className={
                                                    link.active
                                                        ? 'bg-emerald-600 text-white'
                                                        : ''
                                                }
                                            >
                                                {link.url ? (
                                                    <Link
                                                        href={link.url}
                                                        dangerouslySetInnerHTML={{
                                                            __html: link.label,
                                                        }}
                                                    />
                                                ) : (
                                                    <span
                                                        dangerouslySetInnerHTML={{
                                                            __html: link.label,
                                                        }}
                                                    />
                                                )}
                                            </Button>
                                        ))}
                                    </div>
                                </div>
                            </CardContent>
                        </Card>

                        <Card className="rounded-md border-emerald-100 shadow-none">
                            <CardHeader>
                                <CardTitle>Recent Sales</CardTitle>
                                <CardDescription>
                                    Latest completed sales from checkout.
                                </CardDescription>
                            </CardHeader>
                            <CardContent className="overflow-x-auto">
                                <table className="w-full min-w-[760px] text-sm">
                                    <thead>
                                        <tr className="border-b text-left text-muted-foreground">
                                            <th className="py-3 pr-4 font-medium">
                                                Sale
                                            </th>
                                            <th className="py-3 pr-4 font-medium">
                                                Items
                                            </th>
                                            <th className="py-3 pr-4 font-medium">
                                                Customer
                                            </th>
                                            <th className="py-3 pr-4 font-medium">
                                                Warehouse
                                            </th>
                                            <th className="py-3 text-right font-medium">
                                                Total
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {recentSales.length === 0 && (
                                            <tr>
                                                <td
                                                    colSpan={5}
                                                    className="py-10 text-center text-muted-foreground"
                                                >
                                                    No sales recorded yet.
                                                </td>
                                            </tr>
                                        )}
                                        {recentSales.map((sale) => (
                                            <tr
                                                key={sale.id}
                                                className="border-b last:border-0"
                                            >
                                                <td className="py-3 pr-4 font-medium">
                                                    {sale.sale_number}
                                                </td>
                                                <td className="py-3 pr-4">
                                                    {sale.items.length}{' '}
                                                    {sale.items.length === 1
                                                        ? 'item'
                                                        : 'items'}
                                                </td>
                                                <td className="py-3 pr-4">
                                                    {sale.customer_name ??
                                                        'Walk-in'}
                                                </td>
                                                <td className="py-3 pr-4">
                                                    {sale.warehouse_name ?? '-'}
                                                </td>
                                                <td className="py-3 text-right font-medium">
                                                    {moneyFormatter.format(
                                                        Number(
                                                            sale.total_amount,
                                                        ),
                                                    )}
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </CardContent>
                        </Card>
                    </div>

                    <Card className="h-fit rounded-md border-emerald-100 shadow-none xl:sticky xl:top-6">
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2">
                                <ShoppingCart className="size-5 text-emerald-700" />
                                Sale Cart
                            </CardTitle>
                            <CardDescription>
                                Add every product the customer is buying before checkout.
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <form onSubmit={submit} className="grid gap-5">
                                <div className="grid gap-3">
                                    {cart.length === 0 && (
                                        <div className="rounded-md border border-dashed border-emerald-200 p-5 text-center text-sm text-muted-foreground">
                                            Search and add products to start a sale.
                                        </div>
                                    )}

                                    {cart.map((item, index) => (
                                        <div
                                            key={item.product.id}
                                            className="rounded-md border border-emerald-100 p-3"
                                        >
                                            <div className="flex items-start justify-between gap-3">
                                                <div>
                                                    <p className="font-medium">
                                                        {item.product.name}
                                                    </p>
                                                    <p className="text-xs text-muted-foreground">
                                                        {item.product.sku}
                                                    </p>
                                                </div>
                                                <Button
                                                    type="button"
                                                    variant="ghost"
                                                    size="icon"
                                                    className="text-red-600 hover:bg-red-50 hover:text-red-700"
                                                    onClick={() =>
                                                        removeProduct(
                                                            item.product.id,
                                                        )
                                                    }
                                                    aria-label="Remove item"
                                                >
                                                    <Trash2 className="size-4" />
                                                </Button>
                                            </div>

                                            <div className="mt-3 grid grid-cols-[36px_1fr_36px] gap-2">
                                                <Button
                                                    type="button"
                                                    variant="outline"
                                                    size="icon"
                                                    onClick={() =>
                                                        changeQuantity(
                                                            item.product.id,
                                                            item.quantity - 1,
                                                        )
                                                    }
                                                >
                                                    <Minus className="size-4" />
                                                </Button>
                                                <Input
                                                    type="number"
                                                    min="1"
                                                    step="1"
                                                    value={item.quantity}
                                                    onChange={(event) =>
                                                        changeQuantity(
                                                            item.product.id,
                                                            Number(
                                                                event.target
                                                                    .value,
                                                            ),
                                                        )
                                                    }
                                                />
                                                <Button
                                                    type="button"
                                                    variant="outline"
                                                    size="icon"
                                                    onClick={() =>
                                                        changeQuantity(
                                                            item.product.id,
                                                            item.quantity + 1,
                                                        )
                                                    }
                                                >
                                                    <Plus className="size-4" />
                                                </Button>
                                            </div>

                                            <div className="mt-3 flex justify-between text-sm">
                                                <span className="text-muted-foreground">
                                                    {moneyFormatter.format(
                                                        Number(
                                                            item.product
                                                                .selling_price,
                                                        ),
                                                    )}{' '}
                                                    each
                                                </span>
                                                <span className="font-medium">
                                                    {moneyFormatter.format(
                                                        item.quantity *
                                                            Number(
                                                                item.product
                                                                    .selling_price,
                                                            ),
                                                    )}
                                                </span>
                                            </div>
                                            <InputError
                                                message={
                                                    errors[
                                                        `items.${index}.quantity`
                                                    ]
                                                }
                                            />
                                        </div>
                                    ))}
                                </div>

                                <InputError message={errors.items} />

                                <div className="grid gap-2">
                                    <Label>Warehouse</Label>
                                    <Select
                                        value={data.warehouse_id}
                                        onValueChange={(value) =>
                                            setData('warehouse_id', value)
                                        }
                                    >
                                        <SelectTrigger className="w-full">
                                            <SelectValue placeholder="Select warehouse" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {warehouses.map((warehouse) => (
                                                <SelectItem
                                                    key={warehouse.id}
                                                    value={String(
                                                        warehouse.id,
                                                    )}
                                                >
                                                    {warehouse.name}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    <InputError message={errors.warehouse_id} />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="customer_name">
                                        Customer name
                                    </Label>
                                    <Input
                                        id="customer_name"
                                        value={data.customer_name}
                                        onChange={(event) =>
                                            setData(
                                                'customer_name',
                                                event.target.value,
                                            )
                                        }
                                        placeholder="Walk-in customer"
                                    />
                                    <InputError message={errors.customer_name} />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="customer_phone">
                                        Customer phone
                                    </Label>
                                    <Input
                                        id="customer_phone"
                                        value={data.customer_phone}
                                        onChange={(event) =>
                                            setData(
                                                'customer_phone',
                                                event.target.value,
                                            )
                                        }
                                    />
                                    <InputError message={errors.customer_phone} />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="notes">Notes</Label>
                                    <textarea
                                        id="notes"
                                        value={data.notes}
                                        onChange={(event) =>
                                            setData('notes', event.target.value)
                                        }
                                        className="min-h-20 rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                                    />
                                    <InputError message={errors.notes} />
                                </div>

                                <div className="rounded-md bg-slate-50 p-4">
                                    <div className="flex justify-between text-sm">
                                        <span className="text-muted-foreground">
                                            Cart items
                                        </span>
                                        <span>{cartQuantity}</span>
                                    </div>
                                    <div className="mt-2 flex justify-between text-lg font-semibold">
                                        <span>Total</span>
                                        <span>
                                            {moneyFormatter.format(total)}
                                        </span>
                                    </div>
                                </div>

                                <div className="grid grid-cols-[1fr_auto] gap-2">
                                    <Button
                                        type="submit"
                                        disabled={
                                            processing || data.items.length === 0
                                        }
                                        className="bg-emerald-600 text-white hover:bg-emerald-700"
                                    >
                                        <ReceiptText className="size-4" />
                                        Complete sale
                                    </Button>
                                    <Button
                                        type="button"
                                        variant="outline"
                                        disabled={cart.length === 0}
                                        onClick={clearCart}
                                    >
                                        Clear
                                    </Button>
                                </div>
                            </form>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </>
    );
}
