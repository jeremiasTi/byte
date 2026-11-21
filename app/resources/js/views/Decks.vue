<script setup>
import { onMounted, reactive, ref } from 'vue';
import { PhCards, PhPlay, PhPlus, PhTrash } from '@phosphor-icons/vue';
import { api } from '../api';
import { useUi } from '../stores/ui';
import Modal from '../components/Modal.vue';
import PageHeader from '../components/PageHeader.vue';
import EmptyState from '../components/EmptyState.vue';

const ui = useUi();
const decks = ref([]);
const subjects = ref([]);
const loading = ref(true);
const creating = ref(false);
const form = reactive({ name: '', subject_id: '' });

async function load() {
    decks.value = await api.get('/decks');
    loading.value = false;
}
onMounted(async () => {
    subjects.value = await api.get('/subjects');
    await load();
});

async function create() {
    await api.post('/decks', { name: form.name, subject_id: form.subject_id || null });
    Object.assign(form, { name: '', subject_id: '' });
    creating.value = false;
    ui.toast('Baralho criado.');
    await load();
}
async function remove(d) {
    if (!confirm(`Excluir o baralho "${d.name}" e seus cartões?`)) return;
    await api.del(`/decks/${d.id}`);
    ui.toast('Baralho excluído.');
    await load();
}
</script>

<template>
    <div>
        <PageHeader title="Flashcards" subtitle="Revisão espaçada: acertou, o cartão volta mais tarde; errou, volta hoje.">
            <button class="btn btn-primary" @click="creating = true"><PhPlus :size="18" weight="bold" aria-hidden="true" />Novo baralho</button>
        </PageHeader>

        <div v-if="loading" class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3"><div v-for="i in 3" :key="i" class="h-44 animate-pulse rounded-xl bg-line" /></div>

        <EmptyState v-else-if="!decks.length" title="Crie seu primeiro baralho" text="Transforme o conteúdo da aula em perguntas e respostas e revise no ritmo certo.">
            <template #icon><PhCards :size="28" /></template>
            <button class="btn btn-primary" @click="creating = true"><PhPlus :size="18" weight="bold" />Novo baralho</button>
        </EmptyState>

        <div v-else class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <article v-for="d in decks" :key="d.id" class="card flex flex-col">
                <div class="flex items-start justify-between gap-2">
                    <div class="min-w-0">
                        <h2 class="truncate text-base font-semibold">{{ d.name }}</h2>
                        <p v-if="d.subject" class="mt-0.5 flex items-center gap-1.5 text-sm text-muted"><span class="size-2 rounded-full" :style="{ background: d.subject.color }" aria-hidden="true" />{{ d.subject.name }}</p>
                        <p v-else class="mt-0.5 text-sm text-muted">Sem disciplina</p>
                    </div>
                    <button class="btn-icon btn-icon-danger -mr-2 -mt-2" aria-label="Excluir baralho" @click="remove(d)"><PhTrash :size="20" /></button>
                </div>

                <div class="my-5 flex items-end justify-between">
                    <div>
                        <p class="font-display text-3xl font-semibold tabular-nums" :class="d.due_count ? 'text-brand-700' : 'text-success'">{{ d.due_count }}</p>
                        <p class="text-sm text-muted">para revisar hoje</p>
                    </div>
                    <p class="text-sm text-muted tabular-nums">{{ d.flashcards_count }} cartões</p>
                </div>

                <div class="mt-auto flex gap-2">
                    <router-link :to="`/flashcards/${d.id}?revisar=1`" class="btn btn-primary flex-1" :class="{ 'pointer-events-none opacity-50': !d.due_count }">
                        <PhPlay :size="16" weight="fill" aria-hidden="true" />Revisar
                    </router-link>
                    <router-link :to="`/flashcards/${d.id}`" class="btn btn-ghost">Cartões</router-link>
                </div>
            </article>
        </div>

        <Modal v-if="creating" title="Novo baralho" @close="creating = false">
            <form class="space-y-4" @submit.prevent="create">
                <div>
                    <label class="label" for="d-name">Nome</label>
                    <input id="d-name" v-model="form.name" required class="input" placeholder="Ex.: Normalização" />
                </div>
                <div>
                    <label class="label" for="d-sub">Disciplina</label>
                    <select id="d-sub" v-model="form.subject_id" class="input">
                        <option value="">Sem disciplina</option>
                        <option v-for="s in subjects" :key="s.id" :value="s.id">{{ s.name }}</option>
                    </select>
                </div>
                <div class="flex justify-end gap-2 border-t border-line pt-4">
                    <button type="button" class="btn btn-ghost" @click="creating = false">Cancelar</button>
                    <button class="btn btn-primary">Criar baralho</button>
                </div>
            </form>
        </Modal>
    </div>
</template>
