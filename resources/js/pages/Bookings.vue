<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import DataPagination from '@/components/DataPagination.vue';
import FormErrors from '@/components/FormErrors.vue';
import OperationNotice from '@/components/OperationNotice.vue';
import { money, shortDate, statusLabels } from '@/types/tourism';
import type { Booking, Paginated } from '@/types/tourism';
const props = defineProps<{
    bookings: Paginated<Booking>;
    managed: boolean;
    wompiReady: boolean;
    filters: { status?: string };
}>();
const selected = ref<Booking | null>(null);
const status = ref(props.filters.status || '');
const form = useForm({ reason: '' });
const confirmation = useForm({});
const paymentForm = useForm({});
const today = new Intl.DateTimeFormat('sv-SE', {
    timeZone: 'America/Bogota',
}).format(new Date());
function effectiveStatus(booking: Booking) {
    return booking.status === 'pending' &&
        new Date(booking.expires_at) <= new Date()
        ? 'expired'
        : booking.status;
}
function cancel() {
    if (selected.value)
        form.patch(`/reservas/${selected.value.id}/cancelar`, {
            preserveScroll: true,
            onSuccess: () => {
                selected.value = null;
            },
        });
}
function select(booking: Booking) {
    selected.value = booking;
    form.reset();
    form.clearErrors();
}
</script>
<template>
    <Head :title="managed ? 'Gestión de reservas' : 'Mis reservas'" />
    <div class="workspace">
        <div>
            <p class="mb-2 text-sm text-muted-foreground">
                Reservas de operación propia
            </p>
            <h1 class="workspace-heading">
                {{ managed ? 'Gestión de reservas' : 'Mis reservas' }}
            </h1>
            <p class="mt-3 text-muted-foreground">
                La solicitud retiene cupos hasta su vencimiento. El proveedor
                debe confirmar la reserva. Los pagos con dinero real aún no
                están habilitados.
            </p>
        </div>
        <OperationNotice /><FormErrors
            :errors="confirmation.errors"
        /><FormErrors :errors="paymentForm.errors" />
        <form
            v-if="managed"
            class="flex max-w-xl gap-3"
            @submit.prevent="
                router.get(
                    '/gestion/reservas',
                    { status },
                    { preserveState: true },
                )
            "
        >
            <select
                v-model="status"
                class="field-control"
                aria-label="Estado de reserva"
            >
                <option value="">Todos los estados</option>
                <option
                    v-for="key in [
                        'pending',
                        'confirmed',
                        'cancelled',
                        'expired',
                    ]"
                    :key="key"
                    :value="key"
                >
                    {{ statusLabels[key] }}
                </option></select
            ><button class="action-secondary">Filtrar</button>
        </form>
        <section v-if="selected" class="max-w-2xl rounded-xl border p-6">
            <h2 class="text-lg font-semibold">
                Cancelar {{ selected.service_title }}
            </h2>
            <p class="mt-3 text-sm text-muted-foreground">
                Se liberarán los cupos de esta reserva. Este sistema no ha
                realizado ningún cobro.
            </p>
            <form class="mt-5 grid gap-4" @submit.prevent="cancel">
                <FormErrors :errors="form.errors" /><label class="field"
                    >Motivo de cancelación<textarea
                        v-model="form.reason"
                        class="field-control"
                        rows="3"
                        minlength="5"
                        maxlength="1000"
                        required
                    />
                </label>
                <div class="flex gap-3">
                    <button class="action-primary" :disabled="form.processing">
                        Confirmar cancelación</button
                    ><button
                        type="button"
                        class="action-secondary"
                        @click="selected = null"
                    >
                        Volver
                    </button>
                </div>
            </form>
        </section>
        <div
            v-if="!bookings.data.length"
            class="rounded-xl border border-dashed p-10"
        >
            <h2 class="font-semibold">No hay reservas para mostrar</h2>
            <p class="mt-3 text-sm text-muted-foreground">
                Las solicitudes de salidas con disponibilidad aparecerán aquí.
            </p>
            <Link v-if="!managed" href="/explorar" class="action-primary mt-5"
                >Explorar servicios</Link
            >
        </div>
        <div class="grid items-start gap-5 xl:grid-cols-2">
            <article
                v-for="booking in bookings.data"
                :key="booking.id"
                class="rounded-xl border p-6"
            >
                <div class="flex flex-wrap justify-between gap-3">
                    <p class="text-xs break-all text-muted-foreground">
                        {{ booking.reference }}
                    </p>
                    <span class="rounded-full bg-muted px-3 py-1 text-xs">{{
                        statusLabels[effectiveStatus(booking)]
                    }}</span>
                </div>
                <h2 class="mt-4 text-xl font-semibold">
                    {{ booking.service_title }}
                </h2>
                <p class="mt-2 text-sm text-muted-foreground">
                    {{ booking.provider_name }} ·
                    {{ shortDate(booking.service_date) }} ·
                    {{ booking.travelers }} viajeros
                </p>
                <p v-if="managed && booking.customer" class="mt-3 text-sm">
                    {{ booking.customer.name }} · {{ booking.customer.email }}
                </p>
                <p class="mt-5 text-2xl font-semibold">
                    {{ money(booking.total) }}
                </p>
                <p class="mt-1 text-xs text-muted-foreground">
                    {{ money(booking.unit_price) }} por viajero · Sin pago real
                    registrado
                </p>
                <p
                    v-if="effectiveStatus(booking) === 'pending'"
                    class="mt-4 text-sm"
                >
                    Confirmar antes de:
                    {{ new Date(booking.expires_at).toLocaleString('es-CO') }}
                </p>
                <p
                    v-if="booking.cancellation_reason"
                    class="mt-4 text-sm whitespace-pre-line"
                >
                    Motivo de cancelación: {{ booking.cancellation_reason }}
                </p>
                <p
                    v-if="
                        booking.payments?.some(
                            (payment) => payment.status === 'APPROVED',
                        )
                    "
                    class="mt-4 text-sm font-medium text-amber-700 dark:text-amber-400"
                >
                    Prueba Wompi aprobada · No representa un pago real
                </p>
                <button
                    v-if="
                        !managed &&
                        wompiReady &&
                        booking.status === 'confirmed' &&
                        booking.service_date.slice(0, 10) > today &&
                        !booking.payments?.some(
                            (payment) => payment.status === 'APPROVED',
                        )
                    "
                    class="action-secondary mt-4"
                    :disabled="paymentForm.processing"
                    @click="
                        paymentForm.post(`/reservas/${booking.id}/pago-prueba`)
                    "
                >
                    Probar pago con Wompi
                </button>
                <a
                    :href="`/reservas/${booking.id}/comprobante`"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="action-secondary mt-4 mr-3"
                    >Ver comprobante / Imprimir</a
                >
                <details class="mt-5 text-sm">
                    <summary class="cursor-pointer font-medium">
                        Condiciones aceptadas
                    </summary>
                    <p class="mt-3 leading-relaxed whitespace-pre-line">
                        {{ booking.terms }}
                    </p>
                    <p class="mt-3 text-muted-foreground">
                        Cancelación desde el panel hasta el día anterior a la
                        salida. No se ha realizado ningún cobro.
                    </p>
                </details>
                <div
                    v-if="booking.service_date.slice(0, 10) > today"
                    class="mt-5 flex flex-wrap gap-3"
                >
                    <button
                        v-if="managed && effectiveStatus(booking) === 'pending'"
                        class="action-primary"
                        :disabled="confirmation.processing"
                        @click="
                            confirmation.patch(
                                `/gestion/reservas/${booking.id}/confirmar`,
                                { preserveScroll: true },
                            )
                        "
                    >
                        Confirmar reserva local</button
                    ><button
                        v-if="
                            ['pending', 'confirmed'].includes(
                                effectiveStatus(booking),
                            )
                        "
                        class="action-secondary"
                        @click="select(booking)"
                    >
                        Cancelar reserva
                    </button>
                </div>
            </article>
        </div>
        <DataPagination :links="bookings.links" :total="bookings.total" />
    </div>
</template>
