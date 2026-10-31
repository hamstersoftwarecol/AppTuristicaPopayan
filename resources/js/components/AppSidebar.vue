<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import {
    Compass,
    LayoutGrid,
    Building2,
    Package,
    Users,
    FileText,
    ClipboardList,
    ShieldCheck,
    Heart,
    Bell,
} from '@lucide/vue';
import AppLogo from '@/components/AppLogo.vue';
import NavFooter from '@/components/NavFooter.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
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

const page = usePage();
const mainNavItems = computed<NavItem[]>(() => {
    const can = (permission: string) =>
        page.props.auth.permissions.includes(permission);
    const items: NavItem[] = [
        { title: 'Mi panel', href: dashboard(), icon: LayoutGrid },
        {
            title: `Notificaciones (${page.props.auth.unread_notifications || 0})`,
            href: '/notificaciones',
            icon: Bell,
        },
        { title: 'Mis reservas', href: '/mis-reservas', icon: ClipboardList },
        {
            title: 'Mis solicitudes',
            href: '/mis-solicitudes',
            icon: ClipboardList,
        },
        { title: 'Favoritos', href: '/explorar?favorites=1', icon: Heart },
    ];
    if (can('inventory.manage-own'))
        items.push({
            title: 'Mi organización',
            href: '/gestion/mi-organizacion',
            icon: Building2,
        });
    if (can('providers.manage'))
        items.push({
            title: 'Proveedores',
            href: '/gestion/proveedores',
            icon: Building2,
        });
    if (can('services.manage') || can('inventory.manage-own'))
        items.push({
            title: 'Inventario',
            href: '/gestion/servicios',
            icon: Package,
        });
    if (can('bookings.manage') || can('inventory.manage-own'))
        items.push({
            title: 'Gestionar reservas',
            href: '/gestion/reservas',
            icon: ClipboardList,
        });
    if (can('inquiries.manage'))
        items.push({
            title: 'Atender solicitudes',
            href: '/gestion/solicitudes',
            icon: ClipboardList,
        });
    if (can('content.manage'))
        items.push({
            title: 'Artículos',
            href: '/gestion/articulos',
            icon: FileText,
        });
    if (can('settings.manage'))
        items.push({
            title: 'Usuarios y roles',
            href: '/gestion/usuarios',
            icon: Users,
        });
    if (can('settings.manage'))
        items.push({
            title: 'Integraciones',
            href: '/gestion/integraciones',
            icon: Package,
        });
    if (can('payments.view'))
        items.push({
            title: 'Pagos de prueba',
            href: '/gestion/pagos',
            icon: ClipboardList,
        });
    if (can('audit.view'))
        items.push({
            title: 'Auditoría',
            href: '/gestion/auditoria',
            icon: ShieldCheck,
        });
    return items;
});

const footerNavItems: NavItem[] = [
    { title: 'Explorar Popayán', href: '/', icon: Compass },
];
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="dashboard()">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavMain :items="mainNavItems" />
        </SidebarContent>

        <SidebarFooter>
            <NavFooter :items="footerNavItems" />
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
