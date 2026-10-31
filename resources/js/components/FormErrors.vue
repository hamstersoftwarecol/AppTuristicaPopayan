<script setup lang="ts">
defineProps<{ errors: Record<string, string> }>();
</script>
<template>
    <div
        v-if="Object.keys(errors).length"
        role="alert"
        class="rounded-lg border border-red-300 bg-red-50 p-4 text-sm text-red-800"
    >
        <p class="font-medium">Revisa los siguientes datos:</p>
        <ul class="mt-2 list-inside list-disc">
            <li v-for="(error, field) in errors" :key="field">{{ error }}</li>
        </ul>
    </div>
</template>
