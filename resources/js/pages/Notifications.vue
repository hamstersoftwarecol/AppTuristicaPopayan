<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { Bell, CheckCheck } from '@lucide/vue';
import { ref } from 'vue';
import DataPagination from '@/components/DataPagination.vue';
import OperationNotice from '@/components/OperationNotice.vue';
import type { Paginated } from '@/types/tourism';
type Notice = {
    id: string;
    data: { title: string; message: string; url: string; kind: string };
    read_at: string | null;
    created_at: string;
};
const props = defineProps<{
    notifications: Paginated<Notice>;
    onlyUnread: boolean;
}>();
const unread = ref(props.onlyUnread);
const form = useForm({});
function safePath(path: string) {
    return ['/mis-reservas', '/mis-solicitudes'].includes(path)
        ? path
        : '/dashboard';
}
</script>
<template>
    <Head title="Notificaciones" />
    <div class="workspace">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <p class="mb-2 text-sm text-muted-foreground">
                    Mi cuenta / Avisos
                </p>
                <h1 class="workspace-heading">Tus notificaciones</h1>
                <p class="mt-3 text-muted-foreground">
                    Novedades de tus reservas locales y respuestas a tus
                    cotizaciones.
                </p>
            </div>
            <button
                class="action-secondary"
                :disabled="
                    form.processing || !$page.props.auth.unread_notifications
                "
                @click="
                    form.patch('/notificaciones/leer-todas', {
                        preserveScroll: true,
                    })
                "
            >
                <CheckCheck class="mr-2 size-4" />Marcar todas como leídas
            </button>
        </div>
        <OperationNotice />
        <form
            class="flex flex-wrap items-center gap-4"
            @submit.prevent="
                router.get(
                    '/notificaciones',
                    { unread: unread ? 1 : 0 },
                    { preserveState: true },
                )
            "
        >
            <label class="flex items-center gap-2 text-sm"
                ><input v-model="unread" type="checkbox" />Solo sin leer</label
            ><button class="action-secondary">Aplicar filtro</button>
        </form>
        <div
            v-if="!notifications.data.length"
            class="rounded-xl border border-dashed p-10 text-center"
        >
            <Bell class="mx-auto size-8 text-muted-foreground" />
            <h2 class="mt-4 text-lg font-semibold">
                No hay notificaciones para mostrar
            </h2>
            <p class="mt-3 text-sm text-muted-foreground">
                Los cambios de tus reservas y cotizaciones aparecerán aquí.
            </p>
        </div>
        <div class="space-y-4">
            <article
                v-for="notice in notifications.data"
                :key="notice.id"
                class="rounded-xl border p-6"
                :class="!notice.read_at ? 'border-primary/30 bg-muted/30' : ''"
            >
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <h2 class="font-semibold">{{ notice.data.title }}</h2>
                    <span
                        v-if="!notice.read_at"
                        class="rounded-full bg-primary px-3 py-1 text-xs text-primary-foreground"
                        >Sin leer</span
                    >
                </div>
                <p
                    class="mt-3 text-sm leading-relaxed break-words text-muted-foreground"
                >
                    {{ notice.data.message }}
                </p>
                <p class="mt-3 text-xs text-muted-foreground">
                    {{ new Date(notice.created_at).toLocaleString('es-CO') }}
                </p>
                <div class="mt-5 flex flex-wrap gap-3">
                    <Link
                        :href="safePath(notice.data.url)"
                        class="action-primary"
                        >Consultar detalle</Link
                    ><button
                        v-if="!notice.read_at"
                        class="action-secondary"
                        :disabled="form.processing"
                        @click="
                            form.patch(`/notificaciones/${notice.id}/leer`, {
                                preserveScroll: true,
                            })
                        "
                    >
                        Marcar como leída
                    </button>
                </div>
            </article>
        </div>
        <DataPagination
            :links="notifications.links"
            :total="notifications.total"
        />
    </div>
</template>
