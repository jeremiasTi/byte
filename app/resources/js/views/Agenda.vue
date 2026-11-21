<script setup>
import { computed, onMounted, ref } from 'vue';
import { PhArrowLeft, PhArrowRight, PhCheckCircle, PhCircle } from '@phosphor-icons/vue';
import { api, PRIORITY } from '../api';
import { useUi } from '../stores/ui';
import PageHeader from '../components/PageHeader.vue';

const ui = useUi();
const week = ref(null);
const start = ref(null);

async function load() {
    week.value = await api.get('/agenda' + (start.value ? `?start=${start.value}` : ''));
    start.value = week.value.start;
}
onMounted(load);

function shift(days) {
    const d = new Date(start.value + 'T12:00');
    d.setDate(d.getDate() + days);
    start.value = d.toISOString().slice(0, 10);
    load();
}
function today() { start.value = null; load(); }

async function toggle(t) {
    await api.patch(`/tasks/${t.id}/status`, { status: t.status === 'done' ? 'todo' : 'done' });
    if (t.status !== 'done') ui.toast('Tarefa concluída.');
    await load();
}

const label = computed(() => {
    if (!week.value) return '';
    const f = (s) => new Date(s + 'T12:00').toLocaleDateString('pt-BR', { day: '2-digit', month: 'short' });
    return `${f(week.value.start)} – ${f(week.value.end)}`;
});
const dayParts = (s) => {
    const d = new Date(s + 'T12:00');
    return { name: d.toLocaleDateString('pt-BR', { weekday: 'short' }).replace('.', ''), num: d.getDate() };
};
const isToday = (s) => s === new Date().toLocaleDateString('sv-SE');
const hour = (iso) => new Date(iso).toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' });
const total = computed(() => week.value?.days.reduce((n, d) => n + d.tasks.length, 0) ?? 0);
</script>

<template>
    <div>
        <PageHeader title="Agenda semanal" :subtitle="week ? `${week.workload} em aberto de ${total} tarefa(s) nesta semana` : ''">
            <button class="btn btn-ghost" aria-label="Semana anterior" @click="shift(-7)"><PhArrowLeft :size="18" /></button>
            <span class="min-w-36 text-center text-sm font-semibold">{{ label }}</span>
            <button class="btn btn-ghost" aria-label="Próxima semana" @click="shift(7)"><PhArrowRight :size="18" /></button>
            <button class="btn btn-ghost" @click="today">Hoje</button>
        </PageHeader>

        <div v-if="week" class="grid gap-3 md:grid-cols-2 lg:grid-cols-7">
            <section
                v-for="d in week.days" :key="d.date" :aria-label="d.date"
                class="rounded-xl border p-3 lg:min-h-64"
                :class="isToday(d.date) ? 'border-brand-600 bg-white ring-1 ring-brand-600' : 'border-line bg-white'"
            >
                <header class="mb-3 flex items-baseline justify-between">
                    <span class="text-xs font-semibold uppercase tracking-wide" :class="isToday(d.date) ? 'text-brand-700' : 'text-muted'">{{ dayParts(d.date).name }}</span>
                    <span class="grid size-8 place-items-center rounded-full font-display text-base font-semibold tabular-nums" :class="isToday(d.date) ? 'bg-brand-600 text-white' : ''">{{ dayParts(d.date).num }}</span>
                </header>
                <p v-if="!d.tasks.length" class="py-4 text-center text-xs text-muted">Livre</p>
                <article v-for="t in d.tasks" :key="t.id" class="mb-2 rounded-lg bg-brand-50 p-2.5 text-sm last:mb-0">
                    <div class="flex items-start gap-2">
                        <button class="mt-0.5 shrink-0 text-muted hover:text-success" :aria-label="(t.status === 'done' ? 'Reabrir: ' : 'Concluir: ') + t.title" @click="toggle(t)">
                            <component :is="t.status === 'done' ? PhCheckCircle : PhCircle" :size="20" :weight="t.status === 'done' ? 'fill' : 'regular'" :class="t.status === 'done' ? 'text-success' : ''" />
                        </button>
                        <div class="min-w-0">
                            <p class="font-medium leading-snug" :class="{ 'text-muted line-through': t.status === 'done' }">{{ t.title }}</p>
                            <p class="mt-1 flex flex-wrap items-center gap-x-2 gap-y-1 text-xs text-muted">
                                <span class="tabular-nums">{{ hour(t.due_at) }}</span>
                                <span v-if="t.subject" class="flex items-center gap-1"><span class="size-2 rounded-full" :style="{ background: t.subject.color }" aria-hidden="true" />{{ t.subject.name }}</span>
                            </p>
                            <span class="chip mt-1.5" :class="PRIORITY[t.priority].cls">{{ PRIORITY[t.priority].label }}</span>
                        </div>
                    </div>
                </article>
            </section>
        </div>
    </div>
</template>
