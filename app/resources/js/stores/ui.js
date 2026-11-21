import { defineStore } from 'pinia';

let seq = 0;

export const useUi = defineStore('ui', {
    state: () => ({ toasts: [], tick: 0, quickTask: false }),
    actions: {
        toast(message, type = 'success') {
            const id = ++seq;
            this.toasts.push({ id, message, type });
            setTimeout(() => this.dismiss(id), 4000);
        },
        error(message) {
            this.toast(message, 'error');
        },
        dismiss(id) {
            this.toasts = this.toasts.filter((t) => t.id !== id);
        },
        /** Força as telas abertas a recarregarem (ex.: após tarefa rápida). */
        refresh() {
            this.tick++;
        },
    },
});
