<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import DataPagination from '@/components/DataPagination.vue';
import PublicShell from '@/components/PublicShell.vue';
import { shortDate } from '@/types/tourism';
import type { Paginated, Post } from '@/types/tourism';
const props = defineProps<{
    posts: Paginated<Post>;
    categories: string[];
    filters: { q?: string; category?: string };
}>();
const form = useForm({
    q: props.filters.q || '',
    category: props.filters.category || '',
});
</script>
<template>
    <Head title="Historias y destinos" />
    <PublicShell
        ><p class="mb-3 text-xs tracking-widest uppercase">
            El diario del viajero
        </p>
        <h1 class="font-serif text-5xl">Historias que inspiran.</h1>
        <p class="mt-5 text-[#53685f]">
            Destinos, ideas y consejos para tu próximo viaje.
        </p>
        <form
            class="mt-8 flex flex-wrap gap-3"
            @submit.prevent="form.get('/blog', { preserveState: true })"
        >
            <input
                v-model="form.q"
                class="field-control w-auto flex-1"
                aria-label="Buscar artículos"
                placeholder="Buscar por título"
                maxlength="100"
            /><select
                v-model="form.category"
                class="field-control w-auto"
                aria-label="Categoría"
            >
                <option value="">Todas las categorías</option>
                <option
                    v-for="category in categories"
                    :key="category"
                    :value="category"
                >
                    {{ category }}
                </option></select
            ><button class="action-primary" :disabled="form.processing">
                Buscar
            </button>
        </form>
        <div
            v-if="!posts.data.length"
            class="mt-8 rounded-xl border border-dashed border-[#173c32]/20 p-10 text-center text-[#53685f]"
        >
            No hay artículos publicados con estos filtros.
        </div>
        <div class="mt-8 grid gap-5 md:grid-cols-2 lg:grid-cols-3">
            <article
                v-for="post in posts.data"
                :key="post.id"
                class="rounded-2xl border border-[#173c32]/10 bg-white p-7"
            >
                <p class="text-xs tracking-widest uppercase">
                    {{ post.category }}
                </p>
                <h2 class="mt-4 font-serif text-3xl">
                    <Link :href="`/blog/${post.slug}`">{{ post.title }}</Link>
                </h2>
                <p class="mt-4 text-sm leading-relaxed text-[#53685f]">
                    {{ post.excerpt }}
                </p>
                <p v-if="post.published_at" class="mt-5 text-xs text-[#53685f]">
                    {{ shortDate(post.published_at) }}
                </p>
                <Link
                    :href="`/blog/${post.slug}`"
                    class="mt-5 inline-block text-sm font-semibold underline underline-offset-4"
                    >Leer historia</Link
                >
            </article>
        </div>
        <DataPagination :links="posts.links" :total="posts.total"
    /></PublicShell>
</template>
