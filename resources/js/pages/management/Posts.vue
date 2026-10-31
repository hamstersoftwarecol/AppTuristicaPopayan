<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import DataPagination from '@/components/DataPagination.vue';
import FormErrors from '@/components/FormErrors.vue';
import OperationNotice from '@/components/OperationNotice.vue';
import type { Paginated, Post } from '@/types/tourism';
defineProps<{ posts: Paginated<Post> }>();
const editing = ref<number | null>(null);
const form = useForm({
    title: '',
    slug: '',
    category: '',
    excerpt: '',
    body: '',
    published_at: '',
});
function edit(post: Post) {
    editing.value = post.id;
    form.clearErrors();
    Object.assign(form, {
        title: post.title,
        slug: post.slug,
        category: post.category,
        excerpt: post.excerpt,
        body: post.body,
        published_at: post.published_at
            ? new Date(post.published_at).toLocaleDateString('sv-SE', {
                  timeZone: 'America/Bogota',
              })
            : '',
    });
}
function reset() {
    editing.value = null;
    form.reset();
    form.clearErrors();
}
function save() {
    form.transform((data) => ({
        ...data,
        published_at: data.published_at
            ? `${data.published_at} 00:00:00`
            : null,
    }));
    if (editing.value)
        form.put(`/gestion/articulos/${editing.value}`, {
            preserveScroll: true,
            onSuccess: reset,
        });
    else
        form.post('/gestion/articulos', {
            preserveScroll: true,
            onSuccess: reset,
        });
}
</script>
<template>
    <Head title="Contenido del blog" />
    <div class="workspace">
        <div>
            <p class="mb-2 text-sm text-muted-foreground">Contenido / Blog</p>
            <h1 class="workspace-heading">Historias para descubrir</h1>
            <p class="mt-3 text-muted-foreground">
                Crea artículos, guarda borradores y programa su publicación.
            </p>
        </div>
        <OperationNotice />
        <div class="grid items-start gap-8 xl:grid-cols-[1fr_1.4fr]">
            <section>
                <div
                    v-if="!posts.data.length"
                    class="rounded-xl border border-dashed p-8 text-muted-foreground"
                >
                    Todavía no hay artículos. Escribe el primero.
                </div>
                <div class="space-y-3">
                    <article
                        v-for="post in posts.data"
                        :key="post.id"
                        class="rounded-xl border p-5"
                    >
                        <p class="text-xs text-muted-foreground">
                            {{ post.category }} ·
                            {{
                                !post.published_at
                                    ? 'Borrador'
                                    : new Date(post.published_at) > new Date()
                                      ? 'Programado'
                                      : 'Publicado'
                            }}
                        </p>
                        <h2 class="mt-2 font-semibold">{{ post.title }}</h2>
                        <p class="mt-3 text-sm text-muted-foreground">
                            {{ post.excerpt }}
                        </p>
                        <div class="mt-4 flex gap-3">
                            <button
                                class="action-secondary"
                                @click="edit(post)"
                            >
                                Editar</button
                            ><Link
                                v-if="
                                    post.published_at &&
                                    new Date(post.published_at) <= new Date()
                                "
                                :href="`/blog/${post.slug}`"
                                class="action-secondary"
                                >Ver artículo</Link
                            >
                        </div>
                    </article>
                </div>
                <DataPagination :links="posts.links" :total="posts.total" />
            </section>
            <section class="rounded-xl border bg-card p-6">
                <h2 class="text-xl font-semibold">
                    {{ editing ? 'Editar artículo' : 'Nuevo artículo' }}
                </h2>
                <form class="mt-6 grid gap-4" @submit.prevent="save">
                    <FormErrors :errors="form.errors" /><label class="field"
                        >Título<input
                            v-model="form.title"
                            class="field-control"
                            required
                            maxlength="255" /></label
                    ><label class="field"
                        >Dirección del artículo (slug)<input
                            v-model="form.slug"
                            class="field-control"
                            required
                            pattern="[a-z0-9]+(-[a-z0-9]+)*"
                            placeholder="descubre-popayan"
                            maxlength="255" /></label
                    ><label class="field"
                        >Categoría<input
                            v-model="form.category"
                            class="field-control"
                            required
                            maxlength="80" /></label
                    ><label class="field"
                        >Resumen<textarea
                            v-model="form.excerpt"
                            class="field-control"
                            required
                            maxlength="500"
                            rows="3"
                        /></label
                    ><label class="field"
                        >Contenido (texto sin formato)<textarea
                            v-model="form.body"
                            class="field-control"
                            required
                            minlength="20"
                            maxlength="50000"
                            rows="12"
                        /></label
                    ><label class="field"
                        >Fecha de publicación<input
                            v-model="form.published_at"
                            class="field-control"
                            type="date"
                        /><span
                            class="text-xs font-normal text-muted-foreground"
                            >Déjala vacía para guardar como borrador. La
                            publicación comienza a medianoche, hora de
                            Colombia.</span
                        ></label
                    >
                    <div class="flex gap-3">
                        <button
                            class="action-primary"
                            :disabled="form.processing"
                        >
                            Guardar artículo</button
                        ><button
                            v-if="editing"
                            type="button"
                            class="action-secondary"
                            @click="reset"
                        >
                            Cancelar edición
                        </button>
                    </div>
                </form>
            </section>
        </div>
    </div>
</template>
