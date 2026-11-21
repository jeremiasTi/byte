<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Deck;
use App\Models\Flashcard;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DeckController extends Controller
{
    private function own(Request $r, Deck $deck): Deck
    {
        abort_unless($deck->user_id === $r->user()->id, 404);

        return $deck;
    }

    public function index(Request $r)
    {
        return $r->user()->decks()->with('subject:id,name,color')
            ->withCount([
                'flashcards',
                'flashcards as due_count' => fn ($q) => $q->where(fn ($w) => $w->where('next_review_at', '<=', now())->orWhereNull('next_review_at')),
            ])
            ->orderBy('name')->get();
    }

    public function store(Request $r)
    {
        $data = $r->validate([
            'name' => 'required|string|max:120',
            'subject_id' => ['nullable', Rule::exists('subjects', 'id')->where('user_id', $r->user()->id)],
        ]);

        return response()->json($r->user()->decks()->create($data), 201);
    }

    public function show(Request $r, Deck $deck)
    {
        return $this->own($r, $deck)->load('subject:id,name,color', 'flashcards');
    }

    public function destroy(Request $r, Deck $deck)
    {
        $this->own($r, $deck)->delete();

        return response()->noContent();
    }

    public function storeCard(Request $r, Deck $deck)
    {
        $data = $r->validate(['front' => 'required|string|max:1000', 'back' => 'required|string|max:2000']);

        return response()->json($this->own($r, $deck)->flashcards()->create($data + ['next_review_at' => now()]), 201);
    }

    public function destroyCard(Request $r, Flashcard $card)
    {
        $this->own($r, $card->deck);
        $card->delete();

        return response()->noContent();
    }

    /** Cartões pendentes de revisão hoje. */
    public function review(Request $r, Deck $deck)
    {
        return $this->own($r, $deck)->flashcards()
            ->where(fn ($q) => $q->where('next_review_at', '<=', now())->orWhereNull('next_review_at'))
            ->inRandomOrder()->limit(30)->get();
    }

    public function answer(Request $r, Flashcard $card)
    {
        $this->own($r, $card->deck);
        $card->review($r->validate(['correct' => 'required|boolean'])['correct']);

        return $card;
    }
}
