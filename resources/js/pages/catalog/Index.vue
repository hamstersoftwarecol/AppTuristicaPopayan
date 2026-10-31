<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { Heart, MapPin, Search } from '@lucide/vue';
import { ref } from 'vue';
import DataPagination from '@/components/DataPagination.vue';
import PublicShell from '@/components/PublicShell.vue';
import { money } from '@/types/tourism';
import type { Paginated, TourismService } from '@/types/tourism';
const props = defineProps<{
    services: Paginated<TourismService>;
    categories: Record<string, string>;
    filters: {
        q?: string;
        category?: string;
        destination?: string;
        max_price?: number;
        sort?: string;
        favorites?: boolean;
    };
    favoriteIds: number[];
}>();
const form = useForm({
    q: props.filters.q || '',
    category: props.filters.category || '',
    destination: props.filters.destination || '',
    max_price: props.filters.max_price ?? '',
    sort: props.filters.sort || 'recent',
    favorites: props.filters.favorites ? 1 : 0,
});
const busy = ref(false);
function search() {
    form.get('/explorar', { preserveState: true });
}
function favorite(service: TourismService) {
    busy.value = true;
    const options = {
        preserveScroll: true,
        onFinish: () => {
            busy.value = false;
        },
    };
    if (props.favoriteIds.includes(service.id))
        router.delete(`/favoritos/${service.id}`, options);
    else router.post(`/favoritos/${service.id}`, {}, options);
}
</script>
<template>
    <Head title="Explorar servicios" />
    <PublicShell>
        <p class="mb-3 text-xs font-semibold tracking-widest uppercase">
            Elige tu próximo camino
        </p>
        <h1 class="font-serif text-4xl sm:text-5xl">Explora a tu manera.</h1>
        <p class="mt-4 max-w-2xl text-[#53685f]">
            Descubre servicios de nuestros proveedores y solicita una
            cotización. Los precios son de referencia y requieren confirmación.
        </p>
        <form
            class="mt-8 grid gap-4 rounded-2xl border border-[#173c32]/10 bg-white p-5 sm:grid-cols-2 lg:grid-cols-3"
            @submit.prevent="search"
        >
            <label class="field"
                >¿Qué buscas?<input
                    v-model="form.q"
                    class="field-control"
                    placeholder="Servicio o experiencia"
                    maxlength="100"
            /></label>
            <label class="field"
                >Categoría<select v-model="form.category" class="field-control">
                    <option value="">Todas las categorías</option>
                    <option
                        v-for="(label, key) in categories"
                        :key="key"
                        :value="key"
                    >
                        {{ label }}
                    </option>
                </select></label
            >
            <label class="field"
                >Destino<input
                    v-model="form.destination"
                    class="field-control"
                    placeholder="Ciudad o región"
                    maxlength="100"
            /></label>
            <label class="field"
                >Precio máximo de referencia (COP)<input
                    v-model="form.max_price"
                    class="field-control"
                    type="number"
                    min="0"
                    max="1000000000"
                    step="1"
                    placeholder="Sin límite"
            /></label>
            <label class="field"
                >Ordenar<select v-model="form.sort" class="field-control">
                    <option value="recent">Más recientes</option>
                    <option value="price_asc">
                        Menor precio de referencia
                    </option>
                    <option value="price_desc">
                        Mayor precio de referencia
                    </option>
                </select></label
            >
            <div class="flex flex-wrap items-end gap-3">
                <button class="action-primary" :disabled="form.processing">
                    <Search class="size-4" />{{
                        form.processing ? 'Buscando…' : 'Buscar'
                    }}</button
                ><button
                    type="button"
                    class="action-secondary"
                    @click="router.get('/explorar')"
                >
                    Limpiar
                </button>
            </div>
            <label
                v-if="$page.props.auth.user"
                class="flex items-center gap-2 text-sm"
                ><input
                    v-model="form.favorites"
                    type="checkbox"
                    :true-value="1"
                    :false-value="0"
                />
                Solo mis favoritos</label
            >
        </form>
        <div
            v-if="!services.data.length"
            class="my-10 rounded-2xl border border-dashed border-[#173c32]/25 bg-white/50 p-10 text-center"
        >
            <Search class="mx-auto size-8 text-[#53685f]" />
            <h2 class="mt-4 text-xl font-semibold">
                No encontramos servicios con estos filtros
            </h2>
            <p class="mt-3 text-[#53685f]">
                Prueba otra categoría o destino. Solo aparecen servicios
                publicados de proveedores activos.
            </p>
        </div>
        <div class="mt-8 grid gap-5 md:grid-cols-2 lg:grid-cols-3">
            <article
                v-for="service in services.data"
                :key="service.id"
                class="flex flex-col overflow-hidden rounded-2xl border border-[#173c32]/10 bg-white"
            >
                <div
                    class="flex items-center justify-between bg-[#e6dcca]/50 p-5"
                >
                    <span
                        class="text-xs font-semibold tracking-widest uppercase"
                        >{{ categories[service.category] }}</span
                    ><button
                        v-if="$page.props.auth.user"
                        :aria-label="
                            favoriteIds.includes(service.id)
                                ? `Quitar ${service.title} de favoritos`
                                : `Guardar ${service.title} en favoritos`
                        "
                        :aria-pressed="favoriteIds.includes(service.id)"
                        :disabled="busy"
                        class="rounded-full p-2 hover:bg-white"
                        @click="favorite(service)"
                    >
                        <Heart
                            class="size-5"
                            :class="
                                favoriteIds.includes(service.id)
                                    ? 'fill-[#a45432] text-[#a45432]'
                                    : ''
                            "
                        />
                    </button>
                </div>
                <div class="flex flex-1 flex-col p-6">
                    <p class="flex items-center gap-1 text-xs text-[#53685f]">
                        <MapPin class="size-3" />{{ service.destination }}
                    </p>
                    <h2 class="mt-3 text-xl font-semibold">
                        <Link :href="`/servicios/${service.id}`">{{
                            service.title
                        }}</Link>
                    </h2>
                    <p
                        class="mt-3 line-clamp-3 text-sm leading-relaxed text-[#53685f]"
                    >
                        {{ service.description }}
                    </p>
                    <div class="mt-auto pt-6">
                        <p class="font-semibold">
                            {{ money(service.reference_price) }}
                        </p>
                        <p
                            v-if="service.reference_price !== null"
                            class="mt-1 text-xs text-[#53685f]"
                        >
                            Precio de referencia · Sujeto a confirmación
                        </p>
                        <p class="mt-3 text-xs text-[#53685f]">
                            Por {{ service.provider.name }}
                        </p>
                        <Link
                            :href="`/servicios/${service.id}`"
                            class="action-primary mt-5 w-full"
                            >Ver servicio</Link
                        >
                    </div>
                </div>
            </article>
        </div>
        <DataPagination :links="services.links" :total="services.total" />
    </PublicShell>
</template>
