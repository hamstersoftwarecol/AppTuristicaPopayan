<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import DataPagination from '@/components/DataPagination.vue';
import FormErrors from '@/components/FormErrors.vue';
import OperationNotice from '@/components/OperationNotice.vue';
import { money, shortDate } from '@/types/tourism';
import type { Departure, Paginated } from '@/types/tourism';
const props = defineProps<{
    service: { id: number; title: string };
    departures: Paginated<Departure>;
    canOpen: boolean;
}>();
const editing = ref<number | null>(null);
const form = useForm({
    service_date: '',
    capacity: 10,
    unit_price: '' as string | number,
    terms: '',
    is_open: false,
});
function edit(departure: Departure) {
    editing.value = departure.id;
    form.clearErrors();
    Object.assign(form, {
        service_date: departure.service_date.slice(0, 10),
        capacity: departure.capacity,
        unit_price: departure.unit_price,
        terms: departure.terms,
        is_open: props.canOpen ? departure.is_open : false,
    });
}
function reset() {
    editing.value = null;
    form.reset();
    form.clearErrors();
}
function save() {
    if (editing.value)
        form.put(
            `/gestion/servicios/${props.service.id}/salidas/${editing.value}`,
            { preserveScroll: true, onSuccess: reset },
        );
    else
        form.post(`/gestion/servicios/${props.service.id}/salidas`, {
            preserveScroll: true,
            onSuccess: reset,
        });
}
</script>
<template>
    <Head :title="`Salidas · ${service.title}`" />
    <div class="workspace">
        <Link
            href="/gestion/servicios"
            class="text-sm underline underline-offset-4"
            >Volver al inventario</Link
        >
        <div>
            <p class="mb-2 text-sm text-muted-foreground">
                Disponibilidad de operación propia
            </p>
            <h1 class="workspace-heading">{{ service.title }}</h1>
            <p class="mt-3 max-w-3xl text-muted-foreground">
                Define cupos y tarifa por viajero para cada fecha.
                Administración debe abrir las salidas para recibir solicitudes.
                No se conectan proveedores externos ni se procesan cobros.
            </p>
        </div>
        <OperationNotice />
        <div class="grid items-start gap-8 xl:grid-cols-[1.2fr_1fr]">
            <section>
                <div
                    v-if="!departures.data.length"
                    class="rounded-xl border border-dashed p-8 text-muted-foreground"
                >
                    Todavía no hay salidas. Registra una fecha futura para
                    comenzar.
                </div>
                <div class="space-y-4">
                    <article
                        v-for="departure in departures.data"
                        :key="departure.id"
                        class="rounded-xl border p-6"
                    >
                        <div class="flex flex-wrap justify-between gap-3">
                            <h2 class="text-lg font-semibold">
                                {{ shortDate(departure.service_date) }}
                            </h2>
                            <span
                                class="rounded-full bg-muted px-3 py-1 text-xs"
                                >{{
                                    departure.is_open
                                        ? 'Abierta'
                                        : 'Cerrada / por aprobar'
                                }}</span
                            >
                        </div>
                        <p class="mt-4 text-sm">
                            {{ departure.occupied || 0 }} de
                            {{ departure.capacity }} cupos ocupados
                        </p>
                        <p class="mt-2 text-sm text-muted-foreground">
                            {{ money(departure.unit_price) }} por viajero ·
                            {{ departure.bookings_count }} solicitudes
                            históricas
                        </p>
                        <button
                            class="action-secondary mt-4"
                            @click="edit(departure)"
                        >
                            Editar salida
                        </button>
                    </article>
                </div>
                <DataPagination
                    :links="departures.links"
                    :total="departures.total"
                />
            </section>
            <section class="rounded-xl border bg-card p-6">
                <h2 class="text-xl font-semibold">
                    {{ editing ? 'Editar salida' : 'Nueva salida' }}
                </h2>
                <form class="mt-6 grid gap-4" @submit.prevent="save">
                    <FormErrors :errors="form.errors" /><label class="field"
                        >Fecha de la actividad<input
                            v-model="form.service_date"
                            class="field-control"
                            type="date"
                            required /></label
                    ><label class="field"
                        >Capacidad total (viajeros)<input
                            v-model="form.capacity"
                            class="field-control"
                            type="number"
                            min="1"
                            max="10000"
                            step="1"
                            required /></label
                    ><label class="field"
                        >Tarifa final por viajero (COP)<input
                            v-model="form.unit_price"
                            class="field-control"
                            type="number"
                            min="1"
                            max="1000000000"
                            step="1"
                            required
                        /><span
                            class="text-xs font-normal text-muted-foreground"
                            >Incluye todos los conceptos aplicables. Se conserva
                            en cada solicitud, aunque cambie después.</span
                        ></label
                    ><label class="field"
                        >Condiciones de la salida<textarea
                            v-model="form.terms"
                            class="field-control"
                            rows="6"
                            minlength="20"
                            maxlength="10000"
                            required
                        /></label
                    ><label
                        v-if="canOpen"
                        class="flex items-start gap-3 text-sm"
                        ><input
                            v-model="form.is_open"
                            type="checkbox"
                            class="mt-1"
                        /><span
                            >Habilitar reservas para esta salida de operación
                            propia. El servicio debe estar publicado y su
                            proveedor activo.</span
                        ></label
                    >
                    <p v-else class="text-sm text-muted-foreground">
                        Los cambios cierran la salida hasta que administración
                        vuelva a aprobarla.
                    </p>
                    <p class="text-xs text-muted-foreground">
                        Solo se aceptan fechas futuras. Las solicitudes retienen
                        cupos durante un máximo de 24 horas y pueden cancelarse
                        desde el panel hasta el día anterior a la salida. No se
                        realizan cobros.
                    </p>
                    <div class="flex gap-3">
                        <button
                            class="action-primary"
                            :disabled="form.processing"
                        >
                            Guardar salida</button
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
