<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
defineProps<{
    communications: { mailEnabled: boolean; mailer: string; queue: string };
    wompi: {
        ready: boolean;
        enabled: boolean;
        missing: string[];
        webhook: string;
    };
    travelProviders: {
        name: string;
        baseUrlConfigured: boolean;
        documentation: string;
    }[];
}>();
</script>
<template>
    <Head title="Integraciones" />
    <div class="workspace">
        <div>
            <p class="mb-2 text-sm text-muted-foreground">
                Superadministración / Conexiones
            </p>
            <h1 class="workspace-heading">Integraciones elegidas</h1>
            <p class="mt-3 max-w-3xl text-muted-foreground">
                Wompi Colombia para pagos, Netactica Colombia para contenido
                turístico y Avianca Link para la conexión aérea directa.
            </p>
        </div>
        <section class="rounded-xl border p-6">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <h2 class="text-xl font-semibold">Wompi Colombia</h2>
                <span class="rounded-full bg-muted px-3 py-1 text-xs">{{
                    wompi.ready
                        ? 'Configurado para pruebas'
                        : 'Configuración pendiente'
                }}</span>
            </div>
            <p class="mt-4 text-sm text-muted-foreground">
                Checkout y validación de eventos disponibles exclusivamente en
                sandbox. Esta pantalla comprueba la configuración local; no
                valida las credenciales contra Wompi. Los pagos de producción
                están bloqueados en esta entrega.
            </p>
            <p class="mt-4 text-sm">
                Inicio de checkout:
                {{
                    wompi.enabled
                        ? 'Habilitado por configuración'
                        : 'Desactivado'
                }}
            </p>
            <div
                v-if="wompi.missing.length"
                class="mt-4 rounded-lg bg-muted p-4"
            >
                <p class="text-sm font-medium">
                    Variables de sandbox pendientes o con prefijo incorrecto
                </p>
                <ul
                    class="mt-3 list-inside list-disc space-y-1 font-mono text-xs"
                >
                    <li v-for="key in wompi.missing" :key="key">{{ key }}</li>
                </ul>
            </div>
            <p class="mt-5 text-sm font-medium">Webhook de pruebas</p>
            <code
                class="mt-2 block rounded-lg bg-muted p-3 text-xs break-all"
                >{{ wompi.webhook }}</code
            >
            <p class="mt-2 text-xs text-muted-foreground">
                Wompi necesita alcanzar esta dirección por HTTPS. Una URL
                localhost no es accesible desde sus servidores.
            </p>
            <a
                href="https://docs.wompi.co/docs/colombia/inicio-rapido/"
                target="_blank"
                rel="noopener noreferrer"
                class="mt-5 inline-block text-sm underline underline-offset-4"
                >Documentación oficial de Wompi</a
            >
        </section>
        <div class="grid gap-5 lg:grid-cols-2">
            <section
                v-for="provider in travelProviders"
                :key="provider.name"
                class="rounded-xl border p-6"
            >
                <p class="text-xs text-muted-foreground">
                    SELECCIONADO · CONECTOR PENDIENTE
                </p>
                <h2 class="mt-3 text-xl font-semibold">{{ provider.name }}</h2>
                <p class="mt-4 text-sm text-muted-foreground">
                    Pendiente de documentación contractual, autenticación,
                    credenciales y acceso de pruebas. No se consultan tarifas,
                    disponibilidad ni se emiten reservas mediante este proveedor
                    todavía.
                </p>
                <p class="mt-4 text-sm">
                    URL base de referencia:
                    {{
                        provider.baseUrlConfigured
                            ? 'Registrada en el entorno'
                            : 'Por definir con el proveedor'
                    }}
                </p>
                <a
                    :href="provider.documentation"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="mt-5 inline-block text-sm underline underline-offset-4"
                    >Sitio oficial</a
                >
            </section>
        </div>
        <section class="rounded-xl border p-6">
            <h2 class="text-xl font-semibold">Avisos de operación</h2>
            <p class="mt-4 text-sm">Notificaciones internas: activas.</p>
            <p class="mt-2 text-sm">
                Correo operativo:
                {{
                    communications.mailEnabled
                        ? 'Habilitado por configuración'
                        : 'Desactivado'
                }}.
            </p>
            <p class="mt-2 text-sm text-muted-foreground">
                Transporte: {{ communications.mailer }} · Cola:
                {{ communications.queue }}. Esta vista no valida la entrega del
                correo. Configura y prueba SMTP antes de habilitar los envíos
                operativos.
            </p>
        </section>
        <p class="max-w-3xl text-sm text-muted-foreground">
            Configura los secretos en el archivo de entorno del servidor. Esta
            vista nunca muestra sus valores ni permite copiarlos. Una URL base
            por sí sola no habilita un conector.
        </p>
    </div>
</template>
