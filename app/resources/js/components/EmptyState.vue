<script setup>
defineProps({ title: String, text: String });
</script>

<template>
    <div class="card flex flex-col items-center px-6 py-12 text-center">
        <div class="mb-4 grid size-14 place-items-center rounded-2xl bg-brand-50 text-brand-600"><slot name="icon" /></div>
        <p class="font-display text-base font-semibold">{{ title }}</p>
        <p v-if="text" class="mt-1 max-w-sm text-sm text-muted">{{ text }}</p>
        <div class="mt-5"><slot /></div>
    </div>
</template>
