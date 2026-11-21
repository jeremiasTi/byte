<div style="font-family:Arial,sans-serif;max-width:480px;margin:auto">
    <h2 style="color:#4f46e5">Byte</h2>
    <p>Olá, {{ $task->user->name }}! Uma tarefa está chegando ao prazo:</p>
    <div style="border-left:4px solid #4f46e5;padding:8px 14px;background:#f5f5ff">
        <strong>{{ $task->title }}</strong><br>
        @if($task->subject) Disciplina: {{ $task->subject->name }}<br>@endif
        Prazo: {{ $task->due_at->format('d/m/Y H:i') }}<br>
        Prioridade: {{ ['low' => 'Baixa', 'medium' => 'Média', 'high' => 'Alta'][$task->priority] }}
    </div>
    <p style="color:#666;font-size:12px">Você recebe este e-mail porque ativou lembretes no Byte.</p>
</div>
