import { defineStore } from 'pinia';
import { api, getToken, setToken } from '../api';

export const useAuth = defineStore('auth', {
    state: () => ({ user: null, ready: false }),
    getters: { logged: () => !!getToken() },
    actions: {
        async load() {
            if (getToken() && !this.user) {
                try {
                    this.user = await api.get('/me');
                } catch {
                    setToken(null);
                }
            }
            this.ready = true;
        },
        async login(form) {
            const r = await api.post('/login', form);
            setToken(r.token);
            this.user = r.user;
        },
        async register(form) {
            const r = await api.post('/register', form);
            setToken(r.token);
            this.user = r.user;
        },
        async logout() {
            try { await api.post('/logout'); } catch { /* token já inválido */ }
            setToken(null);
            this.user = null;
        },
    },
});
