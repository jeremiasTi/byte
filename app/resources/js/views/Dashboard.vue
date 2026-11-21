<script setup>
import { computed, onMounted, ref } from 'vue';
import { PhCards, PhCheckCircle, PhFlame, PhTarget, PhWarning } from '@phosphor-icons/vue';
import { api, fmtMinutes, PRIORITY, relDue, TONE } from '../api';
import { useAuth } from '../stores/auth';

const auth = useAuth();
const d = ref(null);
onMounted(async () => (d.value = await api.get('/dashboard')));

const first = computed(() => auth.user?.name?.split(' ')[0]);
const greeting = computed(() => {
    const h = new Date().getHours();
    return h < 12 ? 'Bom dia' : h < 18 ? 'Boa tarde' : 'Boa noite';
});
const goalPct = computed(() => Math.min(100, Math.round((d.value.goal.done / d.value.goal.target) * 100)));
const maxDay = computed(() => Math.max(60, ...d.value.study.by_day.map((x) => x.minutes)));
const weekday = (iso) => new Date(iso + 'T12:00').toLocaleDateString('pt-BR', { weekday: 'short' }).replace('.', '');

// ---- mapa de estudo (12 semanas, colunas = semanas) ----
const LEVELS = ['#e8edf5', '#c3d3ee', '#8fb0e3', '#4d7fcf', '#183f87'];
const level = (m) => (m === 0 ? 0 : m < 30 ? 1 : m < 60 ? 2 : m < 100 ? 3 : 4);
const heat = computed(() => {
    const cells = d.value.study.heatmap.map((c) => ({ ...c, level: level(c.minutes) }));
    const pad = (new Date(cells[0].date + 'T12:00').getDay() + 6) % 7; // segunda = 0
    return [...Array(pad).fill(null), ...cells];
});
const cellLabel = (c) =>
    `${new Date(c.date + 'T12:00').toLocaleDateString('pt-BR', { day: '2-digit', month: 'long' })}: ${c.minutes ? fmtMinutes(c.minutes) : 'sem estudo'}`;

// ---- flashcards por caixa ----
const BOX = [
    { name: 'Aprendendo', color: '#c3d3ee' },
    { name: 'Em reforço', color: '#8fb0e3' },
    { name: 'Revisando', color: '#4d7fcf' },
    { name: 'Quase lá', color: '#183f87' },
    { name: 'Dominado', color: '#0f7b4d' },
];
const boxTotal = computed(() => d.value.flashcards.boxes.reduce((a, b) => a + b, 0) || 1);
const mastered = computed(() => Math.round(((d.value.flashcards.boxes[3] + d.value.flashcards.boxes[4]) / boxTotal.value) * 100));
</script>

<template>
    <div v-if="d" class="space-y-6">
        <!-- cabeçalho + meta semanal -->
        <section class="grid gap-4 lg:grid-cols-3">
            <div class="rounded-xl bg-shell p-6 text-white lg:col-span-2">
                <p class="text-sm text-shell-text">{{ greeting }}, {{ first }}.</p>
                <h1 class="mt-1 text-2xl">
                    <template v-if="d.today.length">Você tem {{ d.today.length }} {{ d.today.length === 1 ? 'tarefa' : 'tarefas' }} pedindo atenção hoje.</template>
                    <template v-else>Nada urgente por hoje. Bom momento para revisar.</template>
                </h1>
                <div class="mt-5 flex flex-wrap gap-6 text-sm">
                    <div class="flex items-center gap-2"><PhWarning :size="18" class="text-amber-300" aria-hidden="true" /><span><strong class="tabular-nums">{{ d.tasks.overdue }}</strong> atrasada(s)</span></div>
                    <div class="flex items-center gap-2"><PhCards :size="18" class="text-sky-300" aria-hidden="true" /><span><strong class="tabular-nums">{{ d.flashcards.due }}</strong> cartões para revisar</span></div>
                    <div class="flex items-center gap-2"><PhFlame :size="18" weight="fill" class="text-amber-300" aria-hidden="true" /><span><strong class="tabular-nums">{{ d.study.streak }}</strong> dia(s) seguidos</span></div>
                </div>
            </div>

            <div class="card">
                <div class="flex items-center justify-between">
                    <h2 class="flex items-center gap-2 text-base font-semibold"><PhTarget :size="20" class="text-brand-600" aria-hidden="true" />Meta da semana</h2>
                    <router-link to="/configuracoes" class="text-xs font-semibold text-brand-700 hover:underline">Ajustar</router-link>
                </div>
                <p class="mt-3 font-display text-3xl font-semibold tabular-nums">{{ fmtMinutes(d.goal.done) }}<span class="text-base font-normal text-muted"> / {{ fmtMinutes(d.goal.target) }}</span></p>
                <div class="mt-3 h-2.5 overflow-hidden rounded-full bg-brand-50" role="progressbar" :aria-valuenow="goalPct" aria-valuemin="0" aria-valuemax="100" aria-label="Progresso da meta semanal">
                    <div class="h-full rounded-full transition-all duration-500" :class="goalPct >= 100 ? 'bg-success' : 'bg-brand-600'" :style="{ width: goalPct + '%' }" />
                </div>
                <p class="mt-2 text-sm text-muted">
                    {{ goalPct >= 100 ? 'Meta cumprida. Excelente ritmo.' : `Faltam ${fmtMinutes(d.goal.target - d.goal.done)} para bater a meta (${goalPct}%).` }}
                </p>
            </div>
        </section>

        <!-- foco do dia + próximos prazos -->
        <section class="grid gap-4 lg:grid-cols-5">
            <div class="card lg:col-span-3">
                <div class="mb-1 flex items-center justify-between">
                    <h2 class="text-base font-semibold">Foco de hoje</h2>
                    <router-link to="/tarefas" class="text-sm font-semibold text-brand-700 hover:underline">Ver todas</router-link>
                </div>
                <p v-if="!d.today.length" class="flex items-center gap-2 py-6 text-muted"><PhCheckCircle :size="22" weight="fill" class="text-success" aria-hidden="true" />Tudo em dia por aqui.</p>
                <ul class="divide-y divide-line">
                    <li v-for="t in d.today" :key="t.id" class="flex items-center justify-between gap-3 py-3">
                        <div class="min-w-0">
                            <p class="truncate font-medium">{{ t.title }}</p>
                            <p class="flex flex-wrap items-center gap-x-3 text-sm">
                                <span v-if="t.subject" class="flex items-center gap-1.5 text-muted"><span class="size-2 rounded-full" :style="{ background: t.subject.color }" aria-hidden="true" />{{ t.subject.name }}</span>
                                <span :class="TONE[relDue(t.due_at).tone]">{{ relDue(t.due_at).text }}</span>
                            </p>
                        </div>
                        <span class="chip shrink-0" :class="PRIORITY[t.priority].cls">{{ PRIORITY[t.priority].label }}</span>
                    </li>
                </ul>
            </div>

            <div class="card lg:col-span-2">
                <h2 class="mb-1 text-base font-semibold">Próximos prazos</h2>
                <p v-if="!d.upcoming.length" class="py-6 text-muted">Sem prazos futuros.</p>
                <ul class="divide-y divide-line">
                    <li v-for="t in d.upcoming" :key="t.id" class="py-3">
                        <p class="truncate font-medium">{{ t.title }}</p>
                        <p class="text-sm text-muted">{{ relDue(t.due_at).text }}<span v-if="t.subject"> • {{ t.subject.name }}</span></p>
                    </li>
                </ul>
            </div>
        </section>

        <!-- mapa de estudo -->
        <section class="card">
            <div class="mb-4 flex flex-wrap items-end justify-between gap-2">
                <div>
                    <h2 class="text-base font-semibold">Constância de estudo</h2>
                    <p class="text-sm text-muted">Últimas 12 semanas • {{ fmtMinutes(d.study.total_minutes) }} estudadas no total</p>
                </div>
                <div class="flex items-center gap-1.5 text-xs text-muted">
                    Menos
                    <span v-for="(c, i) in LEVELS" :key="i" class="size-3.5 rounded-[3px]" :style="{ background: c }" aria-hidden="true" />
                    Mais
                </div>
            </div>
            <div class="overflow-x-auto pb-1">
                <div class="grid grid-flow-col grid-rows-7 gap-1" style="width: max-content">
                    <template v-for="(c, i) in heat" :key="i">
                        <span v-if="!c" class="size-4" />
                        <span v-else class="size-4 rounded-[3px]" :style="{ background: LEVELS[c.level] }" :title="cellLabel(c)" :aria-label="cellLabel(c)" role="img" />
                    </template>
                </div>
            </div>
        </section>

        <!-- gráficos -->
        <section class="grid gap-4 lg:grid-cols-2">
            <div class="card">
                <h2 class="mb-4 text-base font-semibold">Minutos por dia (7 dias)</h2>
                <div class="flex h-44 items-end gap-2">
                    <div v-for="x in d.study.by_day" :key="x.date" class="flex flex-1 flex-col items-center justify-end gap-1.5">
                        <span class="text-xs tabular-nums text-muted">{{ x.minutes || '' }}</span>
                        <div class="w-full rounded-t-md transition-all duration-500" :class="x.minutes ? 'bg-brand-600' : 'bg-brand-100'" :style="{ height: Math.max(4, (x.minutes / maxDay) * 120) + 'px' }" />
                        <span class="text-xs capitalize text-muted">{{ weekday(x.date) }}</span>
                    </div>
                </div>
            </div>

            <div class="card">
                <h2 class="mb-4 text-base font-semibold">Progresso por disciplina</h2>
                <p v-if="!d.subjects.length" class="text-muted">Cadastre disciplinas para acompanhar.</p>
                <div v-for="s in d.subjects" :key="s.id" class="mb-4 last:mb-0">
                    <div class="mb-1.5 flex items-baseline justify-between gap-3 text-sm">
                        <span class="flex min-w-0 items-center gap-2 font-medium"><span class="size-2.5 shrink-0 rounded-full" :style="{ background: s.color }" aria-hidden="true" /><span class="truncate">{{ s.name }}</span></span>
                        <span class="shrink-0 tabular-nums text-muted">{{ s.done_count }}/{{ s.tasks_count }} tarefas • {{ fmtMinutes(s.minutes || 0) }}</span>
                    </div>
                    <div class="h-2 overflow-hidden rounded-full bg-brand-50">
                        <div class="h-full rounded-full" :style="{ width: (s.tasks_count ? (s.done_count / s.tasks_count) * 100 : 0) + '%', background: s.color }" />
                    </div>
                </div>
            </div>
        </section>

        <!-- domínio dos flashcards -->
        <section class="card">
            <div class="mb-4 flex flex-wrap items-end justify-between gap-2">
                <div>
                    <h2 class="text-base font-semibold">Domínio dos flashcards</h2>
                    <p class="text-sm text-muted">{{ mastered }}% dos cartões já estão nas caixas 4 e 5 (revisão espaçada).</p>
                </div>
                <router-link to="/flashcards" class="btn btn-ghost !min-h-10">Revisar agora</router-link>
            </div>
            <div class="flex h-3 overflow-hidden rounded-full bg-brand-50" role="img" :aria-label="`Distribuição por caixa: ${d.flashcards.boxes.join(', ')}`">
                <div v-for="(n, i) in d.flashcards.boxes" :key="i" :style="{ width: (n / boxTotal) * 100 + '%', background: BOX[i].color }" />
            </div>
            <ul class="mt-4 grid grid-cols-2 gap-3 text-sm sm:grid-cols-5">
                <li v-for="(b, i) in BOX" :key="b.name" class="flex items-center gap-2">
                    <span class="size-3 shrink-0 rounded-[3px]" :style="{ background: b.color }" aria-hidden="true" />
                    <span class="text-muted">{{ b.name }}</span><strong class="ml-auto tabular-nums sm:ml-0">{{ d.flashcards.boxes[i] }}</strong>
                </li>
            </ul>
        </section>
    </div>

    <div v-else class="space-y-4" aria-busy="true">
        <div class="h-32 animate-pulse rounded-xl bg-line" />
        <div class="grid gap-4 lg:grid-cols-2"><div class="h-48 animate-pulse rounded-xl bg-line" /><div class="h-48 animate-pulse rounded-xl bg-line" /></div>
    </div>
</template>
