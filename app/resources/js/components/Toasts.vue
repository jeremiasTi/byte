<script setup>
import { PhCheckCircle, PhWarningCircle, PhX } from '@phosphor-icons/vue';
import { useUi } from '../stores/ui';

const ui = useUi();
</script>

<template>
    <div class="pointer-events-none fixed inset-x-0 bottom-4 z-[60] flex flex-col items-center gap-2 px-4" aria-live="polite">
        <TransitionGroup name="toast">
            <div
                v-for="t in ui.toasts" :key="t.id"
                class="pointer-events-auto flex w-full max-w-sm items-center gap-3 rounded-xl bg-shell px-4 py-3 text-sm text-white shadow-lg shadow-slate-900/20"
                role="status"
            >
                <component :is="t.type === 'error' ? PhWarningCircle : PhCheckCircle" :size="22" weight="fill" :class="t.type === 'error' ? 'text-red-300' : 'text-emerald-300'" aria-hidden="true" />
                <span class="flex-1">{{ t.message }}</span>
                <button class="grid size-8 place-items-center rounded-md text-shell-text hover:bg-shell-hover hover:text-white" aria-label="Fechar aviso" @click="ui.dismiss(t.id)"><PhX :size="16" /></button>
            </div>
        </TransitionGroup>
    </div>
</template>

<style>
.toast-enter-active, .toast-leave-active { transition: opacity .2s ease-out, transform .2s ease-out; }
.toast-enter-from, .toast-leave-to { opacity: 0; transform: translateY(8px); }
</style>
