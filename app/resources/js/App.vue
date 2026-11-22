<script setup>
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import {
    PhBooks, PhCalendarBlank, PhCards, PhCheckSquare, PhGearSix, PhList, PhPlus, PhSignOut, PhSquaresFour, PhTimer, PhX,
} from '@phosphor-icons/vue';
import { useAuth } from './stores/auth';
import { useUi } from './stores/ui';
import Toasts from './components/Toasts.vue';
import BrandLogo from './components/BrandLogo.vue';
import TaskForm from './components/TaskForm.vue';

const auth = useAuth();
const ui = useUi();
const route = useRoute();
const router = useRouter();
const menu = ref(false);

const onKey = (e) => {
    const tag = e.target.tagName;
    if (e.key === 'n' && !e.ctrlKey && !e.metaKey && !['INPUT', 'TEXTAREA', 'SELECT'].includes(tag) && route.meta.auth && !ui.quickTask) {
        e.preventDefault();
        ui.quickTask = true;
    }
};
onMounted(() => {
    auth.load();
    document.addEventListener('keydown', onKey);
});
onBeforeUnmount(() => document.removeEventListener('keydown', onKey));
watch(() => route.fullPath, () => (menu.value = false));

const showShell = computed(() => route.meta.auth);
const links = [
    { to: '/', label: 'Painel', icon: PhSquaresFour },
    { to: '/tarefas', label: 'Tarefas', icon: PhCheckSquare },
    { to: '/agenda', label: 'Agenda', icon: PhCalendarBlank },
    { to: '/disciplinas', label: 'Disciplinas', icon: PhBooks },
    { to: '/flashcards', label: 'Flashcards', icon: PhCards },
    { to: '/estudo', label: 'Estudo', icon: PhTimer },
];
const initials = computed(() => (auth.user?.name || '?').split(' ').map((p) => p[0]).slice(0, 2).join('').toUpperCase());
const today = new Date().toLocaleDateString('pt-BR', { weekday: 'long', day: 'numeric', month: 'long' });

async function logout() {
    await auth.logout();
    router.push('/login');
}
</script>

<template>
    <Toasts />
    <div v-if="!showShell"><router-view /></div>

    <div v-else class="min-h-screen lg:flex">
        <header class="flex items-center justify-between border-b border-line bg-white px-4 py-2 lg:hidden">
            <BrandLogo :size="30" />
            <button class="btn-icon" :aria-expanded="menu" aria-label="Abrir menu" @click="menu = !menu">
                <component :is="menu ? PhX : PhList" :size="22" />
            </button>
        </header>

        <aside
            :class="menu ? 'block' : 'hidden'"
            class="w-full shrink-0 bg-shell p-4 lg:sticky lg:top-0 lg:flex lg:h-screen lg:w-64 lg:flex-col"
        >
            <div class="mb-8 hidden px-2 lg:block"><BrandLogo dark tagline /></div>

            <nav class="space-y-1" aria-label="Principal">
                <router-link
                    v-for="l in links" :key="l.to" :to="l.to"
                    class="flex min-h-11 items-center gap-3 rounded-lg px-3 text-sm font-semibold text-shell-text transition-colors hover:bg-shell-hover hover:text-white"
                    active-class="!bg-shell-hover !text-white"
                    :exact-active-class="l.to === '/' ? '!bg-shell-hover !text-white' : ''"
                >
                    <component :is="l.icon" :size="20" aria-hidden="true" />{{ l.label }}
                </router-link>
            </nav>

            <div class="mt-6 space-y-1 border-t border-shell-line pt-4 lg:mt-auto">
                <router-link
                    to="/configuracoes"
                    class="flex min-h-11 items-center gap-3 rounded-lg px-3 text-sm font-semibold text-shell-text transition-colors hover:bg-shell-hover hover:text-white"
                    active-class="!bg-shell-hover !text-white"
                ><PhGearSix :size="20" aria-hidden="true" />Configurações</router-link>
                <div class="flex items-center gap-3 px-1 pt-2">
                    <div class="grid size-10 shrink-0 place-items-center rounded-full bg-shell-hover text-sm font-bold text-white">{{ initials }}</div>
                    <div class="min-w-0 flex-1">
                        <p class="truncate text-sm font-semibold text-white">{{ auth.user?.name }}</p>
                        <p class="truncate text-xs text-shell-text">{{ auth.user?.email }}</p>
                    </div>
                    <button class="btn-icon !text-shell-text hover:!bg-shell-hover hover:!text-white" aria-label="Sair" @click="logout"><PhSignOut :size="20" /></button>
                </div>
            </div>
        </aside>

        <div class="min-w-0 flex-1">
            <div class="hidden items-center justify-between border-b border-line bg-white px-8 py-3 lg:flex">
                <p class="text-sm capitalize text-muted">{{ today }}</p>
                <button class="btn btn-primary !min-h-10" @click="ui.quickTask = true">
                    <PhPlus :size="16" weight="bold" aria-hidden="true" />Nova tarefa
                    <kbd class="ml-1 rounded bg-brand-700 px-1.5 text-xs font-medium">N</kbd>
                </button>
            </div>
            <main class="px-4 py-6 sm:px-8 sm:py-8">
                <div class="mx-auto max-w-6xl"><router-view :key="route.fullPath + ui.tick" /></div>
            </main>
        </div>

        <button
            class="btn btn-primary fixed bottom-5 right-5 z-30 !size-14 !rounded-full !p-0 shadow-lg shadow-slate-900/25 lg:hidden"
            aria-label="Nova tarefa" @click="ui.quickTask = true"
        ><PhPlus :size="24" weight="bold" /></button>
    </div>

    <TaskForm v-if="ui.quickTask" @close="ui.quickTask = false" @saved="ui.refresh()" />
</template>
