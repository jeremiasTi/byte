<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Deck;
use App\Models\Flashcard;
use App\Services\PdfCardExtractor;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use DomainException;
use Throwable;

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

    /** Lê um PDF (caderno de respostas ou perguntas/respostas) e devolve os cartões para conferência. */
    public function importPreview(Request $r, Deck $deck, PdfCardExtractor $extractor)
    {
        $this->own($r, $deck);
        $r->validate(['file' => 'required|file|mimes:pdf|max:10240', 'gabarito' => 'nullable|file|mimes:pdf|max:10240']);

        try {
            $result = $extractor->extract($r->file('file')->getRealPath(), $r->file('gabarito')?->getRealPath());
        } catch (DomainException $e) {
            abort(422, $e->getMessage());
        } catch (Throwable) {
            abort(422, 'Não foi possível ler este PDF. Ele pode estar protegido ou ser apenas imagem escaneada.');
        }
        abort_if(empty($result['cards']), 422, 'Nenhum cartão encontrado. Use um PDF com texto selecionável (gabarito ou linhas "P:"/"R:").');

        return $result;
    }

    /** Salva vários cartões de uma vez (após conferência no importador). */
    public function storeCards(Request $r, Deck $deck)
    {
        $data = $r->validate([
            'cards' => 'required|array|min:1|max:'.PdfCardExtractor::MAX_CARDS,
            'cards.*.front' => 'required|string|max:'.PdfCardExtractor::MAX_FRONT,
            'cards.*.back' => 'required|string|max:2000',
        ]);
        $this->own($r, $deck);
        $rows = array_map(fn ($c) => ['front' => $c['front'], 'back' => $c['back']], $data['cards']);
        $deck->flashcards()->createMany(array_map(fn ($c) => $c + ['next_review_at' => now()], $rows));

        return response()->json(['created' => count($rows)], 201);
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
