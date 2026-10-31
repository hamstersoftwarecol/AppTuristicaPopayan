<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import PublicShell from '@/components/PublicShell.vue';
import { shortDate } from '@/types/tourism';
import type { Post } from '@/types/tourism';
defineProps<{ post: Post }>();
</script>
<template>
    <Head :title="post.title"
        ><meta name="description" :content="post.excerpt" /></Head
    ><PublicShell
        ><article class="mx-auto max-w-3xl">
            <Link href="/blog" class="text-sm underline underline-offset-4"
                >Volver al blog</Link
            >
            <p class="mt-10 text-xs tracking-widest uppercase">
                {{ post.category }}
            </p>
            <h1 class="mt-4 font-serif text-4xl sm:text-6xl">
                {{ post.title }}
            </h1>
            <p class="mt-6 text-xl leading-relaxed text-[#53685f]">
                {{ post.excerpt }}
            </p>
            <p v-if="post.published_at" class="mt-5 text-sm text-[#53685f]">
                {{ shortDate(post.published_at) }}
            </p>
            <div
                class="mt-10 border-t border-[#173c32]/15 pt-8 text-lg leading-9 whitespace-pre-line"
            >
                {{ post.body }}
            </div>
        </article></PublicShell
    >
</template>
