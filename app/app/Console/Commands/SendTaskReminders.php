<?php

namespace App\Console\Commands;

use App\Mail\TaskReminder;
use App\Models\Task;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

#[Signature('byte:send-reminders {--hours=24 : Janela de antecedência em horas}')]
#[Description('Envia por e-mail lembretes das tarefas que vencem em breve')]
class SendTaskReminders extends Command
{
    public function handle(): int
    {
        $tasks = Task::with('user', 'subject')
            ->where('status', '!=', 'done')
            ->whereNull('reminder_sent_at')
            ->whereHas('user', fn ($q) => $q->where('reminders_enabled', true))
            ->whereBetween('due_at', [now(), now()->addHours((int) $this->option('hours'))])
            ->get();

        foreach ($tasks as $task) {
            Mail::to($task->user->email)->send(new TaskReminder($task));
            $task->forceFill(['reminder_sent_at' => now()])->save();
        }

        $this->info("{$tasks->count()} lembrete(s) enviado(s).");

        return self::SUCCESS;
    }
}
