<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import {
    ArrowRight,
    Compass,
    MapPin,
    Mountain,
    ShieldCheck,
} from '@lucide/vue';
import { ref } from 'vue';
import { dashboard, login, register } from '@/routes';

defineProps<{ modules: Record<string, string> }>();
const selected = ref('hoteles');
</script>

<template>
    <Head title="Descubre Popayán">
        <meta
            name="description"
            content="Tu próximo viaje comienza en Popayán. Descubre nuestra plataforma de turismo y crea tu cuenta."
        />
    </Head>
    <div class="min-h-screen bg-[#f7f5ee] text-[#173c32]">
        <header
            class="mx-auto flex max-w-7xl flex-wrap items-center justify-between gap-5 px-6 py-6 lg:px-12"
        >
            <Link
                href="/"
                class="flex items-center gap-3 font-semibold tracking-tight"
            >
                <span
                    class="flex size-10 items-center justify-center rounded-full bg-[#173c32] text-white"
                    ><Compass class="size-5"
                /></span>
                <span
                    >Popayán<span
                        class="block text-xs font-normal tracking-[0.22em] uppercase"
                        >Turismo</span
                    ></span
                >
            </Link>
            <nav
                aria-label="Navegación principal"
                class="flex items-center gap-5 text-sm"
            >
                <Link href="/explorar" class="hover:underline">Explorar</Link>
                <Link
                    v-if="$page.props.auth.user"
                    :href="dashboard()"
                    class="rounded-full bg-[#173c32] px-5 py-3 text-white"
                    >Mi panel</Link
                >
                <template v-else>
                    <Link :href="login()" class="hover:underline"
                        >Ingresar</Link
                    >
                    <Link
                        :href="register()"
                        class="rounded-full bg-[#173c32] px-5 py-3 text-white"
                        >Crear cuenta</Link
                    >
                </template>
            </nav>
        </header>
        <main>
            <section
                class="mx-auto grid max-w-7xl items-center gap-12 px-6 py-16 lg:grid-cols-2 lg:px-12 lg:py-24"
            >
                <div>
                    <p
                        class="mb-6 flex items-center gap-2 text-xs font-semibold tracking-[0.2em] uppercase"
                    >
                        <MapPin class="size-4" /> Popayán, Colombia
                    </p>
                    <h1
                        class="max-w-xl font-serif text-6xl leading-[1.05] tracking-tight sm:text-7xl"
                    >
                        El comienzo de<br />una gran
                        <em class="text-[#a45432]">historia.</em>
                    </h1>
                    <p
                        class="mt-7 max-w-md text-lg leading-relaxed text-[#53685f]"
                    >
                        Nuevos caminos, encuentros y lugares por descubrir.
                        Prepara tu próximo viaje desde la Ciudad Blanca.
                    </p>
                    <Link
                        :href="register()"
                        class="mt-9 inline-flex items-center gap-4 rounded-full bg-[#173c32] px-7 py-4 font-medium text-white transition-colors hover:bg-[#285b49]"
                        >Comienza tu viaje <ArrowRight class="size-4"
                    /></Link>
                    <p class="mt-5 text-xs text-[#53685f]">
                        Servicios de proveedores · Cotizaciones a tu medida
                    </p>
                </div>
                <div
                    aria-hidden="true"
                    class="relative flex aspect-[5/4] items-end justify-center overflow-hidden rounded-t-[45%] rounded-b-3xl bg-[#e6dcca] px-8 pt-14"
                >
                    <div
                        class="absolute top-12 right-12 size-24 rounded-full bg-[#c67d4d]"
                    ></div>
                    <Mountain
                        class="absolute -bottom-5 left-0 size-80 text-[#6a8064]"
                        stroke-width="0.5"
                    />
                    <div
                        class="relative flex h-64 w-64 items-end justify-center gap-4 rounded-t-[8rem] bg-[#fffcf2] px-8 shadow-xl"
                    >
                        <div
                            class="mb-0 h-32 w-16 rounded-t-full border-8 border-[#d1b895] bg-[#173c32]"
                        ></div>
                        <div
                            class="absolute -top-7 left-1/2 h-12 w-2 -translate-x-1/2 bg-[#173c32]"
                        ></div>
                        <div
                            class="absolute -top-3 left-1/2 h-2 w-8 -translate-x-1/2 bg-[#173c32]"
                        ></div>
                    </div>
                    <div
                        class="absolute right-6 bottom-6 rounded-xl bg-[#f7f5ee] px-5 py-3 text-sm"
                    >
                        Un destino. Mil posibilidades.
                    </div>
                </div>
            </section>
            <section
                id="explorar"
                class="border-t border-[#173c32]/10 bg-white/50 px-6 py-16 lg:px-12"
            >
                <div class="mx-auto max-w-6xl">
                    <div class="flex flex-wrap items-end justify-between gap-4">
                        <div>
                            <p class="mb-3 text-xs tracking-[0.2em] uppercase">
                                Planea a tu manera
                            </p>
                            <h2 class="font-serif text-4xl">
                                Todo un mundo por explorar.
                            </h2>
                        </div>
                        <Link href="/explorar" class="text-sm underline"
                            >Ver catálogo</Link
                        >
                    </div>
                    <div
                        role="group"
                        aria-label="Categorías turísticas"
                        class="mt-8 flex flex-wrap gap-2"
                    >
                        <button
                            v-for="(label, key) in modules"
                            :key="key"
                            type="button"
                            :aria-pressed="selected === key"
                            :class="
                                selected === key
                                    ? 'bg-[#173c32] text-white'
                                    : 'bg-white text-[#173c32] hover:bg-[#e6dcca]'
                            "
                            class="rounded-full border border-[#173c32]/15 px-5 py-3 text-sm transition-colors"
                            @click="selected = key"
                        >
                            {{ label }}
                        </button>
                    </div>
                    <div
                        aria-live="polite"
                        class="mt-6 rounded-2xl border border-[#173c32]/10 bg-white p-7"
                    >
                        <h3 class="text-xl font-semibold">
                            {{ modules[selected] }}
                        </h3>
                        <p
                            class="mt-2 max-w-2xl leading-relaxed text-[#53685f]"
                        >
                            Consulta los servicios publicados de nuestros
                            proveedores y solicita información para tu viaje.
                        </p>
                        <Link
                            :href="
                                selected === 'blog'
                                    ? '/blog'
                                    : `/explorar?category=${selected}`
                            "
                            class="mt-5 inline-flex items-center gap-2 rounded-full bg-[#173c32] px-5 py-3 text-sm text-white"
                            >{{
                                selected === 'blog'
                                    ? 'Leer historias'
                                    : 'Explorar servicios'
                            }}
                            <ArrowRight class="size-4"
                        /></Link>
                    </div>
                </div>
            </section>
            <section
                class="mx-auto flex max-w-6xl items-start gap-4 px-6 py-12 text-sm text-[#53685f]"
            >
                <ShieldCheck class="size-6 shrink-0" />
                <p>
                    Tu cuenta es el primer paso. Por ahora puedes registrarte,
                    verificar tu correo y configurar la seguridad de tu perfil.
                    Explora el catálogo, guarda favoritos y solicita una
                    cotización. Las reservas y los pagos aún no están
                    disponibles.
                </p>
            </section>
        </main>
        <footer
            class="border-t border-[#173c32]/10 px-6 py-7 text-center text-xs text-[#53685f]"
        >
            Popayán Turismo · Hecho para descubrir nuevos caminos.
        </footer>
    </div>
</template>
