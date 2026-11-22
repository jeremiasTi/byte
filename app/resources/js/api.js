const TOKEN_KEY = 'byte_token';

export const getToken = () => localStorage.getItem(TOKEN_KEY);
export const setToken = (t) => (t ? localStorage.setItem(TOKEN_KEY, t) : localStorage.removeItem(TOKEN_KEY));

export class ApiError extends Error {
    constructor(status, data) {
        super(data?.message || 'Erro inesperado');
        this.status = status;
        this.errors = data?.errors || {};
    }
}

async function request(method, url, body) {
    const isForm = body instanceof FormData;
    const res = await fetch('/api' + url, {
        method,
        headers: {
            Accept: 'application/json',
            ...(body && !isForm ? { 'Content-Type': 'application/json' } : {}),
            ...(getToken() ? { Authorization: `Bearer ${getToken()}` } : {}),
        },
        body: body ? (isForm ? body : JSON.stringify(body)) : undefined,
    });
    if (res.status === 204) return null;
    const data = await res.json().catch(() => null);
    if (!res.ok) {
        if (res.status === 401 && getToken()) {
            setToken(null);
            window.location.href = '/login';
        }
        throw new ApiError(res.status, data);
    }
    return data;
}

export const api = {
    get: (u) => request('GET', u),
    post: (u, b = {}) => request('POST', u, b),
    put: (u, b) => request('PUT', u, b),
    patch: (u, b) => request('PATCH', u, b),
    del: (u, b) => request('DELETE', u, b),
};

// ---------- helpers de formatação ----------
export const fmtDate = (d) =>
    new Date(d).toLocaleDateString('pt-BR', { day: '2-digit', month: 'short' });
export const fmtDateTime = (d) =>
    new Date(d).toLocaleString('pt-BR', { day: '2-digit', month: 'short', hour: '2-digit', minute: '2-digit' });
export const toInput = (d) => {
    const x = new Date(d);
    x.setMinutes(x.getMinutes() - x.getTimezoneOffset());
    return x.toISOString().slice(0, 16);
};
export const fmtMinutes = (m = 0) => (m >= 60 ? `${Math.floor(m / 60)}h${String(m % 60).padStart(2, '0')}` : `${m} min`);
export const todayISO = () => toInput(new Date()).slice(0, 10);

/** Prazo em linguagem natural + urgência para colorir. */
export function relDue(iso, done = false) {
    const due = new Date(iso);
    const now = new Date();
    const day = (d) => new Date(d.getFullYear(), d.getMonth(), d.getDate());
    const diff = Math.round((day(due) - day(now)) / 86400000);
    const hh = due.toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' });
    if (done) return { text: fmtDateTime(iso), tone: 'muted' };
    if (due < now) return { text: diff === 0 ? `Venceu hoje, ${hh}` : `Atrasada há ${-diff} dia(s)`, tone: 'danger' };
    if (diff === 0) return { text: `Hoje, ${hh}`, tone: 'warn' };
    if (diff === 1) return { text: `Amanhã, ${hh}`, tone: 'warn' };
    return { text: `Em ${diff} dias • ${fmtDate(iso)}`, tone: 'muted' };
}
export const TONE = { danger: 'text-red-700 font-semibold', warn: 'text-amber-800 font-semibold', muted: 'text-muted' };

export const PRIORITY = {
    low: { label: 'Baixa', cls: 'bg-slate-100 text-slate-700' },
    medium: { label: 'Média', cls: 'bg-amber-100 text-amber-800' },
    high: { label: 'Alta', cls: 'bg-red-100 text-red-800' },
};
export const STATUS = { todo: 'A fazer', doing: 'Em andamento', done: 'Concluída' };
