<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Flashcard extends Model
{
    protected $fillable = ['front', 'back'];

    /** Intervalo (em dias) de cada caixa do sistema Leitner. */
    public const INTERVALS = [1 => 0, 2 => 1, 3 => 3, 4 => 7, 5 => 14];

    protected function casts(): array
    {
        return ['next_review_at' => 'datetime', 'last_reviewed_at' => 'datetime'];
    }

    public function deck(): BelongsTo { return $this->belongsTo(Deck::class); }

    /** Registra a revisão: acertou sobe de caixa, errou volta para a 1. */
    public function review(bool $correct): void
    {
        $this->box = $correct ? min(5, $this->box + 1) : 1;
        $this->last_reviewed_at = now();
        $this->next_review_at = now()->addDays(self::INTERVALS[$this->box]);
        $this->save();
    }
}
