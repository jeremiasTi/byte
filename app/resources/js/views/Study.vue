<script setup>
import { computed, onBeforeUnmount, onMounted, reactive, ref } from 'vue';
import { PhClockCounterClockwise, PhPause, PhPlay, PhPlus, PhStop, PhTrash } from '@phosphor-icons/vue';
import { api, fmtMinutes, todayISO } from '../api';
import { useUi } from '../stores/ui';
import Modal from '../components/Modal.vue';
import PageHeader from '../components/PageHeader.vue';
import EmptyState from '../components/EmptyState.vue';

const ui = useUi();
const sessions = ref([]);
const subjects = ref([]);
const loading = ref(true);
const manual = ref(false);
const errors = ref({});
const form = reactive({ subject_id: '', studied_on: todayISO(), minutes: 30, notes: '' });

// ---- cronômetro baseado em timestamp (não perde tempo em aba inativa) ----
const MODES = [
    { key: 'free', label: 'Livre', secs: 0 },
    { key: 'p25', label: 'Pomodoro 25', secs: 25 * 60 },
    { key: 'p50', label: 'Foco 50', secs: 50 * 60 },
];
const mode = ref('free');
const subject = ref('');
const running = ref(false);
const acc = ref(0); // segundos acumulados antes do último início
const startedAt = ref(0);
const now = ref(Date.now());
let tick;

const elapsed = computed(() => acc.value + (running.value ? Math.floor((now.value - startedAt.value) / 1000) : 0));
const target = computed(() => MODES.find((m) => m.key === mode.value).secs);
const shownSecs = computed(() => (target.value ? Math.max(0, target.value - elapsed.value) : elapsed.value));
const clock = computed(() => `${String(Math.floor(shownSecs.value / 60)).padStart(2, '0')}:${String(shownSecs.value % 60).padStart(2, '0')}`);
const pct = computed(() => (target.value ? Math.min(100, (elapsed.value / target.value) * 100) : 0));

function start() {
    startedAt.value = Date.now();
    now.value = Date.now();
    running.value = true;
    tick = setInterval(() => {
        now.value = Date.now();
        if (target.value && elapsed.value >= target.value) {
            pause();
            ui.toast('Tempo de foco concluído! Salve a sessão.');
        }
    }, 500);
}
function pause() {
    if (!running.value) return;
    acc.value = elapsed.value;
    running.value = false;
    clearInterval(tick);
}
function reset() {
    pause();
    acc.value = 0;
}
async function finish() {
    pause();
    const minutes = Math.max(1, Math.round(acc.value / 60));
    await api.post('/sessions', { subject_id: subject.value || null, studied_on: todayISO(), minutes, notes: mode.value === 'free' ? 'Cronômetro' : 'Sessão de foco' });
    ui.toast(`Sessão de ${fmtMinutes(minutes)} registrada.`);
    acc.value = 0;
    await load();
}
const setMode = (m) => { if (!running.value && !acc.value) mode.value = m; };

const onVis = () => (now.value = Date.now());
async function load() {
    sessions.value = await api.get('/sessions');
    loading.value = false;
}
onMounted(async () => {
    document.addEventListener('visibilitychange', onVis);
    subjects.value = await api.get('/subjects');
    await load();
});
onBeforeUnmount(() => {
    clearInterval(tick);
    document.removeEventListener('visibilitychange', onVis);
});

async function save() {
    errors.value = {};
    try {
        await api.post('/sessions', { ...form, subject_id: form.subject_id || null });
        ui.toast('Sessão registrada.');
        form.notes = '';
        manual.value = false;
        await load();
    } catch (e) {
        errors.value = e.errors;
    }
}
async function remove(s) {
    await api.del(`/sessions/${s.id}`);
    ui.toast('Sessão excluída.');
    await load();
}

// histórico agrupado por dia
const grouped = computed(() => {
    const map = new Map();
    for (const s of sessions.value) {
        if (!map.has(s.studied_on)) map.set(s.studied_on, []);
        map.get(s.studied_on).push(s);
    }
    return [...map].map(([date, items]) => ({ date, items, total: items.reduce((a, b) => a + b.minutes, 0) }));
});
const dayLabel = (s) => {
    const d = new Date(s + 'T12:00');
    const diff = Math.round((new Date(todayISO() + 'T12:00') - d) / 86400000);
    if (diff === 0) return 'Hoje';
    if (diff === 1) return 'Ontem';
    return d.toLocaleDateString('pt-BR', { weekday: 'long', day: '2-digit', month: 'long' });
};
</script>

<template>
    <div>
        <PageHeader title="Sessões de estudo" subtitle="Cronometre seu foco ou registre o tempo manualmente. Tudo alimenta seu painel e sua meta semanal.">
            <button class="btn btn-ghost" @click="manual = true"><PhPlus :size="18" aria-hidden="true" />Registrar manualmente</button>
        </PageHeader>

        <div class="grid gap-6 lg:grid-cols-[360px_1fr]">
            <!-- cronômetro -->
            <section class="card h-fit text-center" aria-label="Cronômetro">
                <div class="mb-5 flex rounded-lg bg-brand-50 p-1" role="group" aria-label="Modo">
                    <button v-for="m in MODES" :key="m.key" class="btn !min-h-9 flex-1 !px-2" :class="mode === m.key ? 'bg-white text-brand-700 shadow-sm' : 'text-muted'" :aria-pressed="mode === m.key" :disabled="running || acc > 0" @click="setMode(m.key)">{{ m.label }}</button>
                </div>

                <p class="font-display text-6xl font-semibold tabular-nums tracking-tight" role="timer" aria-live="off">{{ clock }}</p>
                <div v-if="target" class="mx-auto mt-4 h-2 max-w-xs overflow-hidden rounded-full bg-brand-100"><div class="h-full rounded-full bg-brand-600 transition-all" :style="{ width: pct + '%' }" /></div>
                <p v-else class="mt-2 text-sm text-muted">{{ running ? 'Em andamento…' : acc ? 'Pausado' : 'Pronto para começar' }}</p>

                <div class="mt-5 text-left">
                    <label class="label" for="t-sub">Disciplina</label>
                    <select id="t-sub" v-model="subject" class="input">
                        <option value="">Estudo geral</option>
                        <option v-for="s in subjects" :key="s.id" :value="s.id">{{ s.name }}</option>
                    </select>
                </div>

                <div class="mt-5 flex justify-center gap-2">
                    <button v-if="!running" class="btn btn-primary flex-1" @click="start"><PhPlay :size="18" weight="fill" aria-hidden="true" />{{ acc ? 'Retomar' : 'Iniciar' }}</button>
                    <button v-else class="btn btn-ghost flex-1" @click="pause"><PhPause :size="18" weight="fill" aria-hidden="true" />Pausar</button>
                    <button class="btn btn-success" :disabled="acc < 30 && !running" @click="finish"><PhStop :size="18" weight="fill" aria-hidden="true" />Salvar</button>
                </div>
                <button v-if="acc && !running" class="mt-3 text-sm font-semibold text-muted hover:text-red-700" @click="reset">Descartar</button>
            </section>

            <!-- histórico -->
            <section aria-label="Histórico">
                <div v-if="loading" class="space-y-2"><div v-for="i in 4" :key="i" class="h-16 animate-pulse rounded-xl bg-line" /></div>
                <EmptyState v-else-if="!sessions.length" title="Nenhuma sessão registrada" text="Inicie o cronômetro e comece a construir sua constância.">
                    <template #icon><PhClockCounterClockwise :size="28" /></template>
                </EmptyState>
                <div v-for="g in grouped" :key="g.date" class="mb-5">
                    <div class="mb-2 flex items-baseline justify-between px-1">
                        <h2 class="text-sm font-semibold capitalize">{{ dayLabel(g.date) }}</h2>
                        <span class="text-sm tabular-nums text-muted">{{ fmtMinutes(g.total) }}</span>
                    </div>
                    <ul class="space-y-2">
                        <li v-for="s in g.items" :key="s.id" class="card flex items-center justify-between gap-3 !p-3 !pl-4">
                            <div class="min-w-0">
                                <p class="flex items-center gap-2 font-medium"><span v-if="s.subject" class="size-2.5 shrink-0 rounded-full" :style="{ background: s.subject.color }" aria-hidden="true" />{{ s.subject?.name || 'Estudo geral' }}</p>
                                <p v-if="s.notes" class="truncate text-sm text-muted">{{ s.notes }}</p>
                            </div>
                            <div class="flex items-center">
                                <span class="chip bg-brand-50 text-brand-700 tabular-nums">{{ fmtMinutes(s.minutes) }}</span>
                                <button class="btn-icon btn-icon-danger" aria-label="Excluir sessão" @click="remove(s)"><PhTrash :size="20" /></button>
                            </div>
                        </li>
                    </ul>
                </div>
            </section>
        </div>

        <Modal v-if="manual" title="Registrar sessão" @close="manual = false">
            <form class="grid gap-4 sm:grid-cols-2" @submit.prevent="save">
                <div>
                    <label class="label" for="m-sub">Disciplina</label>
                    <select id="m-sub" v-model="form.subject_id" class="input">
                        <option value="">Estudo geral</option>
                        <option v-for="s in subjects" :key="s.id" :value="s.id">{{ s.name }}</option>
                    </select>
                </div>
                <div>
                    <label class="label" for="m-date">Data</label>
                    <input id="m-date" v-model="form.studied_on" type="date" :max="todayISO()" required class="input" />
                    <p v-if="errors.studied_on" role="alert" class="mt-1 text-sm text-red-700">{{ errors.studied_on[0] }}</p>
                </div>
                <div>
                    <label class="label" for="m-min">Minutos</label>
                    <input id="m-min" v-model.number="form.minutes" type="number" min="1" max="960" required class="input" />
                </div>
                <div>
                    <label class="label" for="m-notes">Anotação</label>
                    <input id="m-notes" v-model="form.notes" class="input" placeholder="O que você estudou?" />
                </div>
                <div class="flex justify-end gap-2 border-t border-line pt-4 sm:col-span-2">
                    <button type="button" class="btn btn-ghost" @click="manual = false">Cancelar</button>
                    <button class="btn btn-primary">Registrar sessão</button>
                </div>
            </form>
        </Modal>
    </div>
</template>
