<script setup>
// Marca do Byte: monograma "B" geométrico. `dark` = sobre fundo azul-marinho (quadrado branco).
defineProps({ dark: Boolean, size: { type: Number, default: 36 }, wordmark: { type: Boolean, default: true }, tagline: Boolean });
</script>

<template>
    <div class="flex items-center gap-2.5">
        <svg :width="size" :height="size" viewBox="0 0 32 32" role="img" aria-label="Byte" class="shrink-0">
            <rect width="32" height="32" rx="8" :fill="dark ? '#ffffff' : '#12264a'" />
            <path
                d="M11 8v16M11 8h6a4 4 0 0 1 0 8h-6m0 0h7a4 4 0 0 1 0 8h-7"
                fill="none" :stroke="dark ? '#12264a' : '#ffffff'" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"
            />
        </svg>
        <div v-if="wordmark" class="leading-tight">
            <span class="block font-display text-xl font-semibold tracking-tight" :class="dark ? 'text-white' : 'text-shell'">Byte</span>
            <span v-if="tagline" class="block text-[11px]" :class="dark ? 'text-shell-text' : 'text-muted'">Organização dos estudos</span>
        </div>
    </div>
</template>
