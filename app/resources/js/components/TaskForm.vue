<script setup>
import { onMounted, reactive, ref } from 'vue';
import Modal from './Modal.vue';
import { api, PRIORITY, STATUS, toInput } from '../api';
import { useUi } from '../stores/ui';

const props = defineProps({ task: Object });
const emit = defineEmits(['close', 'saved']);
const ui = useUi();

const subjects = ref([]);
const errors = ref({});
const saving = ref(false);
const t = props.task;
const form = reactive({
    title: t?.title ?? '',
    description: t?.description ?? '',
    subject_id: t?.subject_id ?? '',
    priority: t?.priority ?? 'medium',
    status: t?.status ?? 'todo',
    due_at: t ? toInput(t.due_at) : toInput(new Date(Date.now() + 86400000)),
});

onMounted(async () => (subjects.value = await api.get('/subjects')));

async function save() {
    saving.value = true;
    errors.value = {};
    const body = { ...form, subject_id: form.subject_id || null };
    try {
        t ? await api.put(`/tasks/${t.id}`, body) : await api.post('/tasks', body);
        ui.toast(t ? 'Tarefa atualizada.' : 'Tarefa criada.');
        emit('saved');
        emit('close');
    } catch (e) {
        errors.value = e.errors;
        if (!Object.keys(e.errors).length) ui.error(e.message);
    } finally {
        saving.value = false;
    }
}
</script>

<template>
    <Modal :title="task ? 'Editar tarefa' : 'Nova tarefa'" @close="emit('close')">
        <form class="grid gap-4 sm:grid-cols-2" @submit.prevent="save">
            <div class="sm:col-span-2">
                <label class="label" for="tf-title">Título</label>
                <input id="tf-title" v-model="form.title" required class="input" placeholder="Ex.: Entregar lista de exercícios" />
                <p v-if="errors.title" role="alert" class="mt-1 text-sm text-red-700">{{ errors.title[0] }}</p>
            </div>
            <div class="sm:col-span-2">
                <label class="label" for="tf-desc">Descrição <span class="font-normal text-muted">(opcional)</span></label>
                <textarea id="tf-desc" v-model="form.description" rows="2" class="input" />
            </div>
            <div>
                <label class="label" for="tf-due">Prazo</label>
                <input id="tf-due" v-model="form.due_at" type="datetime-local" required class="input" />
                <p v-if="errors.due_at" role="alert" class="mt-1 text-sm text-red-700">{{ errors.due_at[0] }}</p>
            </div>
            <div>
                <label class="label" for="tf-sub">Disciplina</label>
                <select id="tf-sub" v-model="form.subject_id" class="input">
                    <option value="">Sem disciplina</option>
                    <option v-for="s in subjects" :key="s.id" :value="s.id">{{ s.name }}</option>
                </select>
            </div>
            <fieldset>
                <legend class="label">Prioridade</legend>
                <div class="flex gap-2">
                    <label v-for="(p, k) in PRIORITY" :key="k" class="flex-1">
                        <input v-model="form.priority" type="radio" :value="k" class="peer sr-only" />
                        <span class="flex min-h-11 cursor-pointer items-center justify-center rounded-lg border border-slate-300 text-sm font-semibold text-muted transition-colors peer-checked:border-brand-600 peer-checked:bg-brand-50 peer-checked:text-brand-700 peer-focus-visible:outline peer-focus-visible:outline-2 peer-focus-visible:outline-brand-600">{{ p.label }}</span>
                    </label>
                </div>
            </fieldset>
            <div>
                <label class="label" for="tf-status">Status</label>
                <select id="tf-status" v-model="form.status" class="input">
                    <option v-for="(l, k) in STATUS" :key="k" :value="k">{{ l }}</option>
                </select>
            </div>
            <div class="flex justify-end gap-2 border-t border-line pt-4 sm:col-span-2">
                <button type="button" class="btn btn-ghost" @click="emit('close')">Cancelar</button>
                <button class="btn btn-primary" :disabled="saving">{{ saving ? 'Salvando…' : 'Salvar tarefa' }}</button>
            </div>
        </form>
    </Modal>
</template>
