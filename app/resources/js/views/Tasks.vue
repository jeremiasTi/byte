<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue';
import {
    PhCheckSquare, PhKanban, PhListBullets, PhMagnifyingGlass, PhPencilSimple, PhPlus, PhTrash, PhWarning,
} from '@phosphor-icons/vue';
import { api, PRIORITY, relDue, STATUS, TONE } from '../api';
import { useUi } from '../stores/ui';
import TaskForm from '../components/TaskForm.vue';
import PageHeader from '../components/PageHeader.vue';
import EmptyState from '../components/EmptyState.vue';

const ui = useUi();
const all = ref([]);
const subjects = ref([]);
const loading = ref(true);
const editing = ref(null); // null | 'new' | task
const quick = ref('all');
const filters = reactive({ priority: '', subject_id: '', search: '' });
const view = ref((() => { try { return localStorage.getItem('byte_tasks_view') || 'list'; } catch { return 'list'; } })());
watch(view, (v) => { try { localStorage.setItem('byte_tasks_view', v); } catch { /* sem storage */ } });

async function load() {
    all.value = await api.get('/tasks');
    loading.value = false;
}
onMounted(async () => {
    subjects.value = await api.get('/subjects');
    await load();
});

const isToday = (t) => new Date(t.due_at).toDateString() === new Date().toDateString();
const isOverdue = (t) => t.status !== 'done' && new Date(t.due_at) < new Date();
const inWeek = (t) => {
    const d = new Date(t.due_at), n = new Date();
    return t.status !== 'done' && d >= n && d - n < 7 * 86400000;
};

const tabs = computed(() => [
    { key: 'all', label: 'Todas', n: all.value.length },
    { key: 'today', label: 'Hoje', n: all.value.filter((t) => t.status !== 'done' && isToday(t)).length },
    { key: 'week', label: 'Próx. 7 dias', n: all.value.filter(inWeek).length },
    { key: 'overdue', label: 'Atrasadas', n: all.value.filter(isOverdue).length },
    { key: 'done', label: 'Concluídas', n: all.value.filter((t) => t.status === 'done').length },
]);

const shown = computed(() =>
    all.value.filter((t) => {
        if (quick.value === 'today' && !(t.status !== 'done' && isToday(t))) return false;
        if (quick.value === 'week' && !inWeek(t)) return false;
        if (quick.value === 'overdue' && !isOverdue(t)) return false;
        if (quick.value === 'done' && t.status !== 'done') return false;
        if (filters.priority && t.priority !== filters.priority) return false;
        if (filters.subject_id && t.subject_id !== +filters.subject_id) return false;
        if (filters.search && !t.title.toLowerCase().includes(filters.search.toLowerCase())) return false;
        return true;
    }),
);
const columns = computed(() => Object.keys(STATUS).map((k) => ({ key: k, label: STATUS[k], items: shown.value.filter((t) => t.status === k) })));

async function setStatus(t, status) {
    const prev = t.status;
    t.status = status; // otimista
    try {
        const saved = await api.patch(`/tasks/${t.id}/status`, { status });
        Object.assign(t, saved);
        if (status === 'done') ui.toast('Tarefa concluída. Bom trabalho!');
    } catch (e) {
        t.status = prev;
        ui.error(e.message);
    }
}
const toggle = (t) => setStatus(t, t.status === 'done' ? 'todo' : 'done');

async function remove(t) {
    if (!confirm(`Excluir "${t.title}"?`)) return;
    await api.del(`/tasks/${t.id}`);
    ui.toast('Tarefa excluída.');
    await load();
}

// ---- arrastar e soltar (Kanban) ----
const dragging = ref(null);
const over = ref('');
function drop(col) {
    if (dragging.value && dragging.value.status !== col) setStatus(dragging.value, col);
    dragging.value = null;
    over.value = '';
}
</script>

<template>
    <div>
        <PageHeader title="Tarefas" subtitle="Prazos, prioridades e andamento. Lembretes por e-mail chegam 24h antes do vencimento.">
            <div class="flex rounded-lg bg-white p-1 ring-1 ring-line" role="group" aria-label="Modo de visualização">
                <button class="btn !min-h-9 !px-3" :class="view === 'list' ? 'btn-primary' : 'text-muted hover:bg-brand-50'" :aria-pressed="view === 'list'" @click="view = 'list'"><PhListBullets :size="18" aria-hidden="true" />Lista</button>
                <button class="btn !min-h-9 !px-3" :class="view === 'board' ? 'btn-primary' : 'text-muted hover:bg-brand-50'" :aria-pressed="view === 'board'" @click="view = 'board'"><PhKanban :size="18" aria-hidden="true" />Quadro</button>
            </div>
            <button class="btn btn-primary" @click="editing = 'new'"><PhPlus :size="18" weight="bold" aria-hidden="true" />Nova tarefa</button>
        </PageHeader>

        <!-- filtros -->
        <div class="mb-5 flex flex-wrap items-center gap-2" role="tablist" aria-label="Filtros rápidos">
            <button
                v-for="t in tabs" :key="t.key" role="tab" :aria-selected="quick === t.key"
                class="inline-flex min-h-10 items-center gap-2 rounded-full px-4 text-sm font-semibold transition-colors"
                :class="quick === t.key ? 'bg-shell text-white' : 'bg-white text-muted ring-1 ring-line hover:bg-brand-50'"
                @click="quick = t.key"
            >
                {{ t.label }}
                <span class="rounded-full px-1.5 text-xs tabular-nums" :class="quick === t.key ? 'bg-shell-hover' : t.key === 'overdue' && t.n ? 'bg-red-100 text-red-800' : 'bg-brand-50'">{{ t.n }}</span>
            </button>
        </div>
        <div class="mb-6 grid gap-3 sm:grid-cols-3">
            <div class="relative">
                <PhMagnifyingGlass :size="18" class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-muted" aria-hidden="true" />
                <input v-model="filters.search" aria-label="Buscar tarefas" placeholder="Buscar tarefas" class="input !pl-10" />
            </div>
            <select v-model="filters.priority" class="input" aria-label="Filtrar por prioridade">
                <option value="">Todas as prioridades</option>
                <option v-for="(p, k) in PRIORITY" :key="k" :value="k">Prioridade {{ p.label.toLowerCase() }}</option>
            </select>
            <select v-model="filters.subject_id" class="input" aria-label="Filtrar por disciplina">
                <option value="">Todas as disciplinas</option>
                <option v-for="s in subjects" :key="s.id" :value="s.id">{{ s.name }}</option>
            </select>
        </div>

        <div v-if="loading" class="space-y-2"><div v-for="i in 4" :key="i" class="h-16 animate-pulse rounded-xl bg-line" /></div>

        <EmptyState v-else-if="!shown.length" title="Nenhuma tarefa por aqui" text="Ajuste os filtros ou crie uma nova tarefa para organizar seus prazos.">
            <template #icon><PhCheckSquare :size="28" /></template>
            <button class="btn btn-primary" @click="editing = 'new'"><PhPlus :size="18" weight="bold" />Nova tarefa</button>
        </EmptyState>

        <!-- lista -->
        <ul v-else-if="view === 'list'" class="space-y-2">
            <li v-for="t in shown" :key="t.id" class="card flex items-center gap-3 !p-4 transition-colors hover:border-brand-500">
                <input type="checkbox" :checked="t.status === 'done'" class="size-5 shrink-0 cursor-pointer" :aria-label="'Concluir: ' + t.title" @change="toggle(t)" />
                <div class="min-w-0 flex-1">
                    <p class="truncate font-medium" :class="{ 'text-muted line-through': t.status === 'done' }">{{ t.title }}</p>
                    <p class="mt-0.5 flex flex-wrap items-center gap-x-3 gap-y-1 text-sm">
                        <span v-if="t.subject" class="flex items-center gap-1.5 text-muted"><span class="size-2 rounded-full" :style="{ background: t.subject.color }" aria-hidden="true" />{{ t.subject.name }}</span>
                        <span class="flex items-center gap-1" :class="TONE[relDue(t.due_at, t.status === 'done').tone]">
                            <PhWarning v-if="isOverdue(t)" :size="14" weight="fill" aria-hidden="true" />{{ relDue(t.due_at, t.status === 'done').text }}
                        </span>
                    </p>
                </div>
                <select :value="t.status" class="input !hidden !w-auto !min-h-9 !py-1 text-xs font-semibold md:!block" :aria-label="'Status de ' + t.title" @change="setStatus(t, $event.target.value)">
                    <option v-for="(l, k) in STATUS" :key="k" :value="k">{{ l }}</option>
                </select>
                <span class="chip hidden sm:inline-flex" :class="PRIORITY[t.priority].cls">{{ PRIORITY[t.priority].label }}</span>
                <button class="btn-icon" aria-label="Editar tarefa" @click="editing = t"><PhPencilSimple :size="20" /></button>
                <button class="btn-icon btn-icon-danger" aria-label="Excluir tarefa" @click="remove(t)"><PhTrash :size="20" /></button>
            </li>
        </ul>

        <!-- quadro -->
        <div v-else class="grid gap-4 lg:grid-cols-3">
            <section
                v-for="c in columns" :key="c.key"
                class="min-h-48 rounded-xl p-3 transition-colors"
                :class="over === c.key ? 'bg-brand-100' : 'bg-brand-50'"
                :aria-label="c.label"
                @dragover.prevent="over = c.key" @dragleave="over = ''" @drop.prevent="drop(c.key)"
            >
                <h2 class="mb-3 flex items-center justify-between px-1 text-sm font-semibold">
                    {{ c.label }}<span class="chip bg-white text-muted tabular-nums">{{ c.items.length }}</span>
                </h2>
                <div class="space-y-2">
                    <article
                        v-for="t in c.items" :key="t.id" draggable="true"
                        class="cursor-grab rounded-lg border border-line bg-white p-3 transition-shadow hover:shadow-md active:cursor-grabbing"
                        :class="{ 'opacity-50': dragging === t }"
                        @dragstart="dragging = t" @dragend="dragging = null; over = ''"
                    >
                        <div class="flex items-start justify-between gap-2">
                            <p class="font-medium leading-snug" :class="{ 'text-muted line-through': t.status === 'done' }">{{ t.title }}</p>
                            <span class="chip shrink-0" :class="PRIORITY[t.priority].cls">{{ PRIORITY[t.priority].label }}</span>
                        </div>
                        <p v-if="t.subject" class="mt-2 flex items-center gap-1.5 text-xs text-muted"><span class="size-2 rounded-full" :style="{ background: t.subject.color }" aria-hidden="true" />{{ t.subject.name }}</p>
                        <div class="mt-2 flex items-center justify-between">
                            <span class="text-xs" :class="TONE[relDue(t.due_at, t.status === 'done').tone]">{{ relDue(t.due_at, t.status === 'done').text }}</span>
                            <span class="flex">
                                <button class="grid size-8 place-items-center rounded-md text-muted hover:bg-brand-50 hover:text-brand-700" aria-label="Editar tarefa" @click="editing = t"><PhPencilSimple :size="16" /></button>
                                <button class="grid size-8 place-items-center rounded-md text-muted hover:bg-red-50 hover:text-red-700" aria-label="Excluir tarefa" @click="remove(t)"><PhTrash :size="16" /></button>
                            </span>
                        </div>
                    </article>
                    <p v-if="!c.items.length" class="rounded-lg border border-dashed border-slate-300 py-6 text-center text-sm text-muted">Arraste tarefas para cá</p>
                </div>
            </section>
        </div>

        <TaskForm v-if="editing" :task="editing === 'new' ? null : editing" @close="editing = null" @saved="load" />
    </div>
</template>
