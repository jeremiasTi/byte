<script setup>
defineProps({ title: String, subtitle: String });
</script>

<template>
    <div class="mb-7 flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="text-2xl">{{ title }}</h1>
            <p v-if="subtitle" class="mt-1 text-muted">{{ subtitle }}</p>
        </div>
        <div class="flex flex-wrap items-center gap-2"><slot /></div>
    </div>
</template>
