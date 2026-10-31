<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import DataPagination from '@/components/DataPagination.vue';
import type { Paginated } from '@/types/tourism';
type Entry = {
    id: number;
    action: string;
    actor: { name: string } | null;
    subject_type: string;
    subject_id: number;
    metadata: Record<string, unknown>;
    created_at: string;
};
const props = defineProps<{
    logs: Paginated<Entry>;
    actions: string[];
    filters: { action?: string };
}>();
const action = ref(props.filters.action || '');
</script>
<template>
    <Head title="Auditoría" />
    <div class="workspace">
        <div>
            <p class="mb-2 text-sm text-muted-foreground">
                Administración / Trazabilidad
            </p>
            <h1 class="workspace-heading">Registro de cambios</h1>
            <p class="mt-3 text-muted-foreground">
                Consulta quién modificó proveedores, inventario, artículos,
                solicitudes y roles.
            </p>
        </div>
        <form
            class="flex max-w-xl gap-3"
            @submit.prevent="
                router.get(
                    '/gestion/auditoria',
                    { action },
                    { preserveState: true },
                )
            "
        >
            <label class="field flex-1"
                >Tipo de acción<select v-model="action" class="field-control">
                    <option value="">Todas las acciones</option>
                    <option v-for="item in actions" :key="item" :value="item">
                        {{ item }}
                    </option>
                </select></label
            ><button class="action-secondary self-end">Filtrar</button>
        </form>
        <div
            v-if="!logs.data.length"
            class="rounded-xl border border-dashed p-8 text-muted-foreground"
        >
            Todavía no hay cambios registrados con este filtro.
        </div>
        <ol class="space-y-3">
            <li
                v-for="log in logs.data"
                :key="log.id"
                class="rounded-xl border p-5"
            >
                <div class="flex flex-wrap justify-between gap-3">
                    <h2 class="font-mono text-sm font-semibold">
                        {{ log.action }}
                    </h2>
                    <time class="text-sm text-muted-foreground">{{
                        new Date(log.created_at).toLocaleString('es-CO')
                    }}</time>
                </div>
                <p class="mt-3 text-sm">
                    {{ log.actor?.name || 'Cuenta eliminada / sistema' }} ·
                    {{ log.subject_type }} #{{ log.subject_id }}
                </p>
                <details
                    v-if="Object.keys(log.metadata).length"
                    class="mt-3 text-sm"
                >
                    <summary class="cursor-pointer">Ver cambios</summary>
                    <pre
                        class="mt-3 overflow-x-auto rounded-lg bg-muted p-4 text-xs"
                        >{{ JSON.stringify(log.metadata, null, 2) }}</pre>
                </details>
            </li>
        </ol>
        <DataPagination :links="logs.links" :total="logs.total" />
    </div>
</template>
