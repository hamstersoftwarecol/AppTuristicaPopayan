<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import DataPagination from '@/components/DataPagination.vue';
import FormErrors from '@/components/FormErrors.vue';
import OperationNotice from '@/components/OperationNotice.vue';
import { statusLabels } from '@/types/tourism';
import type { Owner, Paginated, Provider } from '@/types/tourism';
const props = defineProps<{
    providers: Paginated<Provider>;
    owners: Owner[];
    filters: { q?: string; status?: string };
}>();
const query = ref(props.filters.q || '');
const status = ref(props.filters.status || '');
const editing = ref<number | null>(null);
const form = useForm({
    name: '',
    contact_email: '',
    phone: '',
    website: '',
    status: 'pending',
    owner_id: '' as string | number,
    notes: '',
});
function edit(provider: Provider) {
    editing.value = provider.id;
    form.clearErrors();
    Object.assign(form, {
        name: provider.name,
        contact_email: provider.contact_email,
        phone: provider.phone || '',
        website: provider.website || '',
        status: provider.status,
        owner_id: provider.owner_id || '',
        notes: provider.notes || '',
    });
}
function reset() {
    editing.value = null;
    form.reset();
    form.clearErrors();
}
function save() {
    if (editing.value)
        form.put(`/gestion/proveedores/${editing.value}`, {
            preserveScroll: true,
            onSuccess: reset,
        });
    else
        form.post('/gestion/proveedores', {
            preserveScroll: true,
            onSuccess: reset,
        });
}
function search() {
    router.get(
        '/gestion/proveedores',
        { q: query.value, status: status.value },
        { preserveState: true },
    );
}
</script>
<template>
    <Head title="Proveedores" />
    <div class="workspace">
        <div>
            <p class="mb-2 text-sm text-muted-foreground">
                Administración / Proveedores
            </p>
            <h1 class="workspace-heading">Tu red de proveedores</h1>
            <p class="mt-3 text-muted-foreground">
                Asigna responsables, administra contactos y controla la
                publicación de su inventario.
            </p>
        </div>
        <OperationNotice />
        <div class="grid items-start gap-8 xl:grid-cols-[1.4fr_1fr]">
            <section class="min-w-0">
                <form
                    class="mb-5 flex flex-wrap gap-3"
                    @submit.prevent="search"
                >
                    <input
                        v-model="query"
                        aria-label="Buscar proveedor por nombre"
                        class="field-control min-w-40 flex-1"
                        placeholder="Nombre del proveedor"
                    /><select
                        v-model="status"
                        aria-label="Filtrar por estado"
                        class="field-control w-auto"
                    >
                        <option value="">Todos los estados</option>
                        <option
                            v-for="key in ['pending', 'active', 'inactive']"
                            :key="key"
                            :value="key"
                        >
                            {{ statusLabels[key] }}
                        </option></select
                    ><button class="action-secondary">Filtrar</button>
                </form>
                <div
                    v-if="!providers.data.length"
                    class="rounded-xl border border-dashed p-8 text-muted-foreground"
                >
                    No hay proveedores con estos filtros. Crea uno para comenzar
                    a cargar servicios.
                </div>
                <div class="space-y-3">
                    <article
                        v-for="provider in providers.data"
                        :key="provider.id"
                        class="rounded-xl border bg-card p-5"
                    >
                        <div
                            class="flex flex-wrap items-start justify-between gap-3"
                        >
                            <div>
                                <h2 class="font-semibold">
                                    {{ provider.name }}
                                </h2>
                                <p class="mt-1 text-sm text-muted-foreground">
                                    {{ provider.contact_email }}
                                </p>
                            </div>
                            <span
                                class="rounded-full bg-muted px-3 py-1 text-xs"
                                >{{ statusLabels[provider.status] }}</span
                            >
                        </div>
                        <p class="mt-4 text-sm">
                            Responsable:
                            {{ provider.owner?.name || 'Sin asignar' }}
                        </p>
                        <button
                            class="action-secondary mt-4"
                            :aria-label="`Editar ${provider.name}`"
                            @click="edit(provider)"
                        >
                            Editar proveedor
                        </button>
                    </article>
                </div>
                <DataPagination
                    :links="providers.links"
                    :total="providers.total"
                />
            </section>
            <section class="rounded-xl border bg-card p-6">
                <h2 class="text-xl font-semibold">
                    {{ editing ? 'Editar proveedor' : 'Nuevo proveedor' }}
                </h2>
                <p class="mt-2 text-sm text-muted-foreground">
                    Activar un proveedor permite publicar sus servicios. No
                    conecta automáticamente una API.
                </p>
                <form class="mt-6 grid gap-4" @submit.prevent="save">
                    <FormErrors :errors="form.errors" />
                    <label class="field"
                        >Nombre comercial<input
                            v-model="form.name"
                            class="field-control"
                            required
                            maxlength="255"
                    /></label>
                    <label class="field"
                        >Correo de contacto<input
                            v-model="form.contact_email"
                            class="field-control"
                            type="email"
                            required
                            maxlength="255"
                    /></label>
                    <label class="field"
                        >Teléfono<input
                            v-model="form.phone"
                            class="field-control"
                            type="tel"
                            maxlength="40"
                    /></label>
                    <label class="field"
                        >Sitio web (HTTPS)<input
                            v-model="form.website"
                            class="field-control"
                            type="url"
                            placeholder="https://"
                            maxlength="255"
                    /></label>
                    <label class="field"
                        >Cuenta responsable<select
                            v-model="form.owner_id"
                            class="field-control"
                        >
                            <option value="">Sin asignar</option>
                            <option
                                v-for="owner in owners"
                                :key="owner.id"
                                :value="owner.id"
                            >
                                {{ owner.name }} · {{ owner.email }}
                            </option>
                        </select></label
                    >
                    <p
                        v-if="!owners.length"
                        class="text-xs text-muted-foreground"
                    >
                        Superadministración puede asignar el rol proveedor a una
                        cuenta registrada desde Usuarios.
                    </p>
                    <label class="field"
                        >Estado<select
                            v-model="form.status"
                            class="field-control"
                        >
                            <option
                                v-for="key in ['pending', 'active', 'inactive']"
                                :key="key"
                                :value="key"
                            >
                                {{ statusLabels[key] }}
                            </option>
                        </select></label
                    >
                    <label class="field"
                        >Notas internas<textarea
                            v-model="form.notes"
                            class="field-control"
                            rows="3"
                            maxlength="5000"
                        />
                    </label>
                    <div class="flex gap-3">
                        <button
                            class="action-primary"
                            :disabled="form.processing"
                        >
                            {{
                                form.processing
                                    ? 'Guardando…'
                                    : 'Guardar proveedor'
                            }}</button
                        ><button
                            v-if="editing"
                            type="button"
                            class="action-secondary"
                            @click="reset"
                        >
                            Cancelar edición
                        </button>
                    </div>
                </form>
            </section>
        </div>
    </div>
</template>
