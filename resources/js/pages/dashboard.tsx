import { Head } from '@inertiajs/react';
import {
    AlertTriangle,
    ArrowDownToLine,
    ArrowUpFromLine,
    Boxes,
    DollarSign,
    Package,
    ShoppingCart,
    Warehouse,
} from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { dashboard } from '@/routes';

type DashboardStats = {
    totalProducts: number;
    lowStock: number;
    outOfStock: number;
    stockValue: number;
    totalSales: number;
    totalPurchases: number;
};

type WarehouseStock = {
    id: number;
    name: string;
    quantity: number;
    records: number;
    percentage: number;
};

type RecentMovement = {
    id: number;
    type: string;
    product: string;
    warehouse: string;
    quantity: number;
    unit: string;
};

type PriorityAlert = {
    id: number;
    product: string;
    warehouse: string;
    available: number;
    minimum: number;
    unit: string;
    status: 'Low stock' | 'Out of stock';
};

type DashboardProps = {
    stats: DashboardStats;
    warehouseStocks: WarehouseStock[];
    recentMovements: RecentMovement[];
    alerts: PriorityAlert[];
};

const numberFormatter = new Intl.NumberFormat();
const moneyFormatter = new Intl.NumberFormat(undefined, {
    style: 'currency',
    currency: 'USD',
    maximumFractionDigits: 0,
});

const formatQuantity = (value: number, unit: string) =>
    `${numberFormatter.format(Number(value))} ${unit}`;

const formatMovementQuantity = (movement: RecentMovement) => {
    const sign =
        movement.type === 'stock_in' || movement.type === 'adjustment'
            ? '+'
            : movement.type === 'stock_out'
              ? '-'
              : '';

    return `${sign}${formatQuantity(Math.abs(Number(movement.quantity)), movement.unit)}`;
};

export default function Dashboard({
    stats: dashboardStats,
    warehouseStocks,
    recentMovements,
    alerts,
}: DashboardProps) {
    const stats = [
        {
            label: 'Total products',
            value: numberFormatter.format(dashboardStats.totalProducts),
            icon: Package,
            tone: 'text-emerald-700',
        },
        {
            label: 'Low stock',
            value: numberFormatter.format(dashboardStats.lowStock),
            icon: AlertTriangle,
            tone: 'text-amber-600',
        },
        {
            label: 'Out of stock',
            value: numberFormatter.format(dashboardStats.outOfStock),
            icon: Boxes,
            tone: 'text-red-600',
        },
        {
            label: 'Stock value',
            value: moneyFormatter.format(dashboardStats.stockValue),
            icon: DollarSign,
            tone: 'text-emerald-700',
        },
        {
            label: 'Total sales',
            value: moneyFormatter.format(dashboardStats.totalSales),
            icon: ShoppingCart,
            tone: 'text-sky-700',
        },
        {
            label: 'Purchases',
            value: moneyFormatter.format(dashboardStats.totalPurchases),
            icon: ArrowDownToLine,
            tone: 'text-violet-700',
        },
    ];

    return (
        <>
            <Head title="Dashboard" />
            <div className="flex flex-1 flex-col gap-6 bg-white p-4 md:p-6">
                <div className="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                    <div>
                        <h1 className="text-2xl font-semibold tracking-tight">
                            Inventory Dashboard
                        </h1>
                        <p className="text-sm text-muted-foreground">
                            A clear view of products, warehouse stock,
                            purchases, sales, and movement activity.
                        </p>
                    </div>
                    <div className="flex gap-2">
                        <Button
                            variant="outline"
                            className="border-emerald-200 text-emerald-700 hover:bg-emerald-50"
                        >
                            <ArrowUpFromLine className="size-4" />
                            Export
                        </Button>
                        <Button className="bg-emerald-600 text-white hover:bg-emerald-700">
                            <Package className="size-4" />
                            Add product
                        </Button>
                    </div>
                </div>

                <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                    {stats.map((stat) => (
                        <Card
                            key={stat.label}
                            className="rounded-md border-emerald-100 shadow-none"
                        >
                            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                                <CardDescription>{stat.label}</CardDescription>
                                <stat.icon className={`size-5 ${stat.tone}`} />
                            </CardHeader>
                            <CardContent>
                                <div className="text-2xl font-semibold">
                                    {stat.value}
                                </div>
                            </CardContent>
                        </Card>
                    ))}
                </div>

                <div className="grid gap-4 xl:grid-cols-[minmax(0,1.2fr)_minmax(320px,0.8fr)]">
                    <Card className="rounded-md border-emerald-100 shadow-none">
                        <CardHeader>
                            <CardTitle>Warehouse Stock</CardTitle>
                            <CardDescription>
                                Current stock pressure across active locations.
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            {warehouseStocks.length ? (
                                warehouseStocks.map((warehouse) => (
                                    <div key={warehouse.id} className="space-y-2">
                                        <div className="flex items-center justify-between text-sm">
                                            <span className="font-medium">
                                                {warehouse.name}
                                            </span>
                                            <span className="text-muted-foreground">
                                                {numberFormatter.format(
                                                    warehouse.records,
                                                )}{' '}
                                                records
                                            </span>
                                        </div>
                                        <div className="h-2 rounded-full bg-emerald-50">
                                            <div
                                                className="h-2 rounded-full bg-emerald-600"
                                                style={{
                                                    width: `${warehouse.percentage}%`,
                                                }}
                                            />
                                        </div>
                                    </div>
                                ))
                            ) : (
                                <p className="text-sm text-muted-foreground">
                                    No warehouse stock has been recorded yet.
                                </p>
                            )}
                        </CardContent>
                    </Card>

                    <Card className="rounded-md border-emerald-100 shadow-none">
                        <CardHeader>
                            <CardTitle>Recent Movements</CardTitle>
                            <CardDescription>
                                Latest stock changes recorded by the system.
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-3">
                            {recentMovements.length ? (
                                recentMovements.map((movement) => (
                                    <div
                                        key={movement.id}
                                        className="flex items-center justify-between gap-3 rounded-md border border-emerald-50 px-3 py-2"
                                    >
                                        <div className="min-w-0">
                                            <div className="flex items-center gap-2">
                                                <Badge
                                                    variant="secondary"
                                                    className="rounded-sm bg-emerald-50 text-emerald-700"
                                                >
                                                    {movement.type}
                                                </Badge>
                                                <span className="truncate text-sm font-medium">
                                                    {movement.product}
                                                </span>
                                            </div>
                                            <p className="mt-1 text-xs text-muted-foreground">
                                                {movement.warehouse}
                                            </p>
                                        </div>
                                        <span className="text-sm font-semibold">
                                            {formatMovementQuantity(movement)}
                                        </span>
                                    </div>
                                ))
                            ) : (
                                <p className="text-sm text-muted-foreground">
                                    No stock movements have been recorded yet.
                                </p>
                            )}
                        </CardContent>
                    </Card>
                </div>

                <Card className="rounded-md border-emerald-100 shadow-none">
                    <CardHeader className="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
                        <div>
                            <CardTitle>Priority Alerts</CardTitle>
                            <CardDescription>
                                Products that need purchase, adjustment, or
                                review.
                            </CardDescription>
                        </div>
                        <Warehouse className="size-5 text-emerald-700" />
                    </CardHeader>
                    <CardContent className="overflow-x-auto">
                        <table className="w-full min-w-[680px] text-sm">
                            <thead>
                                <tr className="border-b text-left text-muted-foreground">
                                    <th className="py-3 font-medium">
                                        Product
                                    </th>
                                    <th className="py-3 font-medium">
                                        Warehouse
                                    </th>
                                    <th className="py-3 font-medium">
                                        Available
                                    </th>
                                    <th className="py-3 font-medium">
                                        Minimum
                                    </th>
                                    <th className="py-3 font-medium">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                {alerts.map((alert) => (
                                    <tr
                                        key={`${alert.id}-${alert.warehouse}`}
                                        className="border-b last:border-0"
                                    >
                                        <td className="py-3">{alert.product}</td>
                                        <td className="py-3">
                                            {alert.warehouse}
                                        </td>
                                        <td className="py-3">
                                            {formatQuantity(
                                                alert.available,
                                                alert.unit,
                                            )}
                                        </td>
                                        <td className="py-3">
                                            {formatQuantity(
                                                alert.minimum,
                                                alert.unit,
                                            )}
                                        </td>
                                        <td className="py-3">
                                            <Badge
                                                className={
                                                    alert.status ===
                                                    'Out of stock'
                                                        ? 'bg-red-600'
                                                        : 'bg-amber-500'
                                                }
                                            >
                                                {alert.status}
                                            </Badge>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                        {!alerts.length && (
                            <p className="py-6 text-sm text-muted-foreground">
                                No low-stock or out-of-stock products need
                                attention right now.
                            </p>
                        )}
                    </CardContent>
                </Card>
            </div>
        </>
    );
}

Dashboard.layout = {
    breadcrumbs: [
        {
            title: 'Dashboard',
            href: dashboard(),
        },
    ],
};
