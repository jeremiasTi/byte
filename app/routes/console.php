<?php

use Illuminate\Support\Facades\Schedule;

// Lembretes de prazo por e-mail (rode `php artisan schedule:work` em desenvolvimento).
Schedule::command('byte:send-reminders')->hourly();
