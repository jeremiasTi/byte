<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\StudySession;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class StudySessionController extends Controller
{
    public function index(Request $r)
    {
        return $r->user()->studySessions()->with('subject:id,name,color')
            ->orderByDesc('studied_on')->orderByDesc('id')->limit(50)->get();
    }

    public function store(Request $r)
    {
        $data = $r->validate([
            'subject_id' => ['nullable', Rule::exists('subjects', 'id')->where('user_id', $r->user()->id)],
            'studied_on' => 'required|date|before_or_equal:today',
            'minutes' => 'required|integer|min:1|max:960',
            'notes' => 'nullable|string|max:255',
        ]);

        return response()->json($r->user()->studySessions()->create($data)->load('subject:id,name,color'), 201);
    }

    public function destroy(Request $r, StudySession $session)
    {
        abort_unless($session->user_id === $r->user()->id, 404);
        $session->delete();

        return response()->noContent();
    }
}
