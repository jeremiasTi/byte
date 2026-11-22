<script setup>
import { reactive, ref } from 'vue';
import { useRouter } from 'vue-router';
import { PhCalendarCheck, PhCards, PhChartLineUp } from '@phosphor-icons/vue';
import { useAuth } from '../stores/auth';
import BrandLogo from '../components/BrandLogo.vue';

const auth = useAuth();
const router = useRouter();
const form = reactive({ email: 'demo@byte.test', password: 'password' });
const error = ref('');
const loading = ref(false);

const perks = [
    { icon: PhCalendarCheck, text: 'Prazos e agenda semanal em um só lugar' },
    { icon: PhCards, text: 'Flashcards com revisão espaçada' },
    { icon: PhChartLineUp, text: 'Progresso de estudo por disciplina' },
];

async function submit() {
    loading.value = true;
    error.value = '';
    try {
        await auth.login(form);
        router.push('/');
    } catch (e) {
        error.value = e.errors?.email?.[0] || e.message;
    } finally {
        loading.value = false;
    }
}
</script>

<template>
    <div class="grid min-h-screen lg:grid-cols-2">
        <section class="hidden flex-col justify-between bg-shell p-12 text-white lg:flex">
            <BrandLogo dark :size="40" />
            <div>
                <h2 class="max-w-md font-display text-4xl font-semibold leading-tight">Estude com rotina, não no desespero.</h2>
                <ul class="mt-8 space-y-4">
                    <li v-for="p in perks" :key="p.text" class="flex items-center gap-3 text-shell-text">
                        <component :is="p.icon" :size="22" aria-hidden="true" />{{ p.text }}
                    </li>
                </ul>
            </div>
            <div aria-hidden="true"></div>
        </section>

        <section class="grid place-items-center p-6">
            <form class="w-full max-w-sm space-y-5" @submit.prevent="submit">
                <div>
                    <h1 class="text-2xl">Entrar</h1>
                    <p class="mt-1 text-sm text-muted">Use sua conta para acessar seus estudos.</p>
                </div>
                <div>
                    <label class="label" for="email">E-mail</label>
                    <input id="email" v-model="form.email" type="email" autocomplete="email" required class="input" />
                </div>
                <div>
                    <label class="label" for="password">Senha</label>
                    <input id="password" v-model="form.password" type="password" autocomplete="current-password" required class="input" />
                </div>
                <p v-if="error" role="alert" class="rounded-lg bg-red-50 px-3 py-2 text-sm text-red-700">{{ error }}</p>
                <button class="btn btn-primary w-full" :disabled="loading">{{ loading ? 'Entrando…' : 'Entrar' }}</button>
                <p class="text-center text-sm text-muted">
                    Novo por aqui? <router-link to="/cadastro" class="font-semibold text-brand-700 underline-offset-2 hover:underline">Criar conta</router-link>
                </p>
            </form>
        </section>
    </div>
</template>
