<script setup lang="ts">
import { Link, useForm } from '@inertiajs/vue3';
import { computed, watch } from 'vue';
import FormErrors from '@/components/FormErrors.vue';
import { money, shortDate } from '@/types/tourism';
import type { Departure } from '@/types/tourism';
const props = defineProps<{ departures: Departure[] }>();
const form = useForm({
    departure_id:
        props.departures.find((departure) => (departure.available || 0) > 0)
            ?.id || '',
    travelers: 1,
    accept_terms: false,
    quote_token: '',
    idempotency_key: crypto.randomUUID(),
});
const selected = computed(() =>
    props.departures.find(
        (departure) => departure.id === Number(form.departure_id),
    ),
);
watch(
    selected,
    (departure) => {
        form.quote_token = departure?.quote_token || '';
        form.accept_terms = false;
        form.idempotency_key = crypto.randomUUID();
    },
    { immediate: true },
);
function reserve() {
    if (selected.value) form.post(`/salidas/${selected.value.id}/reservar`);
}
</script>
<template>
    <section
        class="mt-10 rounded-2xl border border-[#173c32]/15 bg-white p-6 sm:p-8"
    >
        <p class="text-xs tracking-widest uppercase">
            Operación propia · Cupos por fecha
        </p>
        <h2 class="mt-3 text-2xl font-semibold">Solicitar una reserva</h2>
        <p class="mt-3 text-sm leading-relaxed text-[#53685f]">
            Los cupos se retienen hasta por 24 horas mientras el proveedor
            confirma. No se realiza ningún cobro. Solo se aceptan fechas
            futuras.
        </p>
        <form class="mt-6 grid gap-5" @submit.prevent="reserve">
            <FormErrors :errors="form.errors" />
            <label class="field"
                >Fecha disponible<select
                    v-model="form.departure_id"
                    class="field-control"
                    required
                >
                    <option value="" disabled>Selecciona una fecha</option>
                    <option
                        v-for="departure in departures"
                        :key="departure.id"
                        :value="departure.id"
                        :disabled="!departure.available"
                    >
                        {{ shortDate(departure.service_date) }} ·
                        {{ departure.available }}
                        {{ departure.available === 1 ? 'cupo' : 'cupos' }} ·
                        {{ money(departure.unit_price) }} / viajero
                    </option>
                </select></label
            >
            <template v-if="selected"
                ><label class="field"
                    >Viajeros<input
                        v-model="form.travelers"
                        class="field-control"
                        type="number"
                        min="1"
                        :max="Math.min(selected.available || 0, 100)"
                        step="1"
                        required
                /></label>
                <p class="text-2xl font-semibold">
                    Total:
                    {{ money(selected.unit_price * Number(form.travelers)) }}
                </p>
                <p class="text-xs text-[#53685f]">
                    COP · Tarifa por viajero para la fecha seleccionada. Este
                    total se conserva en tu solicitud.
                </p>
                <div class="rounded-lg bg-[#f7f5ee] p-5">
                    <h3 class="text-sm font-semibold">
                        Condiciones de la salida
                    </h3>
                    <p class="mt-3 text-sm leading-relaxed whitespace-pre-line">
                        {{ selected.terms }}
                    </p>
                    <p class="mt-3 text-sm text-[#53685f]">
                        Puedes cancelar desde el panel hasta el día anterior a
                        la salida. Una solicitud vencida libera los cupos. Los
                        pagos y reembolsos no están habilitados.
                    </p>
                </div>
                <label
                    v-if="$page.props.auth.user"
                    class="flex items-start gap-3 text-sm"
                    ><input
                        v-model="form.accept_terms"
                        type="checkbox"
                        class="mt-1"
                        required
                    /><span
                        >He revisado la fecha, la tarifa y las condiciones de
                        esta salida y solicito la reserva pendiente de
                        confirmación.</span
                    ></label
                ><button
                    v-if="$page.props.auth.user"
                    class="action-primary justify-self-start"
                    :disabled="form.processing || !form.accept_terms"
                >
                    {{
                        form.processing
                            ? 'Registrando…'
                            : 'Solicitar reserva sin pago'
                    }}</button
                ><Link
                    v-else
                    href="/login"
                    class="action-primary justify-self-start"
                    >Ingresa para solicitar la reserva</Link
                ></template
            >
        </form>
    </section>
</template>
