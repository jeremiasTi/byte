<?php

namespace App\Mail;

use App\Models\Task;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class TaskReminder extends Mailable
{
    public function __construct(public Task $task) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Byte • Lembrete: '.$this->task->title);
    }

    public function content(): Content
    {
        return new Content(view: 'emails.task-reminder');
    }
}
