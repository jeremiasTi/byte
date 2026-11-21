<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Subject;
use Illuminate\Http\Request;

class SubjectController extends Controller
{
    private function rules(): array
    {
        return [
            'name' => 'required|string|max:120',
            'professor' => 'nullable|string|max:120',
            'color' => ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/'],
        ];
    }

    public function index(Request $r)
    {
        return $r->user()->subjects()
            ->withCount([
                'tasks',
                'tasks as pending_tasks_count' => fn ($q) => $q->where('status', '!=', 'done'),
                'tasks as done_count' => fn ($q) => $q->where('status', 'done'),
            ])
            ->withSum('studySessions as total_minutes', 'minutes')
            ->orderBy('name')->get();
    }

    public function store(Request $r)
    {
        return response()->json($r->user()->subjects()->create($r->validate($this->rules())), 201);
    }

    public function update(Request $r, Subject $subject)
    {
        abort_unless($subject->user_id === $r->user()->id, 404);
        $subject->update($r->validate($this->rules()));

        return $subject;
    }

    public function destroy(Request $r, Subject $subject)
    {
        abort_unless($subject->user_id === $r->user()->id, 404);
        $subject->delete();

        return response()->noContent();
    }
}
