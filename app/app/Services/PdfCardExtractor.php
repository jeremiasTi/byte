<?php

namespace App\Services;

use Smalot\PdfParser\Parser;

/**
 * Extrai pares frente/verso de um PDF.
 *
 * Formatos reconhecidos:
 *  - "pergunta": linhas "P:/Pergunta:" seguidas de "R:/Resposta:".
 *  - "gabarito": cadernos de respostas (ex.: ENEM) com pares "número + letra" (91 A, 92 C…).
 */
class PdfCardExtractor
{
    public const MAX_CARDS = 300;

    /** @return array{mode: string, cards: array<int, array{front: string, back: string}>} */
    public function extract(string $path): array
    {
        $text = (new Parser)->parseFile($path)->getText();
        $text = str_replace(["\r\n", "\r", "\t"], ["\n", "\n", ' '], $text);

        $cards = $this->questionAnswer($text);
        if ($cards) {
            return ['mode' => 'pergunta', 'cards' => array_slice($cards, 0, self::MAX_CARDS)];
        }

        return ['mode' => 'gabarito', 'cards' => array_slice($this->answerKey($text), 0, self::MAX_CARDS)];
    }

    private function questionAnswer(string $text): array
    {
        $cards = [];
        $pattern = '/(?:^|\n)\s*(?:P|Pergunta)\s*[:\-)]\s*(.+?)\s*\n\s*(?:R|Resposta)\s*[:\-)]\s*(.+?)(?=\n\s*(?:P|Pergunta)\s*[:\-)]|\z)/isu';
        if (preg_match_all($pattern, $text, $m, PREG_SET_ORDER)) {
            foreach ($m as $x) {
                $front = $this->clean($x[1]);
                $back = $this->clean($x[2]);
                if ($front !== '' && $back !== '') {
                    $cards[] = ['front' => mb_substr($front, 0, 1000), 'back' => mb_substr($back, 0, 2000)];
                }
            }
        }

        return $cards;
    }

    private function answerKey(string $text): array
    {
        $found = [];
        if (preg_match_all('/(?<!\d)(\d{1,3})\s*[-–.:)|]?\s*([A-E])(?![\p{L}\d])/u', $text, $m, PREG_SET_ORDER)) {
            foreach ($m as $x) {
                $n = (int) $x[1];
                if ($n >= 1 && $n <= 300 && ! isset($found[$n])) {
                    $found[$n] = $x[2];
                }
            }
        }
        ksort($found);

        return array_map(
            fn ($n, $letter) => ['front' => "Questão {$n}", 'back' => "Alternativa {$letter}"],
            array_keys($found), $found
        );
    }

    private function clean(string $s): string
    {
        return trim(preg_replace('/\s+/u', ' ', $s));
    }
}
