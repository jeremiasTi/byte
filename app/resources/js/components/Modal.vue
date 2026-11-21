<script setup>
import { nextTick, onBeforeUnmount, onMounted, ref } from 'vue';
import { PhX } from '@phosphor-icons/vue';

defineProps({ title: String, wide: Boolean });
const emit = defineEmits(['close']);
const box = ref(null);
const previous = document.activeElement;

const onKey = (e) => {
    if (e.key === 'Escape') emit('close');
    if (e.key === 'Tab' && box.value) {
        const f = [...box.value.querySelectorAll('a[href],button:not([disabled]),input:not([disabled]),select,textarea')];
        if (!f.length) return;
        const first = f[0], last = f[f.length - 1];
        if (e.shiftKey && document.activeElement === first) { e.preventDefault(); last.focus(); }
        else if (!e.shiftKey && document.activeElement === last) { e.preventDefault(); first.focus(); }
    }
};

onMounted(async () => {
    document.addEventListener('keydown', onKey);
    document.body.style.overflow = 'hidden';
    await nextTick();
    box.value?.querySelector('input,select,textarea')?.focus();
});
onBeforeUnmount(() => {
    document.removeEventListener('keydown', onKey);
    document.body.style.overflow = '';
    previous?.focus?.();
});
</script>

<template>
    <Teleport to="body">
        <div class="fixed inset-0 z-50 flex items-end justify-center bg-shell/60 p-0 sm:items-center sm:p-4" @mousedown.self="emit('close')">
            <div
                ref="box" role="dialog" aria-modal="true" :aria-label="title"
                class="modal-box max-h-[92vh] w-full overflow-y-auto rounded-t-2xl bg-white shadow-2xl shadow-slate-900/30 sm:rounded-2xl"
                :class="wide ? 'sm:max-w-2xl' : 'sm:max-w-lg'"
            >
                <div class="sticky top-0 flex items-center justify-between border-b border-line bg-white px-6 py-4">
                    <h2 class="text-lg font-semibold">{{ title }}</h2>
                    <button class="btn-icon -mr-2" aria-label="Fechar" @click="emit('close')"><PhX :size="20" /></button>
                </div>
                <div class="p-6"><slot /></div>
            </div>
        </div>
    </Teleport>
</template>

<style>
.modal-box { animation: modal-in .18s ease-out; }
@keyframes modal-in { from { opacity: 0; transform: translateY(12px); } to { opacity: 1; transform: none; } }
</style>
