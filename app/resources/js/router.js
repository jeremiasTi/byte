import { createRouter, createWebHistory } from 'vue-router';
import { getToken } from './api';

const routes = [
    { path: '/login', component: () => import('./views/Login.vue'), meta: { guest: true } },
    { path: '/cadastro', component: () => import('./views/Register.vue'), meta: { guest: true } },
    { path: '/', component: () => import('./views/Dashboard.vue'), meta: { auth: true, title: 'Painel' } },
    { path: '/disciplinas', component: () => import('./views/Subjects.vue'), meta: { auth: true, title: 'Disciplinas' } },
    { path: '/tarefas', component: () => import('./views/Tasks.vue'), meta: { auth: true, title: 'Tarefas' } },
    { path: '/agenda', component: () => import('./views/Agenda.vue'), meta: { auth: true, title: 'Agenda semanal' } },
    { path: '/flashcards', component: () => import('./views/Decks.vue'), meta: { auth: true, title: 'Flashcards' } },
    { path: '/flashcards/:id', component: () => import('./views/DeckDetail.vue'), meta: { auth: true, title: 'Flashcards' } },
    { path: '/estudo', component: () => import('./views/Study.vue'), meta: { auth: true, title: 'Sessões de estudo' } },
    { path: '/configuracoes', component: () => import('./views/Settings.vue'), meta: { auth: true, title: 'Configurações' } },
    { path: '/:pathMatch(.*)*', redirect: '/' },
];

const router = createRouter({ history: createWebHistory(), routes });

router.beforeEach((to) => {
    if (to.meta.auth && !getToken()) return '/login';
    if (to.meta.guest && getToken()) return '/';
});

router.afterEach((to) => {
    document.title = (to.meta.title ? to.meta.title + ' • ' : '') + 'Byte';
});

export default router;
