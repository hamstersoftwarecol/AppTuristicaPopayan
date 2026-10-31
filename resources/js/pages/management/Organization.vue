<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import FormErrors from '@/components/FormErrors.vue';
import OperationNotice from '@/components/OperationNotice.vue';
import { statusLabels } from '@/types/tourism';
import type { Provider } from '@/types/tourism';
defineProps<{
    providers: Pick<
        Provider,
        'id' | 'name' | 'contact_email' | 'phone' | 'website' | 'status'
    >[];
}>();
const editing = ref<number | null>(null);
const form = useForm({ contact_email: '', phone: '', website: '' });
function edit(
    provider: Pick<Provider, 'id' | 'contact_email' | 'phone' | 'website'>,
) {
    editing.value = provider.id;
    form.clearErrors();
    Object.assign(form, {
        contact_email: provider.contact_email,
        phone: provider.phone || '',
        website: provider.website || '',
    });
}
function save() {
    if (editing.value)
        form.put(`/gestion/mi-organizacion/${editing.value}`, {
            preserveScroll: true,
            onSuccess: () => {
                editing.value = null;
            },
        });
}
</script>
<template>
    <Head title="Mi organización" />
    <div class="workspace">
        <div>
            <p class="mb-2 text-sm text-muted-foreground">
                Portal del proveedor
            </p>
            <h1 class="workspace-heading">Mi organización</h1>
            <p class="mt-3 text-muted-foreground">
                Mantén tus datos de contacto al día. Administración gestiona la
                activación y los responsables.
            </p>
        </div>
        <OperationNotice />
        <div
            v-if="!providers.length"
            class="rounded-xl border border-dashed p-8 text-muted-foreground"
        >
            Tu cuenta aún no está vinculada a un proveedor. Solicita la
            vinculación al administrador.
        </div>
        <div class="grid items-start gap-6 lg:grid-cols-2">
            <article
                v-for="provider in providers"
                :key="provider.id"
                class="rounded-xl border p-6"
            >
                <div class="flex flex-wrap justify-between gap-3">
                    <h2 class="text-xl font-semibold">{{ provider.name }}</h2>
                    <span class="rounded-full bg-muted px-3 py-1 text-xs">{{
                        statusLabels[provider.status]
                    }}</span>
                </div>
                <p class="mt-4 text-sm">{{ provider.contact_email }}</p>
                <p
                    v-if="provider.phone"
                    class="mt-2 text-sm text-muted-foreground"
                >
                    {{ provider.phone }}
                </p>
                <p
                    v-if="provider.website"
                    class="mt-2 text-sm break-all text-muted-foreground"
                >
                    {{ provider.website }}
                </p>
                <button class="action-secondary mt-5" @click="edit(provider)">
                    Editar contacto
                </button>
                <form
                    v-if="editing === provider.id"
                    class="mt-6 grid gap-4 border-t pt-6"
                    @submit.prevent="save"
                >
                    <FormErrors :errors="form.errors" /><label class="field"
                        >Correo de contacto<input
                            v-model="form.contact_email"
                            class="field-control"
                            type="email"
                            required
                            maxlength="255" /></label
                    ><label class="field"
                        >Teléfono<input
                            v-model="form.phone"
                            class="field-control"
                            type="tel"
                            maxlength="40" /></label
                    ><label class="field"
                        >Sitio web (HTTPS)<input
                            v-model="form.website"
                            class="field-control"
                            type="url"
                            maxlength="255"
                    /></label>
                    <div class="flex gap-3">
                        <button
                            class="action-primary"
                            :disabled="form.processing"
                        >
                            Guardar contacto</button
                        ><button
                            type="button"
                            class="action-secondary"
                            @click="editing = null"
                        >
                            Cancelar
                        </button>
                    </div>
                </form>
            </article>
        </div>
        <Link href="/gestion/servicios" class="action-primary self-start"
            >Gestionar mi inventario</Link
        >
    </div>
</template>
