<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TaskController extends Controller
{
    private function rules(Request $r): array
    {
        return [
            'title' => 'required|string|max:160',
            'description' => 'nullable|string|max:2000',
            'due_at' => 'required|date',
            'priority' => ['required', Rule::in(['low', 'medium', 'high'])],
            'status' => ['sometimes', Rule::in(['todo', 'doing', 'done'])],
            'subject_id' => ['nullable', Rule::exists('subjects', 'id')->where('user_id', $r->user()->id)],
        ];
    }

    private function prepare(Task $task, array $data): array
    {
        if (isset($data['status'])) {
            $data['completed_at'] = $data['status'] === 'done' ? ($task->completed_at ?? now()) : null;
        }
        // novo prazo => novo lembrete
        if ($task->exists && isset($data['due_at']) && $task->due_at->ne(\Illuminate\Support\Carbon::parse($data['due_at']))) {
            $task->reminder_sent_at = null;
        }

        return $data;
    }

    public function index(Request $r)
    {
        $q = $r->user()->tasks()->with('subject:id,name,color')
            ->when($r->status, fn ($q, $v) => $q->where('status', $v))
            ->when($r->priority, fn ($q, $v) => $q->where('priority', $v))
            ->when($r->subject_id, fn ($q, $v) => $q->where('subject_id', $v))
            ->when($r->search, fn ($q, $v) => $q->where('title', 'like', "%$v%"));

        return $q->orderByRaw("status = 'done'")->orderBy('due_at')->get();
    }

    public function store(Request $r)
    {
        $data = $r->validate($this->rules($r));
        $task = new Task();
        $task->user_id = $r->user()->id;
        $task->fill($this->prepare($task, $data))->save();

        return response()->json($task->load('subject:id,name,color'), 201);
    }

    public function update(Request $r, Task $task)
    {
        abort_unless($task->user_id === $r->user()->id, 404);
        $task->fill($this->prepare($task, $r->validate($this->rules($r))))->save();

        return $task->load('subject:id,name,color');
    }

    /** Troca rápida de status (usada ao arrastar cartões no quadro Kanban). */
    public function status(Request $r, Task $task)
    {
        abort_unless($task->user_id === $r->user()->id, 404);
        $data = $r->validate(['status' => ['required', Rule::in(['todo', 'doing', 'done'])]]);
        $task->fill($this->prepare($task, $data))->save();

        return $task->load('subject:id,name,color');
    }

    public function destroy(Request $r, Task $task)
    {
        abort_unless($task->user_id === $r->user()->id, 404);
        $task->delete();

        return response()->noContent();
    }
}
