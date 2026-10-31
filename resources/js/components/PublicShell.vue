<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { Compass } from '@lucide/vue';
import OperationNotice from '@/components/OperationNotice.vue';
</script>
<template>
    <div class="min-h-screen bg-[#f7f5ee] text-[#173c32]">
        <header class="border-b border-[#173c32]/10 bg-white/70">
            <nav
                aria-label="Navegación principal"
                class="mx-auto flex max-w-7xl flex-wrap items-center justify-between gap-4 px-6 py-5"
            >
                <Link href="/" class="flex items-center gap-2 font-semibold"
                    ><Compass class="size-7" />Popayán Turismo</Link
                >
                <div class="flex flex-wrap items-center gap-5 text-sm">
                    <Link href="/explorar">Explorar</Link
                    ><Link href="/blog">Blog</Link>
                    <Link
                        v-if="$page.props.auth.user"
                        href="/dashboard"
                        class="rounded-full bg-[#173c32] px-5 py-2 text-white"
                        >Mi panel</Link
                    >
                    <Link
                        v-else
                        href="/login"
                        class="rounded-full bg-[#173c32] px-5 py-2 text-white"
                        >Ingresar</Link
                    >
                </div>
            </nav>
        </header>
        <main class="mx-auto max-w-7xl px-6 py-10">
            <OperationNotice /><slot />
        </main>
        <footer
            class="mx-auto max-w-7xl border-t border-[#173c32]/10 px-6 py-8 text-sm text-[#53685f]"
        >
            Popayán Turismo · Las solicitudes están sujetas a confirmación. No
            se procesan pagos en esta plataforma.
        </footer>
    </div>
</template>
