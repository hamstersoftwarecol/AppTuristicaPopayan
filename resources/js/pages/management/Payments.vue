<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import DataPagination from '@/components/DataPagination.vue';
import { money } from '@/types/tourism';
import type { Paginated } from '@/types/tourism';
type Payment = {
    id: number;
    reference: string;
    environment: string;
    amount_in_cents: number;
    status: string;
    gateway_transaction_id: string | null;
    created_at: string;
    booking: { reference: string; service_title: string; status: string };
};
const props = defineProps<{
    payments: Paginated<Payment>;
    filters: { status?: string };
}>();
const status = ref(props.filters.status || '');
</script>
<template>
    <Head title="Pagos de prueba" />
    <div class="workspace">
        <div>
            <p class="mb-2 text-sm text-muted-foreground">Finanzas / Sandbox</p>
            <h1 class="workspace-heading">Pagos de prueba Wompi</h1>
            <p class="mt-3 text-muted-foreground">
                Estos registros son pruebas sin dinero real. No forman parte de
                ingresos, cobros, liquidaciones o conciliación de producción.
            </p>
        </div>
        <form
            class="flex max-w-xl gap-3"
            @submit.prevent="
                router.get(
                    '/gestion/pagos',
                    { status },
                    { preserveState: true },
                )
            "
        >
            <select
                v-model="status"
                class="field-control"
                aria-label="Estado del pago de prueba"
            >
                <option value="">Todos los estados</option>
                <option
                    v-for="item in [
                        'CREATED',
                        'PENDING',
                        'APPROVED',
                        'DECLINED',
                        'ERROR',
                        'VOIDED',
                    ]"
                    :key="item"
                    :value="item"
                >
                    {{ item }}
                </option></select
            ><button class="action-secondary">Filtrar</button>
        </form>
        <div
            v-if="!payments.data.length"
            class="rounded-xl border border-dashed p-8 text-muted-foreground"
        >
            Todavía no hay intentos de pago con estos filtros.
        </div>
        <div class="grid gap-4 lg:grid-cols-2">
            <article
                v-for="payment in payments.data"
                :key="payment.id"
                class="rounded-xl border p-6"
            >
                <div class="flex flex-wrap justify-between gap-3">
                    <h2 class="font-semibold">
                        {{ payment.booking.service_title }}
                    </h2>
                    <span class="rounded-full bg-muted px-3 py-1 text-xs"
                        >{{ payment.status }} · SANDBOX</span
                    >
                </div>
                <p class="mt-4 text-2xl font-semibold">
                    {{ money(payment.amount_in_cents / 100) }}
                </p>
                <p class="mt-3 text-xs break-all text-muted-foreground">
                    Pago de prueba: {{ payment.reference }}
                </p>
                <p class="mt-2 text-xs break-all text-muted-foreground">
                    Reserva: {{ payment.booking.reference }} ·
                    {{ payment.booking.status }}
                </p>
                <p
                    v-if="payment.gateway_transaction_id"
                    class="mt-2 text-xs break-all text-muted-foreground"
                >
                    Transacción: {{ payment.gateway_transaction_id }}
                </p>
            </article>
        </div>
        <DataPagination :links="payments.links" :total="payments.total" />
    </div>
</template>
