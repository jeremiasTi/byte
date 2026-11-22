# Byte – Plataforma Web de Organização dos Estudos (MVP)

Laravel 13 (API REST + Sanctum) + Vue 3 (SPA com Vue Router e Pinia) + Tailwind 4 + SQLite.

## Rodar
```
composer install && npm install
cp .env.example .env && php artisan key:generate
php artisan migrate --seed
npm run build            # ou: npm run dev (hot reload)
php artisan serve        # http://localhost:8000
```
Login de demonstração: **demo@byte.test / password**

## Lembretes por e-mail
`php artisan byte:send-reminders` envia e-mail das tarefas que vencem em 24h
(agendado de hora em hora: `php artisan schedule:work`). Com `MAIL_MAILER=log`
os e-mails aparecem em `storage/logs/laravel.log`.

## Módulos do plano
Cadastro/autenticação · Disciplinas · Tarefas (prazo, prioridade, status, filtros) ·
Agenda semanal · Lembretes por e-mail · Flashcards (revisão espaçada Leitner) ·
Sessões de estudo + painel de progresso.

## Importar PDF nos flashcards
Na tela de um baralho, **Importar PDF** lê cadernos de respostas/gabaritos (ex.: ENEM, `91 A, 92 C…`) ou PDFs com linhas `P:` / `R:`, mostra uma prévia editável e só então cria os cartões. O PDF precisa ter texto selecionável (não escaneado).
