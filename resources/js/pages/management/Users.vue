<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import DataPagination from '@/components/DataPagination.vue';
import FormErrors from '@/components/FormErrors.vue';
import OperationNotice from '@/components/OperationNotice.vue';
import type { Owner, Paginated } from '@/types/tourism';
type Account = Owner & {
    roles: { name: string }[];
    email_verified_at: string | null;
};
const props = defineProps<{
    users: Paginated<Account>;
    roles: string[];
    filters: { q?: string };
}>();
const query = ref(props.filters.q || '');
const selected = ref<Account | null>(null);
const form = useForm({ role: 'cliente' });
function edit(user: Account) {
    selected.value = user;
    form.role = user.roles[0]?.name || 'cliente';
    form.clearErrors();
}
function save() {
    if (selected.value)
        form.patch(`/gestion/usuarios/${selected.value.id}`, {
            preserveScroll: true,
            onSuccess: () => {
                selected.value = null;
            },
        });
}
</script>
<template>
    <Head title="Usuarios y roles" />
    <div class="workspace">
        <div>
            <p class="mb-2 text-sm text-muted-foreground">
                Superadministración / Acceso
            </p>
            <h1 class="workspace-heading">Usuarios y roles</h1>
            <p class="mt-3 text-muted-foreground">
                Administra el rol de las cuentas registradas. Los cambios quedan
                en auditoría.
            </p>
        </div>
        <OperationNotice />
        <form
            class="flex max-w-xl gap-3"
            @submit.prevent="
                router.get(
                    '/gestion/usuarios',
                    { q: query },
                    { preserveState: true },
                )
            "
        >
            <input
                v-model="query"
                class="field-control"
                aria-label="Buscar por nombre o correo"
                placeholder="Nombre o correo electrónico"
            /><button class="action-secondary">Buscar</button>
        </form>
        <section v-if="selected" class="max-w-xl rounded-xl border bg-card p-6">
            <h2 class="font-semibold">Cambiar rol de {{ selected.name }}</h2>
            <p class="mt-2 text-sm text-muted-foreground">
                Se sustituirán sus roles actuales. Administradores y finanzas
                deberán configurar doble factor.
            </p>
            <form class="mt-5 grid gap-4" @submit.prevent="save">
                <FormErrors :errors="form.errors" /><label class="field"
                    >Nuevo rol<select v-model="form.role" class="field-control">
                        <option v-for="role in roles" :key="role" :value="role">
                            {{ role }}
                        </option>
                    </select></label
                >
                <div class="flex gap-3">
                    <button class="action-primary" :disabled="form.processing">
                        Aplicar rol</button
                    ><button
                        type="button"
                        class="action-secondary"
                        @click="selected = null"
                    >
                        Cancelar
                    </button>
                </div>
            </form>
        </section>
        <div class="overflow-x-auto rounded-xl border">
            <table class="w-full text-left text-sm">
                <thead class="bg-muted">
                    <tr>
                        <th class="p-4">Cuenta</th>
                        <th class="p-4">Roles</th>
                        <th class="p-4">Correo</th>
                        <th class="p-4">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <tr
                        v-for="user in users.data"
                        :key="user.id"
                        class="border-t"
                    >
                        <td class="p-4">
                            <p class="font-medium">{{ user.name }}</p>
                            <p class="mt-1 text-muted-foreground">
                                {{ user.email }}
                            </p>
                        </td>
                        <td class="p-4">
                            {{
                                user.roles
                                    .map((role) => role.name)
                                    .join(', ') || 'Sin rol'
                            }}
                        </td>
                        <td class="p-4">
                            {{
                                user.email_verified_at
                                    ? 'Verificado'
                                    : 'Pendiente'
                            }}
                        </td>
                        <td class="p-4">
                            <button
                                v-if="user.id !== $page.props.auth.user.id"
                                class="action-secondary whitespace-nowrap"
                                @click="edit(user)"
                            >
                                Cambiar rol</button
                            ><span v-else class="text-muted-foreground"
                                >Tu cuenta</span
                            >
                        </td>
                    </tr>
                    <tr v-if="!users.data.length">
                        <td
                            colspan="4"
                            class="p-8 text-center text-muted-foreground"
                        >
                            No se encontraron cuentas.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        <DataPagination :links="users.links" :total="users.total" />
    </div>
</template>
