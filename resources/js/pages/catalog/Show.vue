<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { MapPin, ShieldCheck } from '@lucide/vue';
import FormErrors from '@/components/FormErrors.vue';
import LocalBookingForm from '@/components/LocalBookingForm.vue';
import PublicShell from '@/components/PublicShell.vue';
import { money } from '@/types/tourism';
import type { TourismService, Departure } from '@/types/tourism';
const props = defineProps<{
    service: TourismService;
    isFavorite: boolean;
    departures: Departure[];
}>();
const form = useForm({ travel_date: '', travelers: 1, message: '' });
const favoriteForm = useForm({});
const today = new Intl.DateTimeFormat('sv-SE', {
    timeZone: 'America/Bogota',
}).format(new Date());
function favorite() {
    if (props.isFavorite)
        favoriteForm.delete(`/favoritos/${props.service.id}`, {
            preserveScroll: true,
        });
    else
        favoriteForm.post(`/favoritos/${props.service.id}`, {
            preserveScroll: true,
        });
}
</script>
<template>
    <Head :title="service.title"
        ><meta name="description" :content="service.description.slice(0, 160)"
    /></Head>
    <PublicShell>
        <Link href="/explorar" class="text-sm underline underline-offset-4"
            >Volver al catálogo</Link
        >
        <div class="mt-8 grid items-start gap-10 lg:grid-cols-[1.5fr_1fr]">
            <article>
                <p class="flex items-center gap-2 text-sm text-[#53685f]">
                    <MapPin class="size-4" />{{ service.destination }}
                </p>
                <h1 class="mt-4 font-serif text-4xl sm:text-5xl">
                    {{ service.title }}
                </h1>
                <p class="mt-4 text-sm text-[#53685f]">
                    Servicio de {{ service.provider.name }}
                </p>
                <p class="mt-8 leading-8 whitespace-pre-line">
                    {{ service.description }}
                </p>
                <section
                    v-if="service.conditions"
                    class="mt-10 border-t border-[#173c32]/15 pt-7"
                >
                    <h2 class="text-xl font-semibold">
                        Condiciones e inclusiones
                    </h2>
                    <p class="mt-4 leading-8 whitespace-pre-line">
                        {{ service.conditions }}
                    </p>
                </section>
                <button
                    v-if="$page.props.auth.user"
                    class="action-secondary mt-8"
                    :disabled="favoriteForm.processing"
                    :aria-pressed="isFavorite"
                    @click="favorite"
                >
                    {{
                        isFavorite
                            ? 'Quitar de favoritos'
                            : 'Guardar en favoritos'
                    }}
                </button>
                <LocalBookingForm
                    v-if="departures.length"
                    :departures="departures"
                />
                <p
                    v-else-if="
                        ['actividades', 'paquetes'].includes(service.category)
                    "
                    class="mt-8 rounded-xl border border-dashed border-[#173c32]/20 p-5 text-sm text-[#53685f]"
                >
                    No hay fechas de reserva habilitadas. Puedes solicitar una
                    cotización al equipo.
                </p>
            </article>
            <aside class="rounded-2xl border border-[#173c32]/10 bg-white p-7">
                <p class="text-2xl font-semibold">
                    {{
                        departures.length
                            ? '¿Buscas otra opción?'
                            : money(service.reference_price)
                    }}
                </p>
                <p class="mt-2 text-sm text-[#53685f]">
                    {{
                        departures.length
                            ? 'Consulta otras fechas o necesidades para tu viaje.'
                            : service.reference_price === null
                              ? 'Solicita las condiciones para tu viaje.'
                              : 'Precio de referencia en COP. La tarifa final depende de fechas, viajeros y disponibilidad.'
                    }}
                </p>
                <div
                    class="mt-6 flex items-start gap-3 rounded-lg bg-[#f7f5ee] p-4 text-sm"
                >
                    <ShieldCheck class="size-5 shrink-0" />
                    <p>
                        Esta es una solicitud de cotización. No bloquea cupos,
                        confirma reservas ni genera cobros.
                    </p>
                </div>
                <h2 class="mt-7 text-xl font-semibold">Solicitar cotización</h2>
                <form
                    v-if="$page.props.auth.user"
                    class="mt-5 grid gap-4"
                    @submit.prevent="
                        form.post(`/servicios/${service.id}/cotizar`)
                    "
                >
                    <FormErrors :errors="form.errors" /><label class="field"
                        >Fecha estimada<input
                            v-model="form.travel_date"
                            class="field-control"
                            type="date"
                            :min="today"
                            required /></label
                    ><label class="field"
                        >Número de viajeros<input
                            v-model="form.travelers"
                            class="field-control"
                            type="number"
                            min="1"
                            max="100"
                            required /></label
                    ><label class="field"
                        >Cuéntanos qué necesitas<textarea
                            v-model="form.message"
                            class="field-control"
                            rows="5"
                            required
                            minlength="10"
                            maxlength="5000"
                            placeholder="Detalles del viaje y dudas sobre el servicio"
                        /></label
                    ><button class="action-primary" :disabled="form.processing">
                        {{ form.processing ? 'Enviando…' : 'Enviar solicitud' }}
                    </button>
                    <p class="text-xs text-[#53685f]">
                        La respuesta aparecerá en Mis solicitudes. Tu correo
                        debe estar verificado.
                    </p>
                </form>
                <div v-else class="mt-5">
                    <p class="text-sm text-[#53685f]">
                        Ingresa para guardar el servicio y solicitar una
                        cotización.
                    </p>
                    <Link href="/login" class="action-primary mt-5 w-full"
                        >Ingresar</Link
                    ><Link
                        href="/register"
                        class="mt-4 block text-center text-sm underline"
                        >Crear cuenta</Link
                    >
                </div>
            </aside>
        </div>
    </PublicShell>
</template>
