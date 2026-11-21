<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Flashcard;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __invoke(Request $r)
    {
        $u = $r->user();
        $total = $u->tasks()->count();
        $done = $u->tasks()->where('status', 'done')->count();
        $deckIds = $u->decks()->select('id');
        $due = fn ($q) => $q->where(fn ($w) => $w->where('next_review_at', '<=', now())->orWhereNull('next_review_at'));

        // Últimos 84 dias (12 semanas) de estudo, incluindo dias zerados — alimenta o mapa de estudo.
        $from = now()->subDays(83)->startOfDay();
        $minutesByDay = $u->studySessions()->where('studied_on', '>=', $from->toDateString())
            ->get()->groupBy(fn ($s) => $s->studied_on->toDateString())->map->sum('minutes');
        $heatmap = collect(range(0, 83))->map(fn ($i) => [
            'date' => ($d = $from->copy()->addDays($i))->toDateString(),
            'minutes' => (int) ($minutesByDay[$d->toDateString()] ?? 0),
        ]);
        $week = $heatmap->slice(-7)->values();

        $weekStart = now()->startOfWeek()->toDateString();
        $weekDone = (int) $u->studySessions()->where('studied_on', '>=', $weekStart)->sum('minutes');

        $subjects = $u->subjects()
            ->withCount(['tasks', 'tasks as done_count' => fn ($q) => $q->where('status', 'done')])
            ->withSum('studySessions as minutes', 'minutes')
            ->orderBy('name')->get(['id', 'name', 'color']);

        $boxes = Flashcard::whereIn('deck_id', $deckIds)->selectRaw('box, count(*) as c')->groupBy('box')->pluck('c', 'box');

        return [
            'tasks' => [
                'total' => $total,
                'done' => $done,
                'pending' => $total - $done,
                'overdue' => $u->tasks()->where('status', '!=', 'done')->where('due_at', '<', now())->count(),
                'completion' => $total ? round($done / $total * 100) : 0,
                'by_status' => [
                    'todo' => $u->tasks()->where('status', 'todo')->count(),
                    'doing' => $u->tasks()->where('status', 'doing')->count(),
                    'done' => $done,
                ],
            ],
            // Foco do dia: atrasadas + as que vencem até o fim de hoje.
            'today' => $u->tasks()->with('subject:id,name,color')->where('status', '!=', 'done')
                ->where('due_at', '<=', now()->endOfDay())->orderBy('due_at')->limit(6)->get(),
            'upcoming' => $u->tasks()->with('subject:id,name,color')->where('status', '!=', 'done')
                ->where('due_at', '>', now()->endOfDay())->orderBy('due_at')->limit(5)->get(),
            'goal' => ['target' => $u->weekly_goal_minutes, 'done' => $weekDone],
            'study' => [
                'week_minutes' => $week->sum('minutes'),
                'total_minutes' => (int) $u->studySessions()->sum('minutes'),
                'streak' => $this->streak($u),
                'by_day' => $week,
                'heatmap' => $heatmap,
                'by_subject' => $subjects->filter(fn ($s) => $s->minutes)->values(),
            ],
            'subjects' => $subjects,
            'flashcards' => [
                'due' => Flashcard::whereIn('deck_id', $deckIds)->where($due)->count(),
                'total' => Flashcard::whereIn('deck_id', $deckIds)->count(),
                'boxes' => collect(range(1, 5))->map(fn ($b) => (int) ($boxes[$b] ?? 0))->values(),
            ],
        ];
    }

    /** Dias consecutivos com estudo registrado (hoje ainda não quebra a sequência). */
    private function streak($u): int
    {
        $days = $u->studySessions()->pluck('studied_on')->map->toDateString()->unique()->flip();
        $d = now()->startOfDay();
        if (! isset($days[$d->toDateString()])) {
            $d->subDay();
        }
        $n = 0;
        while (isset($days[$d->toDateString()])) {
            $n++;
            $d->subDay();
        }

        return $n;
    }
}
