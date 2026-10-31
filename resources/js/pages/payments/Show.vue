<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { money } from '@/types/tourism';
defineProps<{
    payment: {
        id: number;
        reference: string;
        environment: string;
        amount_in_cents: number;
        status: string;
        gateway_transaction_id: string | null;
    };
    booking: {
        id: number;
        reference: string;
        service_title: string;
        status: string;
    };
    checkoutFields: Record<string, string | number> | null;
}>();
const labels: Record<string, string> = {
    CREATED: 'Checkout preparado',
    PENDING: 'En proceso',
    APPROVED: 'Prueba aprobada',
    DECLINED: 'Prueba rechazada',
    ERROR: 'Error en la prueba',
    VOIDED: 'Prueba anulada',
};
</script>
<template>
    <Head title="Pago de prueba · Wompi" />
    <div class="workspace max-w-4xl">
        <Link href="/mis-reservas" class="text-sm underline underline-offset-4"
            >Volver a mis reservas</Link
        >
        <div>
            <p
                class="mb-2 text-sm font-medium text-amber-700 dark:text-amber-400"
            >
                WOMPI COLOMBIA · SANDBOX
            </p>
            <h1 class="workspace-heading">Pago de prueba</h1>
            <p class="mt-3 text-muted-foreground">
                Este flujo no mueve dinero real ni registra una reserva como
                pagada.
            </p>
        </div>
        <section class="rounded-xl border bg-card p-6 sm:p-8">
            <h2 class="text-xl font-semibold">{{ booking.service_title }}</h2>
            <p class="mt-3 text-3xl font-semibold">
                {{ money(payment.amount_in_cents / 100) }}
            </p>
            <p class="mt-4 text-sm break-all text-muted-foreground">
                Referencia de prueba: {{ payment.reference }}
            </p>
            <p class="mt-4 font-medium">
                {{ labels[payment.status] || payment.status }}
            </p>
            <p
                v-if="payment.gateway_transaction_id"
                class="mt-2 text-xs break-all text-muted-foreground"
            >
                Transacción sandbox: {{ payment.gateway_transaction_id }}
            </p>
            <p
                v-if="booking.status !== 'confirmed'"
                class="mt-4 text-sm text-muted-foreground"
            >
                La reserva ya no está confirmada. No se puede abrir un nuevo
                checkout para ella.
            </p>
            <form
                v-if="checkoutFields"
                action="https://checkout.wompi.co/p/"
                method="GET"
                class="mt-6"
            >
                <input
                    v-for="(value, name) in checkoutFields"
                    :key="name"
                    type="hidden"
                    :name="String(name)"
                    :value="value"
                /><button type="submit" class="action-primary">
                    Abrir Wompi sandbox
                </button>
                <p class="mt-3 text-xs text-muted-foreground">
                    Completa la prueba en la página de Wompi con sus datos de
                    prueba oficiales. Los datos de tarjeta no pasan por esta
                    aplicación.
                </p>
            </form>
            <div class="mt-6 border-t pt-5">
                <p class="text-sm text-muted-foreground">
                    Si ya regresaste del checkout, el estado se actualizará
                    cuando llegue la notificación verificada. La URL de retorno
                    no acredita el pago.
                </p>
                <button
                    type="button"
                    class="action-secondary mt-4"
                    @click="
                        router.reload({
                            only: ['payment', 'booking', 'checkoutFields'],
                        })
                    "
                >
                    Actualizar estado
                </button>
            </div>
        </section>
    </div>
</template>
