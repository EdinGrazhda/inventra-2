import { Link, usePage } from '@inertiajs/react';
import {
    ArrowLeftRight,
    BarChart3,
    Boxes,
    Building2,
    ClipboardList,
    FolderTree,
    LayoutDashboard,
    Package,
    ReceiptText,
    ShoppingCart,
    Users,
    Warehouse,
} from 'lucide-react';
import AppLogo from '@/components/app-logo';
import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { dashboard } from '@/routes';
import type { NavItem } from '@/types';

const mainNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: dashboard(),
        icon: LayoutDashboard,
    },
    {
        title: 'Products',
        href: '/products',
        icon: Package,
        permission: 'products.access',
    },
    {
        title: 'Categories',
        href: '/categories',
        icon: FolderTree,
        permission: 'categories.access',
    },
    {
        title: 'Suppliers',
        href: '/suppliers',
        icon: Building2,
        permission: 'suppliers.access',
    },
    {
        title: 'Warehouses',
        href: '/warehouses',
        icon: Warehouse,
        permission: 'warehouses.access',
    },
];

const stockNavItems: NavItem[] = [
    {
        title: 'Product Stock',
        href: '/stock',
        icon: Boxes,
        permission: 'stock.access',
    },
    {
        title: 'Stock Movements',
        href: '/stock-movements',
        icon: ClipboardList,
        permission: 'stock_movements.access',
    },
    {
        title: 'Stock Transfers',
        href: '/stock-transfers',
        icon: ArrowLeftRight,
        permission: 'stock_transfers.access',
    },
];

const operationsNavItems: NavItem[] = [
    {
        title: 'Purchases',
        href: '/purchases',
        icon: ReceiptText,
        permission: 'purchases.access',
    },
    {
        title: 'Sales',
        href: '/sales',
        icon: ShoppingCart,
        permission: 'sales.access',
    },
    {
        title: 'Reports',
        href: '/reports',
        icon: BarChart3,
        permission: 'reports.access',
    },
    {
        title: 'Users & Roles',
        href: '/users-roles',
        icon: Users,
        permission: 'users_roles.access',
    },
];

export function AppSidebar() {
    const permissions = usePage().props.auth.permissions;
    const canAccess = (item: NavItem) => !item.permission || permissions.includes(item.permission);

    return (
        <Sidebar collapsible="icon" variant="inset">
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild>
                            <Link href={dashboard()} prefetch>
                                <AppLogo />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent>
                <NavMain items={mainNavItems.filter(canAccess)} label="Inventory" />
                <NavMain items={stockNavItems.filter(canAccess)} label="Stock Control" />
                <NavMain items={operationsNavItems.filter(canAccess)} label="Operations" />
            </SidebarContent>

            <SidebarFooter>
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
