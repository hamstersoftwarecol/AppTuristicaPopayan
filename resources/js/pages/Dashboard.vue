<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Compass, LockKeyhole, UserRound } from '@lucide/vue';
import { dashboard } from '@/routes';
import { edit as editProfile } from '@/routes/profile';
import { edit as editSecurity } from '@/routes/security';

defineProps<{
    role: string;
    permissions: string[];
    stats: {
        my_bookings: number;
        my_inquiries: number;
        my_favorites: number;
        providers: number | null;
        pending_inquiries: number | null;
        inventory: number | null;
    };
}>();
defineOptions({
    layout: { breadcrumbs: [{ title: 'Mi panel', href: dashboard() }] },
});
</script>

<template>
    <Head title="Mi panel" />
    <div class="flex flex-1 flex-col gap-8 p-6 lg:p-10">
        <div>
            <p class="text-sm text-muted-foreground">
                Popayán Turismo · <span class="capitalize">{{ role }}</span>
            </p>
            <h1 class="mt-3 text-3xl font-semibold tracking-tight">
                Hola, {{ $page.props.auth.user.name }}
            </h1>
            <p class="mt-3 text-muted-foreground">
                Este es tu espacio para preparar el próximo viaje.
            </p>
        </div>
        <div class="grid gap-5 md:grid-cols-3">
            <Link
                :href="editProfile()"
                class="rounded-xl border p-6 transition-colors hover:bg-muted"
                ><UserRound class="mb-5 size-6" />
                <h2 class="font-semibold">Tu perfil</h2>
                <p class="mt-2 text-sm text-muted-foreground">
                    Actualiza tu nombre y correo electrónico.
                </p></Link
            >
            <Link
                :href="editSecurity()"
                class="rounded-xl border p-6 transition-colors hover:bg-muted"
                ><LockKeyhole class="mb-5 size-6" />
                <h2 class="font-semibold">Seguridad</h2>
                <p class="mt-2 text-sm text-muted-foreground">
                    Configura tu contraseña y el doble factor.
                </p></Link
            >
            <Link
                href="/explorar"
                class="rounded-xl border p-6 transition-colors hover:bg-muted"
                ><Compass class="mb-5 size-6" />
                <h2 class="font-semibold">Explorar</h2>
                <p class="mt-2 text-sm text-muted-foreground">
                    Busca servicios y solicita una cotización.
                </p></Link
            >
        </div>
        <section>
            <h2 class="text-xl font-semibold">Tu actividad</h2>
            <div class="mt-5 grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                <Link href="/mis-reservas" class="rounded-xl border p-6"
                    ><p class="text-3xl font-semibold">
                        {{ stats.my_bookings }}
                    </p>
                    <h3 class="mt-2 text-sm text-muted-foreground">
                        Mis reservas confirmadas
                    </h3></Link
                >
                <Link href="/mis-solicitudes" class="rounded-xl border p-6"
                    ><p class="text-3xl font-semibold">
                        {{ stats.my_inquiries }}
                    </p>
                    <h3 class="mt-2 text-sm text-muted-foreground">
                        Mis solicitudes de cotización
                    </h3></Link
                >
                <Link href="/explorar?favorites=1" class="rounded-xl border p-6"
                    ><p class="text-3xl font-semibold">
                        {{ stats.my_favorites }}
                    </p>
                    <h3 class="mt-2 text-sm text-muted-foreground">
                        Servicios guardados
                    </h3></Link
                >
                <Link
                    v-if="stats.providers !== null"
                    href="/gestion/proveedores"
                    class="rounded-xl border p-6"
                    ><p class="text-3xl font-semibold">{{ stats.providers }}</p>
                    <h3 class="mt-2 text-sm text-muted-foreground">
                        Proveedores registrados
                    </h3></Link
                >
                <Link
                    v-if="stats.inventory !== null"
                    href="/gestion/servicios"
                    class="rounded-xl border p-6"
                    ><p class="text-3xl font-semibold">{{ stats.inventory }}</p>
                    <h3 class="mt-2 text-sm text-muted-foreground">
                        Servicios en inventario
                    </h3></Link
                >
                <Link
                    v-if="stats.pending_inquiries !== null"
                    href="/gestion/solicitudes"
                    class="rounded-xl border p-6"
                    ><p class="text-3xl font-semibold">
                        {{ stats.pending_inquiries }}
                    </p>
                    <h3 class="mt-2 text-sm text-muted-foreground">
                        Solicitudes por atender
                    </h3></Link
                >
            </div>
        </section>
        <section
            v-if="
                permissions.includes('providers.manage') ||
                permissions.includes('inventory.manage-own') ||
                permissions.includes('settings.manage')
            "
            class="rounded-xl border bg-muted/40 p-6"
        >
            <h2 class="text-lg font-semibold">Herramientas de trabajo</h2>
            <div class="mt-4 flex flex-wrap gap-3">
                <Link
                    v-if="permissions.includes('providers.manage')"
                    href="/gestion/proveedores"
                    class="action-primary"
                    >Administrar proveedores</Link
                >
                <Link
                    v-if="
                        permissions.includes('services.manage') ||
                        permissions.includes('inventory.manage-own')
                    "
                    href="/gestion/servicios"
                    class="action-secondary"
                    >Gestionar inventario</Link
                >
                <Link
                    v-if="permissions.includes('settings.manage')"
                    href="/gestion/usuarios"
                    class="action-secondary"
                    >Usuarios y roles</Link
                >
            </div>
        </section>
    </div>
</template>
