<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import DataPagination from '@/components/DataPagination.vue';
import FormErrors from '@/components/FormErrors.vue';
import OperationNotice from '@/components/OperationNotice.vue';
import { money, statusLabels } from '@/types/tourism';
import type { Paginated, TourismService } from '@/types/tourism';
const props = defineProps<{
    services: Paginated<TourismService>;
    providers: { id: number; name: string; status: string }[];
    categories: Record<string, string>;
    canPublish: boolean;
    filters: { q?: string; status?: string };
}>();
const query = ref(props.filters.q || '');
const status = ref(props.filters.status || '');
const editing = ref<number | null>(null);
const form = useForm({
    provider_id: '' as string | number,
    title: '',
    category: 'actividades',
    destination: '',
    description: '',
    conditions: '',
    reference_price: '' as string | number,
    currency: 'COP',
    status: 'draft',
});
function edit(service: TourismService) {
    editing.value = service.id;
    form.clearErrors();
    Object.assign(form, {
        provider_id: service.provider_id,
        title: service.title,
        category: service.category,
        destination: service.destination,
        description: service.description,
        conditions: service.conditions || '',
        reference_price: service.reference_price ?? '',
        currency: service.currency,
        status: props.canPublish ? service.status : 'draft',
    });
}
function reset() {
    editing.value = null;
    form.reset();
    form.clearErrors();
}
function save() {
    if (editing.value)
        form.put(`/gestion/servicios/${editing.value}`, {
            preserveScroll: true,
            onSuccess: reset,
        });
    else
        form.post('/gestion/servicios', {
            preserveScroll: true,
            onSuccess: reset,
        });
}
function search() {
    router.get(
        '/gestion/servicios',
        { q: query.value, status: status.value },
        { preserveState: true },
    );
}
</script>
<template>
    <Head title="Servicios turísticos" />
    <div class="workspace">
        <div>
            <p class="mb-2 text-sm text-muted-foreground">
                Inventario / Servicios
            </p>
            <h1 class="workspace-heading">Servicios turísticos</h1>
            <p class="mt-3 text-muted-foreground">
                {{
                    canPublish
                        ? 'Revisa el inventario y publica los servicios aprobados.'
                        : 'Administra tu inventario y envíalo a revisión. Los cambios deben aprobarse antes de publicarse.'
                }}
            </p>
        </div>
        <OperationNotice />
        <div class="grid items-start gap-8 xl:grid-cols-[1.2fr_1fr]">
            <section class="min-w-0">
                <form
                    class="mb-5 flex flex-wrap gap-3"
                    @submit.prevent="search"
                >
                    <input
                        v-model="query"
                        class="field-control min-w-40 flex-1"
                        aria-label="Buscar servicio"
                        placeholder="Nombre del servicio"
                    /><select
                        v-model="status"
                        class="field-control w-auto"
                        aria-label="Filtrar por estado"
                    >
                        <option value="">Todos los estados</option>
                        <option
                            v-for="key in [
                                'draft',
                                'review',
                                'published',
                                'archived',
                            ]"
                            :key="key"
                            :value="key"
                        >
                            {{ statusLabels[key] }}
                        </option></select
                    ><button class="action-secondary">Filtrar</button>
                </form>
                <div
                    v-if="!services.data.length"
                    class="rounded-xl border border-dashed p-8 text-muted-foreground"
                >
                    Todavía no hay servicios con estos filtros.
                </div>
                <div class="space-y-3">
                    <article
                        v-for="service in services.data"
                        :key="service.id"
                        class="rounded-xl border p-5"
                    >
                        <div class="flex flex-wrap justify-between gap-3">
                            <h2 class="font-semibold">{{ service.title }}</h2>
                            <span
                                class="rounded-full bg-muted px-3 py-1 text-xs"
                                >{{ statusLabels[service.status] }}</span
                            >
                        </div>
                        <p class="mt-2 text-sm text-muted-foreground">
                            {{ service.provider.name }} ·
                            {{ service.destination }} ·
                            {{ categories[service.category] }}
                        </p>
                        <p class="mt-4 text-sm">
                            {{ money(service.reference_price) }}
                            <span
                                v-if="service.reference_price !== null"
                                class="text-muted-foreground"
                                >de referencia</span
                            >
                        </p>
                        <button
                            class="action-secondary mt-4"
                            @click="edit(service)"
                        >
                            Editar servicio
                        </button>
                        <Link
                            v-if="
                                ['actividades', 'paquetes'].includes(
                                    service.category,
                                )
                            "
                            :href="`/gestion/servicios/${service.id}/salidas`"
                            class="action-secondary mt-4 ml-3"
                            >Fechas y cupos</Link
                        >
                    </article>
                </div>
                <DataPagination
                    :links="services.links"
                    :total="services.total"
                />
            </section>
            <section class="rounded-xl border bg-card p-6">
                <h2 class="text-xl font-semibold">
                    {{ editing ? 'Editar servicio' : 'Nuevo servicio' }}
                </h2>
                <p
                    v-if="!providers.length"
                    class="mt-4 text-sm text-muted-foreground"
                >
                    {{
                        canPublish
                            ? 'Crea primero un proveedor en la sección Proveedores.'
                            : 'Solicita a administración que vincule tu cuenta a un proveedor.'
                    }}
                </p>
                <form v-else class="mt-6 grid gap-4" @submit.prevent="save">
                    <FormErrors :errors="form.errors" />
                    <label class="field"
                        >Proveedor<select
                            v-model="form.provider_id"
                            class="field-control"
                            required
                        >
                            <option value="" disabled>Seleccionar</option>
                            <option
                                v-for="provider in providers"
                                :key="provider.id"
                                :value="provider.id"
                            >
                                {{ provider.name }} ({{
                                    statusLabels[provider.status]
                                }})
                            </option>
                        </select></label
                    >
                    <label class="field"
                        >Nombre del servicio<input
                            v-model="form.title"
                            class="field-control"
                            required
                            maxlength="255"
                    /></label>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <label class="field"
                            >Categoría<select
                                v-model="form.category"
                                class="field-control"
                            >
                                <option
                                    v-for="(label, key) in categories"
                                    :key="key"
                                    :value="key"
                                >
                                    {{ label }}
                                </option>
                            </select></label
                        ><label class="field"
                            >Destino<input
                                v-model="form.destination"
                                class="field-control"
                                required
                                maxlength="255"
                        /></label>
                    </div>
                    <label class="field"
                        >Descripción<textarea
                            v-model="form.description"
                            class="field-control"
                            required
                            minlength="20"
                            maxlength="20000"
                            rows="5"
                        />
                    </label>
                    <label class="field"
                        >Condiciones e inclusiones<textarea
                            v-model="form.conditions"
                            class="field-control"
                            maxlength="10000"
                            rows="3"
                        />
                    </label>
                    <label class="field"
                        >Precio de referencia (COP, opcional)<input
                            v-model="form.reference_price"
                            class="field-control"
                            type="number"
                            min="0"
                            max="1000000000"
                            step="1"
                        /><span
                            class="text-xs font-normal text-muted-foreground"
                            >Debe confirmarse con el proveedor; no representa
                            una tarifa disponible en tiempo real.</span
                        ></label
                    >
                    <label class="field"
                        >Estado<select
                            v-model="form.status"
                            class="field-control"
                        >
                            <option
                                v-for="key in canPublish
                                    ? [
                                          'draft',
                                          'review',
                                          'published',
                                          'archived',
                                      ]
                                    : ['draft', 'review']"
                                :key="key"
                                :value="key"
                            >
                                {{ statusLabels[key] }}
                            </option>
                        </select></label
                    >
                    <div class="flex gap-3">
                        <button
                            class="action-primary"
                            :disabled="form.processing"
                        >
                            {{
                                form.processing
                                    ? 'Guardando…'
                                    : 'Guardar servicio'
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
