<script setup>
import { reactive, ref } from 'vue';
import { useRouter } from 'vue-router';
import { useAuth } from '../stores/auth';

const auth = useAuth();
const router = useRouter();
const form = reactive({ name: '', email: '', password: '', password_confirmation: '' });
const errors = ref({});
const loading = ref(false);

async function submit() {
    loading.value = true;
    errors.value = {};
    try {
        await auth.register(form);
        router.push('/');
    } catch (e) {
        errors.value = e.errors;
    } finally {
        loading.value = false;
    }
}
</script>

<template>
    <div class="grid min-h-screen place-items-center p-4">
        <form class="card w-full max-w-sm space-y-4 !p-8" @submit.prevent="submit">
            <div>
                <h1 class="text-2xl">Criar conta</h1>
                <p class="mt-1 text-sm text-muted">Coletamos apenas os dados necessários (LGPD).</p>
            </div>
            <div v-for="f in [
                ['name', 'Nome', 'text'], ['email', 'E-mail', 'email'],
                ['password', 'Senha (mín. 8 caracteres)', 'password'], ['password_confirmation', 'Confirmar senha', 'password'],
            ]" :key="f[0]">
                <label class="label">{{ f[1] }}</label>
                <input v-model="form[f[0]]" :type="f[2]" required class="input" />
                <p v-if="errors[f[0]]" role="alert" class="mt-1 text-sm text-red-700">{{ errors[f[0]][0] }}</p>
            </div>
            <button class="btn btn-primary w-full" :disabled="loading">{{ loading ? 'Criando…' : 'Cadastrar' }}</button>
            <p class="text-center text-sm text-muted">
                Já tem conta? <router-link to="/login" class="font-semibold text-brand-700 hover:underline">Entrar</router-link>
            </p>
        </form>
    </div>
</template>
