# Byte

Plataforma web de organização dos estudos para universitários (MVP): disciplinas, tarefas, agenda semanal, lembretes por e-mail, flashcards com revisão espaçada e painel de progresso.

**Stack:** Laravel 13 (API REST + Sanctum) · Vue 3 (Vue Router + Pinia) · Tailwind 4 · SQLite

## Como rodar

```sh
cd app
composer install && npm install
cp .env.example .env && php artisan key:generate
php artisan migrate --seed
npm run build            # ou: npm run dev (hot reload)
php artisan serve        # http://localhost:8000
```

Login de demonstração: `demo@byte.test` / `password`

Mais detalhes (lembretes, módulos) em [app/README.md](app/README.md).
