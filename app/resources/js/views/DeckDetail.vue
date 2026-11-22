<script setup>
import { computed, onBeforeUnmount, onMounted, reactive, ref } from 'vue';
import { useRoute } from 'vue-router';
import { PhArrowLeft, PhCards, PhCheckCircle, PhFilePdf, PhPlay, PhTrash, PhTrophy, PhUploadSimple, PhX, PhXCircle } from '@phosphor-icons/vue';
import { ApiError, api } from '../api';
import { useUi } from '../stores/ui';
import EmptyState from '../components/EmptyState.vue';

const route = useRoute();
const ui = useUi();
const id = route.params.id;
const deck = ref(null);
const form = reactive({ front: '', back: '' });
const queue = ref([]);
const total = ref(0);
const reviewing = ref(false);
const flipped = ref(false);
const stats = reactive({ right: 0, wrong: 0 });

// ---------- importação de PDF ----------
const importing = ref(false);
const dragging = ref(false);
const parsing = ref(false);
const preview = ref(null); // { mode, name, cards: [{front, back, keep}] }
const fileInput = ref(null);
const keptCount = computed(() => preview.value?.cards.filter((c) => c.keep).length ?? 0);

async function readPdf(file) {
    if (!file) return;
    if (file.type !== 'application/pdf' && !file.name.toLowerCase().endsWith('.pdf')) return ui.toast('Selecione um arquivo PDF.', 'error');
    if (file.size > 10 * 1024 * 1024) return ui.toast('O PDF deve ter no máximo 10 MB.', 'error');
    const body = new FormData();
    body.append('file', file);
    parsing.value = true;
    try {
        const res = await api.post(`/decks/${id}/import/preview`, body);
        preview.value = { mode: res.mode, name: file.name, cards: res.cards.map((c) => ({ ...c, keep: true })) };
    } catch (e) {
        ui.toast(e instanceof ApiError ? e.message : 'Falha ao ler o PDF.', 'error');
    } finally {
        parsing.value = false;
        if (fileInput.value) fileInput.value.value = '';
    }
}
function onDrop(e) {
    dragging.value = false;
    readPdf(e.dataTransfer.files[0]);
}
async function confirmImport() {
    const cards = preview.value.cards.filter((c) => c.keep && c.front.trim() && c.back.trim()).map(({ front, back }) => ({ front, back }));
    if (!cards.length) return;
    await api.post(`/decks/${id}/cards/bulk`, { cards });
    ui.toast(`${cards.length} cartões importados.`);
    preview.value = null;
    importing.value = false;
    await load();
}
function closeImport() {
    preview.value = null;
    importing.value = false;
}

const BOX_NAMES =['Aprendendo', 'Em reforço', 'Revisando', 'Quase lá', 'Dominado'];
const load = async () => (deck.value = await api.get(`/decks/${id}`));

onMounted(async () => {
    await load();
    document.addEventListener('keydown', onKey);
    if (route.query.revisar) await start();
});
onBeforeUnmount(() => document.removeEventListener('keydown', onKey));

async function addCard() {
    await api.post(`/decks/${id}/cards`, form);
    Object.assign(form, { front: '', back: '' });
    ui.toast('Cartão adicionado.');
    await load();
}
async function removeCard(c) {
    await api.del(`/cards/${c.id}`);
    await load();
}

async function start() {
    queue.value = await api.get(`/decks/${id}/review`);
    total.value = queue.value.length;
    Object.assign(stats, { right: 0, wrong: 0 });
    flipped.value = false;
    reviewing.value = true;
    if (!total.value) ui.toast('Nenhum cartão para revisar agora.', 'error');
}
const current = computed(() => queue.value[0]);
const done = computed(() => total.value - queue.value.length);
const accuracy = computed(() => (stats.right + stats.wrong ? Math.round((stats.right / (stats.right + stats.wrong)) * 100) : 0));

async function answer(correct) {
    if (!current.value) return;
    await api.post(`/cards/${current.value.id}/answer`, { correct });
    correct ? stats.right++ : stats.wrong++;
    queue.value.shift();
    flipped.value = false;
    if (!queue.value.length) await load();
}
function leave() {
    reviewing.value = false;
    load();
}

function onKey(e) {
    if (!reviewing.value || !current.value || ['INPUT', 'TEXTAREA'].includes(e.target.tagName)) return;
    if (e.key === ' ' || e.key === 'Enter') { e.preventDefault(); flipped.value = true; }
    else if (flipped.value && (e.key === '1' || e.key === 'ArrowLeft')) answer(false);
    else if (flipped.value && (e.key === '2' || e.key === 'ArrowRight')) answer(true);
}
</script>

<template>
    <div v-if="deck" class="space-y-6">
        <router-link to="/flashcards" class="inline-flex items-center gap-1 text-sm font-semibold text-brand-700 hover:underline"><PhArrowLeft :size="16" aria-hidden="true" />Baralhos</router-link>
        <div class="flex flex-wrap items-end justify-between gap-3">
            <div>
                <h1 class="text-2xl">{{ deck.name }}</h1>
                <p class="mt-1 text-muted">{{ deck.subject?.name || 'Sem disciplina' }} • {{ deck.flashcards.length }} cartões</p>
            </div>
            <div v-if="!reviewing" class="flex flex-wrap gap-2">
                <button class="btn btn-ghost" @click="importing = !importing"><PhFilePdf :size="18" aria-hidden="true" />Importar PDF</button>
                <button class="btn btn-primary" :disabled="!deck.flashcards.length" @click="start"><PhPlay :size="18" weight="fill" aria-hidden="true" />Iniciar revisão</button>
            </div>
            <button v-else class="btn btn-ghost" @click="leave">Sair da revisão</button>
        </div>

        <!-- modo revisão -->
        <div v-if="reviewing" class="mx-auto max-w-2xl space-y-4">
            <template v-if="current">
                <div>
                    <div class="mb-1.5 flex justify-between text-sm text-muted">
                        <span>Cartão {{ done + 1 }} de {{ total }}</span>
                        <span class="flex items-center gap-3">
                            <span class="flex items-center gap-1 text-success"><PhCheckCircle :size="16" weight="fill" aria-hidden="true" />{{ stats.right }}</span>
                            <span class="flex items-center gap-1 text-red-700"><PhXCircle :size="16" weight="fill" aria-hidden="true" />{{ stats.wrong }}</span>
                        </span>
                    </div>
                    <div class="h-2 overflow-hidden rounded-full bg-brand-100"><div class="h-full rounded-full bg-brand-600 transition-all duration-300" :style="{ width: (done / total) * 100 + '%' }" /></div>
                </div>

                <div
                    :key="current.id + (flipped ? 'b' : 'f')"
                    class="card byte-flip grid min-h-72 cursor-pointer place-items-center !p-10 text-center transition-colors"
                    :class="flipped ? '!border-brand-500 !bg-brand-50' : 'hover:!border-brand-500'"
                    role="button" tabindex="0" :aria-label="flipped ? 'Resposta' : 'Mostrar resposta'"
                    @click="flipped = true" @keydown.enter.space.prevent="flipped = true"
                >
                    <div>
                        <p class="mb-3 text-xs font-semibold uppercase tracking-wide text-muted">{{ flipped ? 'Resposta' : 'Pergunta' }} • {{ BOX_NAMES[current.box - 1] }}</p>
                        <p class="font-display text-xl font-medium leading-snug">{{ flipped ? current.back : current.front }}</p>
                        <p v-if="!flipped" class="mt-6 text-sm text-muted">Clique ou pressione <kbd class="rounded border border-line bg-white px-1.5">Espaço</kbd> para ver a resposta</p>
                    </div>
                </div>

                <div v-if="flipped" class="grid grid-cols-2 gap-3">
                    <button class="btn btn-danger !min-h-12" @click="answer(false)"><PhXCircle :size="20" aria-hidden="true" />Errei <kbd class="ml-1 rounded bg-red-50 px-1.5 text-xs">1</kbd></button>
                    <button class="btn btn-success !min-h-12" @click="answer(true)"><PhCheckCircle :size="20" aria-hidden="true" />Acertei <kbd class="ml-1 rounded bg-emerald-800 px-1.5 text-xs">2</kbd></button>
                </div>
            </template>

            <div v-else class="card py-10 text-center">
                <PhTrophy :size="44" weight="duotone" class="mx-auto text-gold" aria-hidden="true" />
                <p class="mt-3 font-display text-xl font-semibold">Revisão concluída</p>
                <p class="mt-1 text-muted">Aproveitamento de <strong class="tabular-nums text-ink">{{ accuracy }}%</strong> • {{ stats.right }} acerto(s) e {{ stats.wrong }} erro(s)</p>
                <button class="btn btn-primary mt-6" @click="leave">Voltar ao baralho</button>
            </div>
        </div>

        <template v-else>
            <section v-if="importing" class="card space-y-4" aria-labelledby="imp-title">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <h2 id="imp-title" class="text-lg font-semibold">Importar de um PDF</h2>
                        <p class="mt-1 text-sm text-muted">Funciona com caderno de respostas/gabarito (ex.: ENEM, no formato <em>91 A, 92 C…</em>) ou com linhas <em>P:</em> e <em>R:</em>. O PDF precisa ter texto selecionável.</p>
                    </div>
                    <button class="btn-icon -mr-2 -mt-2" aria-label="Fechar importação" @click="closeImport"><PhX :size="20" /></button>
                </div>

                <label
                    v-if="!preview"
                    class="flex cursor-pointer flex-col items-center gap-2 rounded-xl border-2 border-dashed px-6 py-10 text-center transition-colors focus-within:border-brand-600"
                    :class="dragging ? 'border-brand-600 bg-brand-50' : 'border-slate-300 hover:border-brand-500 hover:bg-brand-50'"
                    @dragover.prevent="dragging = true" @dragleave="dragging = false" @drop.prevent="onDrop"
                >
                    <PhUploadSimple :size="32" weight="duotone" class="text-brand-600" aria-hidden="true" />
                    <span class="font-semibold">{{ parsing ? 'Lendo o PDF…' : 'Arraste o PDF aqui ou clique para escolher' }}</span>
                    <span class="text-sm text-muted">Até 10 MB</span>
                    <input ref="fileInput" type="file" accept="application/pdf,.pdf" class="sr-only" :disabled="parsing" @change="readPdf($event.target.files[0])" />
                </label>

                <template v-else>
                    <div class="flex flex-wrap items-center justify-between gap-2 text-sm">
                        <p class="flex items-center gap-2">
                            <PhFilePdf :size="18" class="text-brand-600" aria-hidden="true" /><strong class="font-semibold">{{ preview.name }}</strong>
                            <span class="chip bg-brand-50 text-brand-700">{{ preview.mode === 'gabarito' ? 'Gabarito' : 'Perguntas e respostas' }}</span>
                        </p>
                        <p class="tabular-nums text-muted">{{ keptCount }} de {{ preview.cards.length }} selecionados</p>
                    </div>
                    <p v-if="preview.mode === 'gabarito'" class="rounded-lg bg-brand-50 px-3 py-2 text-sm text-brand-700">Dica: edite a frente de cada cartão para incluir o tema da questão (ex.: “Questão 91 — Genética”).</p>
                    <ul class="max-h-96 space-y-2 overflow-y-auto pr-1">
                        <li v-for="(c, i) in preview.cards" :key="i" class="flex items-center gap-2 rounded-lg border border-line p-2" :class="{ 'opacity-50': !c.keep }">
                            <input v-model="c.keep" type="checkbox" class="size-5 shrink-0" :aria-label="`Importar cartão ${i + 1}`" />
                            <input v-model="c.front" class="input !min-h-10" :aria-label="`Frente do cartão ${i + 1}`" maxlength="1000" />
                            <input v-model="c.back" class="input !min-h-10" :aria-label="`Verso do cartão ${i + 1}`" maxlength="2000" />
                        </li>
                    </ul>
                    <div class="flex flex-wrap justify-end gap-2 border-t border-line pt-4">
                        <button class="btn btn-ghost" @click="preview = null">Escolher outro PDF</button>
                        <button class="btn btn-primary" :disabled="!keptCount" @click="confirmImport">Importar {{ keptCount }} cartões</button>
                    </div>
                </template>
            </section>

            <form class="card grid gap-4 sm:grid-cols-[1fr_1fr_auto]" @submit.prevent="addCard">
                <div><label class="label" for="c-front">Frente (pergunta)</label><input id="c-front" v-model="form.front" required class="input" /></div>
                <div><label class="label" for="c-back">Verso (resposta)</label><input id="c-back" v-model="form.back" required class="input" /></div>
                <div class="flex items-end"><button class="btn btn-primary w-full sm:w-auto">Adicionar</button></div>
            </form>

            <EmptyState v-if="!deck.flashcards.length" title="Baralho vazio" text="Adicione o primeiro cartão acima.">
                <template #icon><PhCards :size="28" /></template>
            </EmptyState>
            <ul class="space-y-2">
                <li v-for="c in deck.flashcards" :key="c.id" class="card flex items-center justify-between gap-3 !p-4">
                    <div class="min-w-0">
                        <p class="font-medium">{{ c.front }}</p>
                        <p class="text-sm text-muted">{{ c.back }}</p>
                    </div>
                    <div class="flex shrink-0 items-center gap-1">
                        <span class="chip bg-brand-50 text-brand-700">{{ BOX_NAMES[c.box - 1] }}</span>
                        <button class="btn-icon btn-icon-danger" aria-label="Excluir cartão" @click="removeCard(c)"><PhTrash :size="20" /></button>
                    </div>
                </li>
            </ul>
        </template>
    </div>
</template>
