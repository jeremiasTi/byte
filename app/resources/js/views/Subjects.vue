<script setup>
import { onMounted, reactive, ref } from 'vue';
import { PhBooks, PhClock, PhListChecks, PhPencilSimple, PhPlus, PhTrash } from '@phosphor-icons/vue';
import { api, fmtMinutes } from '../api';
import { useUi } from '../stores/ui';
import Modal from '../components/Modal.vue';
import PageHeader from '../components/PageHeader.vue';
import EmptyState from '../components/EmptyState.vue';

const ui = useUi();
const items = ref([]);
const loading = ref(true);
const editing = ref(null);
const form = reactive({ name: '', professor: '', color: '#2150a8' });
const errors = ref({});
const SWATCHES = ['#2150a8', '#0f7b4d', '#b4690e', '#0e7490', '#8a3a6b', '#b42318', '#475569'];

async function load() {
    items.value = await api.get('/subjects');
    loading.value = false;
}
onMounted(load);

function edit(s) {
    editing.value = s?.id ?? 'new';
    Object.assign(form, { name: s?.name ?? '', professor: s?.professor ?? '', color: s?.color ?? '#2150a8' });
    errors.value = {};
}

async function save() {
    try {
        editing.value === 'new' ? await api.post('/subjects', form) : await api.put(`/subjects/${editing.value}`, form);
        ui.toast(editing.value === 'new' ? 'Disciplina cadastrada.' : 'Disciplina atualizada.');
        editing.value = null;
        await load();
    } catch (e) {
        errors.value = e.errors;
    }
}

async function remove(s) {
    if (!confirm(`Excluir "${s.name}"? As tarefas ficarão sem disciplina.`)) return;
    await api.del(`/subjects/${s.id}`);
    ui.toast('Disciplina excluída.');
    await load();
}
</script>

<template>
    <div>
        <PageHeader title="Disciplinas" subtitle="As matérias do semestre, com o andamento de tarefas e o tempo dedicado a cada uma.">
            <button class="btn btn-primary" @click="edit(null)"><PhPlus :size="18" weight="bold" aria-hidden="true" />Nova disciplina</button>
        </PageHeader>

        <div v-if="loading" class="grid gap-4 sm:grid-cols-2"><div v-for="i in 4" :key="i" class="h-36 animate-pulse rounded-xl bg-line" /></div>

        <EmptyState v-else-if="!items.length" title="Cadastre sua primeira disciplina" text="Associe tarefas, sessões de estudo e flashcards a cada matéria para ver seu progresso.">
            <template #icon><PhBooks :size="28" /></template>
            <button class="btn btn-primary" @click="edit(null)"><PhPlus :size="18" weight="bold" />Nova disciplina</button>
        </EmptyState>

        <div v-else class="grid gap-4 sm:grid-cols-2">
            <article v-for="s in items" :key="s.id" class="card">
                <div class="flex items-start justify-between gap-2">
                    <div class="min-w-0">
                        <h2 class="flex items-center gap-2.5 text-base font-semibold">
                            <span class="grid size-9 shrink-0 place-items-center rounded-lg text-white" :style="{ background: s.color }" aria-hidden="true"><PhBooks :size="18" /></span>
                            <span class="truncate">{{ s.name }}</span>
                        </h2>
                        <p class="mt-1 text-sm text-muted">{{ s.professor || 'Professor não informado' }}</p>
                    </div>
                    <div class="-mr-2 -mt-2 flex shrink-0">
                        <button class="btn-icon" aria-label="Editar disciplina" @click="edit(s)"><PhPencilSimple :size="20" /></button>
                        <button class="btn-icon btn-icon-danger" aria-label="Excluir disciplina" @click="remove(s)"><PhTrash :size="20" /></button>
                    </div>
                </div>

                <div class="mt-4">
                    <div class="mb-1.5 flex justify-between text-sm">
                        <span class="text-muted">Tarefas concluídas</span>
                        <span class="font-semibold tabular-nums">{{ s.done_count }}/{{ s.tasks_count }}</span>
                    </div>
                    <div class="h-2 overflow-hidden rounded-full bg-brand-50">
                        <div class="h-full rounded-full" :style="{ width: (s.tasks_count ? (s.done_count / s.tasks_count) * 100 : 0) + '%', background: s.color }" />
                    </div>
                </div>
                <div class="mt-4 flex gap-5 border-t border-line pt-3 text-sm text-muted">
                    <span class="flex items-center gap-1.5"><PhListChecks :size="16" aria-hidden="true" />{{ s.pending_tasks_count }} pendente(s)</span>
                    <span class="flex items-center gap-1.5"><PhClock :size="16" aria-hidden="true" />{{ fmtMinutes(s.total_minutes || 0) }} de estudo</span>
                </div>
            </article>
        </div>

        <Modal v-if="editing" :title="editing === 'new' ? 'Nova disciplina' : 'Editar disciplina'" @close="editing = null">
            <form class="space-y-4" @submit.prevent="save">
                <div>
                    <label class="label" for="s-name">Nome</label>
                    <input id="s-name" v-model="form.name" required class="input" placeholder="Ex.: Banco de Dados" />
                    <p v-if="errors.name" role="alert" class="mt-1 text-sm text-red-700">{{ errors.name[0] }}</p>
                </div>
                <div>
                    <label class="label" for="s-prof">Professor <span class="font-normal text-muted">(opcional)</span></label>
                    <input id="s-prof" v-model="form.professor" class="input" />
                </div>
                <fieldset>
                    <legend class="label">Cor</legend>
                    <div class="flex flex-wrap gap-2">
                        <label v-for="c in SWATCHES" :key="c">
                            <input v-model="form.color" type="radio" :value="c" class="peer sr-only" :aria-label="'Cor ' + c" />
                            <span class="block size-9 cursor-pointer rounded-full ring-offset-2 transition peer-checked:ring-2 peer-checked:ring-ink peer-focus-visible:ring-2 peer-focus-visible:ring-brand-600" :style="{ background: c }" />
                        </label>
                    </div>
                </fieldset>
                <div class="flex justify-end gap-2 border-t border-line pt-4">
                    <button type="button" class="btn btn-ghost" @click="editing = null">Cancelar</button>
                    <button class="btn btn-primary">Salvar disciplina</button>
                </div>
            </form>
        </Modal>
    </div>
</template>
