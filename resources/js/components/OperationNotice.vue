<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
const page = usePage();
</script>
<template>
    <div
        v-if="page.props.success"
        role="status"
        class="rounded-lg border border-emerald-300 bg-emerald-50 p-4 text-sm text-emerald-900"
    >
        {{ page.props.success }}
    </div>
</template>
