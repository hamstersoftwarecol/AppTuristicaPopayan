<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import DataPagination from '@/components/DataPagination.vue';
import FormErrors from '@/components/FormErrors.vue';
import OperationNotice from '@/components/OperationNotice.vue';
import { shortDate, statusLabels } from '@/types/tourism';
import type { Inquiry, Paginated } from '@/types/tourism';
const props = defineProps<{
    inquiries: Paginated<Inquiry>;
    managed: boolean;
    filters: { status?: string };
}>();
const status = ref(props.filters.status || '');
const selected = ref<Inquiry | null>(null);
const form = useForm({ status: 'in_progress', response: '' });
const cancelForm = useForm({});
function edit(inquiry: Inquiry) {
    selected.value = inquiry;
    form.status = 'in_progress';
    form.response = inquiry.response || '';
    form.clearErrors();
}
function save() {
    if (selected.value)
        form.patch(`/gestion/solicitudes/${selected.value.id}`, {
            preserveScroll: true,
            onSuccess: () => {
                selected.value = null;
            },
        });
}
</script>
<template>
    <Head :title="managed ? 'Atención de solicitudes' : 'Mis solicitudes'" />
    <div class="workspace">
        <div>
            <p class="mb-2 text-sm text-muted-foreground">
                {{
                    managed
                        ? 'Operación / Cotizaciones'
                        : 'Mi cuenta / Cotizaciones'
                }}
            </p>
            <h1 class="workspace-heading">
                {{ managed ? 'Atención de solicitudes' : 'Mis solicitudes' }}
            </h1>
            <p class="mt-3 text-muted-foreground">
                Las solicitudes de cotización no son reservas confirmadas ni
                generan cobros.
            </p>
        </div>
        <OperationNotice />
        <form
            v-if="managed"
            class="flex max-w-xl gap-3"
            @submit.prevent="
                router.get(
                    '/gestion/solicitudes',
                    { status },
                    { preserveState: true },
                )
            "
        >
            <select
                v-model="status"
                aria-label="Estado de solicitud"
                class="field-control"
            >
                <option value="">Todos los estados</option>
                <option
                    v-for="key in [
                        'pending',
                        'in_progress',
                        'responded',
                        'cancelled',
                    ]"
                    :key="key"
                    :value="key"
                >
                    {{ statusLabels[key] }}
                </option></select
            ><button class="action-secondary">Filtrar</button>
        </form>
        <section v-if="selected" class="rounded-xl border bg-card p-6">
            <h2 class="text-xl font-semibold">
                Atender solicitud #{{ selected.id }}
            </h2>
            <p class="mt-2 text-sm whitespace-pre-line text-muted-foreground">
                {{ selected.message }}
            </p>
            <form class="mt-5 grid gap-4" @submit.prevent="save">
                <FormErrors :errors="form.errors" /><label class="field"
                    >Estado<select v-model="form.status" class="field-control">
                        <option
                            v-for="key in [
                                'in_progress',
                                'responded',
                                'cancelled',
                            ]"
                            :key="key"
                            :value="key"
                        >
                            {{ statusLabels[key] }}
                        </option>
                    </select></label
                ><label class="field"
                    >Respuesta visible para el cliente<textarea
                        v-model="form.response"
                        class="field-control"
                        rows="5"
                        :required="form.status === 'responded'"
                        minlength="10"
                        maxlength="10000"
                    />
                </label>
                <div class="flex gap-3">
                    <button class="action-primary" :disabled="form.processing">
                        Guardar atención</button
                    ><button
                        type="button"
                        class="action-secondary"
                        @click="selected = null"
                    >
                        Cancelar edición
                    </button>
                </div>
            </form>
        </section>
        <div
            v-if="!inquiries.data.length"
            class="rounded-xl border border-dashed p-10"
        >
            <h2 class="font-semibold">No hay solicitudes para mostrar</h2>
            <p class="mt-3 text-sm text-muted-foreground">
                {{
                    managed
                        ? 'Las nuevas solicitudes del catálogo aparecerán aquí.'
                        : 'Explora el catálogo y solicita información sobre un servicio.'
                }}
            </p>
            <Link v-if="!managed" href="/explorar" class="action-primary mt-5"
                >Explorar servicios</Link
            >
        </div>
        <div class="grid gap-4 lg:grid-cols-2">
            <article
                v-for="inquiry in inquiries.data"
                :key="inquiry.id"
                class="rounded-xl border p-6"
            >
                <div class="flex flex-wrap justify-between gap-3">
                    <span class="text-sm text-muted-foreground"
                        >Solicitud #{{ inquiry.id }}</span
                    ><span class="rounded-full bg-muted px-3 py-1 text-xs">{{
                        statusLabels[inquiry.status]
                    }}</span>
                </div>
                <h2 class="mt-4 text-lg font-semibold">
                    {{ inquiry.service?.title || 'Servicio retirado' }}
                </h2>
                <p class="mt-2 text-sm text-muted-foreground">
                    {{ shortDate(inquiry.travel_date) }} ·
                    {{ inquiry.travelers }} viajeros ·
                    {{ inquiry.service?.destination }}
                </p>
                <p v-if="managed && inquiry.customer" class="mt-3 text-sm">
                    {{ inquiry.customer.name }} · {{ inquiry.customer.email }}
                </p>
                <p class="mt-4 text-sm whitespace-pre-line">
                    {{ inquiry.message }}
                </p>
                <section
                    v-if="inquiry.response"
                    class="mt-5 rounded-lg bg-muted p-4"
                >
                    <h3 class="text-sm font-semibold">Respuesta del equipo</h3>
                    <p class="mt-2 text-sm whitespace-pre-line">
                        {{ inquiry.response }}
                    </p>
                </section>
                <div
                    v-if="['pending', 'in_progress'].includes(inquiry.status)"
                    class="mt-5"
                >
                    <button
                        v-if="managed"
                        class="action-primary"
                        @click="edit(inquiry)"
                    >
                        Atender solicitud</button
                    ><button
                        v-else
                        class="action-secondary"
                        :disabled="cancelForm.processing"
                        @click="
                            cancelForm.patch(
                                `/mis-solicitudes/${inquiry.id}/cancelar`,
                                { preserveScroll: true },
                            )
                        "
                    >
                        Cancelar solicitud
                    </button>
                </div>
            </article>
        </div>
        <DataPagination :links="inquiries.links" :total="inquiries.total" />
    </div>
</template>
