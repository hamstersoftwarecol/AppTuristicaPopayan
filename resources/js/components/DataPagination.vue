<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
defineProps<{
    links: { url: string | null; label: string; active: boolean }[];
    total: number;
}>();
</script>
<template>
    <div class="mt-6 flex flex-wrap items-center justify-between gap-4">
        <p class="text-sm text-muted-foreground">
            {{ total }} {{ total === 1 ? 'resultado' : 'resultados' }}
        </p>
        <nav
            v-if="links.length > 3"
            aria-label="Paginación"
            class="flex flex-wrap gap-1"
        >
            <template v-for="(link, index) in links" :key="index">
                <Link
                    v-if="link.url"
                    :href="link.url"
                    :aria-current="link.active ? 'page' : undefined"
                    :class="
                        link.active
                            ? 'bg-primary text-primary-foreground'
                            : 'hover:bg-muted'
                    "
                    class="rounded-md border px-3 py-2 text-sm"
                    preserve-scroll
                    >{{
                        index === 0
                            ? 'Anterior'
                            : index === links.length - 1
                              ? 'Siguiente'
                              : link.label
                    }}</Link
                >
                <span
                    v-else
                    class="rounded-md border px-3 py-2 text-sm text-muted-foreground"
                    >{{
                        index === 0
                            ? 'Anterior'
                            : index === links.length - 1
                              ? 'Siguiente'
                              : link.label
                    }}</span
                >
            </template>
        </nav>
    </div>
</template>
