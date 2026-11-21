<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class AgendaController extends Controller
{
    /** Tarefas de uma semana (segunda a domingo), agrupadas por dia. */
    public function week(Request $r)
    {
        $r->validate(['start' => 'nullable|date']);
        $start = Carbon::parse($r->start ?? now())->startOfWeek();
        $end = $start->copy()->endOfWeek();

        $tasks = $r->user()->tasks()->with('subject:id,name,color')
            ->whereBetween('due_at', [$start, $end])->orderBy('due_at')->get();

        $days = collect(range(0, 6))->map(function ($i) use ($start, $tasks) {
            $d = $start->copy()->addDays($i);

            return [
                'date' => $d->toDateString(),
                'tasks' => $tasks->filter(fn ($t) => $t->due_at->isSameDay($d))->values(),
            ];
        });

        return [
            'start' => $start->toDateString(),
            'end' => $end->toDateString(),
            'days' => $days,
            'workload' => $tasks->where('status', '!=', 'done')->count(),
        ];
    }
}
