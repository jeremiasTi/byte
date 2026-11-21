<script setup>
import { reactive, ref } from 'vue';
import { useRouter } from 'vue-router';
import { api, setToken } from '../api';
import { useAuth } from '../stores/auth';
import { useUi } from '../stores/ui';
import PageHeader from '../components/PageHeader.vue';

const auth = useAuth();
const ui = useUi();
const router = useRouter();
const u = auth.user;
const form = reactive({
    name: u?.name ?? '',
    weekly_goal_minutes: u?.weekly_goal_minutes ?? 600,
    reminders_enabled: !!u?.reminders_enabled,
    current_password: '', password: '', password_confirmation: '',
});
const errors = ref({});
const saving = ref(false);
const delPass = ref('');
const confirmDelete = ref(false);

async function save() {
    saving.value = true;
    errors.value = {};
    try {
        auth.user = await api.put('/me', form);
        Object.assign(form, { current_password: '', password: '', password_confirmation: '' });
        ui.toast('Configurações salvas.');
    } catch (e) {
        errors.value = e.errors;
    } finally {
        saving.value = false;
    }
}

async function destroyAccount() {
    try {
        await api.del('/me', { password: delPass.value });
        setToken(null);
        auth.user = null;
        router.push('/login');
    } catch (e) {
        ui.error(e.message);
    }
}
</script>

<template>
    <div class="max-w-2xl">
        <PageHeader title="Configurações" subtitle="Perfil, meta de estudo e preferências de lembrete." />

        <form class="space-y-6" @submit.prevent="save">
            <section class="card space-y-4">
                <h2 class="text-base font-semibold">Perfil</h2>
                <div>
                    <label class="label" for="p-name">Nome</label>
                    <input id="p-name" v-model="form.name" required class="input" />
                    <p v-if="errors.name" role="alert" class="mt-1 text-sm text-red-700">{{ errors.name[0] }}</p>
                </div>
                <div>
                    <label class="label" for="p-mail">E-mail</label>
                    <input id="p-mail" :value="u?.email" disabled class="input bg-slate-50 text-muted" />
                </div>
            </section>

            <section class="card space-y-4">
                <h2 class="text-base font-semibold">Estudo e lembretes</h2>
                <div>
                    <label class="label" for="p-goal">Meta semanal de estudo (minutos)</label>
                    <input id="p-goal" v-model.number="form.weekly_goal_minutes" type="number" min="30" max="6000" step="30" required class="input max-w-48" />
                    <p class="mt-1 text-sm text-muted">Equivale a {{ Math.floor(form.weekly_goal_minutes / 60) }}h{{ String(form.weekly_goal_minutes % 60).padStart(2, '0') }} por semana.</p>
                    <p v-if="errors.weekly_goal_minutes" role="alert" class="mt-1 text-sm text-red-700">{{ errors.weekly_goal_minutes[0] }}</p>
                </div>
                <label class="flex cursor-pointer items-start gap-3">
                    <input v-model="form.reminders_enabled" type="checkbox" class="mt-1 size-5" />
                    <span>
                        <span class="block text-sm font-medium">Lembretes de prazo por e-mail</span>
                        <span class="block text-sm text-muted">Enviamos um aviso 24 horas antes do vencimento de cada tarefa em aberto.</span>
                    </span>
                </label>
            </section>

            <section class="card space-y-4">
                <h2 class="text-base font-semibold">Alterar senha</h2>
                <div>
                    <label class="label" for="p-cur">Senha atual</label>
                    <input id="p-cur" v-model="form.current_password" type="password" autocomplete="current-password" class="input" />
                    <p v-if="errors.current_password" role="alert" class="mt-1 text-sm text-red-700">{{ errors.current_password[0] }}</p>
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="label" for="p-new">Nova senha</label>
                        <input id="p-new" v-model="form.password" type="password" autocomplete="new-password" class="input" />
                        <p v-if="errors.password" role="alert" class="mt-1 text-sm text-red-700">{{ errors.password[0] }}</p>
                    </div>
                    <div>
                        <label class="label" for="p-conf">Confirmar nova senha</label>
                        <input id="p-conf" v-model="form.password_confirmation" type="password" autocomplete="new-password" class="input" />
                    </div>
                </div>
            </section>

            <div class="flex justify-end"><button class="btn btn-primary" :disabled="saving">{{ saving ? 'Salvando…' : 'Salvar alterações' }}</button></div>
        </form>

        <section class="card mt-10 border-red-200">
            <h2 class="text-base font-semibold text-red-800">Excluir conta</h2>
            <p class="mt-1 text-sm text-muted">Remove definitivamente sua conta e todos os seus dados (disciplinas, tarefas, flashcards e sessões), conforme a LGPD. Esta ação não pode ser desfeita.</p>
            <button v-if="!confirmDelete" class="btn btn-danger mt-4" @click="confirmDelete = true">Excluir minha conta</button>
            <form v-else class="mt-4 flex flex-wrap items-end gap-3" @submit.prevent="destroyAccount">
                <div class="min-w-48 flex-1">
                    <label class="label" for="p-del">Confirme com sua senha</label>
                    <input id="p-del" v-model="delPass" type="password" required class="input" />
                </div>
                <button class="btn !bg-red-700 text-white hover:!bg-red-800">Excluir definitivamente</button>
                <button type="button" class="btn btn-ghost" @click="confirmDelete = false">Cancelar</button>
            </form>
        </section>
    </div>
</template>
