<?php

namespace App\Services;

use Smalot\PdfParser\Parser;

/**
 * Extrai pares frente/verso de PDFs.
 *
 * Formatos reconhecidos:
 *  - "prova": caderno de questões (blocos "QUESTÃO 91") + gabarito em outro PDF.
 *  - "pergunta": linhas "P:/Pergunta:" seguidas de "R:/Resposta:".
 *  - "gabarito": só o caderno de respostas, com pares "número + letra" (91 A, 92 C…).
 */
class PdfCardExtractor
{
    public const MAX_CARDS = 300;
    public const MAX_FRONT = 4000;
    public const MAX_BACK = 2000;

    /** @return array{mode: string, cards: array<int, array{front: string, back: string}>, missing?: int} */
    public function extract(string $path, ?string $answerKeyPath = null): array
    {
        $text = $this->text($path);

        $questions = $this->questions($text);
        if (count($questions) >= 3) {
            $key = $this->answerKey($answerKeyPath ? $this->text($answerKeyPath) : $text);
            if (! $key) {
                throw new \DomainException('Encontrei as questões, mas não o gabarito. Envie também o PDF do gabarito.');
            }

            return $this->join($questions, $key);
        }

        $cards = $this->questionAnswer($text);
        if ($cards) {
            return ['mode' => 'pergunta', 'cards' => array_slice($cards, 0, self::MAX_CARDS)];
        }

        $key = $this->answerKey($text);

        return ['mode' => 'gabarito', 'cards' => array_slice($this->keyCards($key), 0, self::MAX_CARDS)];
    }

    private function text(string $path): string
    {
        $text = (new Parser)->parseFile($path)->getText();

        return str_replace(["\r\n", "\r", "\t"], ["\n", "\n", ' '], $text);
    }

    /** @return array<int, string> número da questão => texto (enunciado + alternativas) */
    private function questions(string $text): array
    {
        $parts = preg_split('/(?:^|\n)\s*QUEST[ÃA]O\s+(\d{1,3})\b[^\n]*\n/iu', $text, -1, PREG_SPLIT_DELIM_CAPTURE);
        $out = [];
        for ($i = 1; $i + 1 < count($parts); $i += 2) {
            $n = (int) $parts[$i];
            $body = $this->clean($parts[$i + 1], true);
            if ($n >= 1 && mb_strlen($body) > 20 && ! isset($out[$n])) {
                $out[$n] = $body;
            }
        }

        return $out;
    }

    private function join(array $questions, array $key): array
    {
        ksort($questions);
        $cards = [];
        $missing = 0;
        foreach ($questions as $n => $body) {
            if (! isset($key[$n])) {
                $missing++;

                continue;
            }
            $letter = $key[$n];
            $front = "Questão {$n}\n".$this->withoutAlternatives($body);
            $alt = $this->alternative($body, $letter);

            $cards[] = [
                'front' => mb_substr($front, 0, self::MAX_FRONT),
                'back' => mb_substr("Alternativa {$letter}".($alt ? " — {$alt}" : ''), 0, self::MAX_BACK),
            ];
        }

        return ['mode' => 'prova', 'cards' => array_slice($cards, 0, self::MAX_CARDS), 'missing' => $missing];
    }

    /** Texto da alternativa (A–E) dentro do bloco da questão. */
    private function alternative(string $body, string $letter): ?string
    {
        if (preg_match('/(?:^|\n)\s*'.$letter.'\s*[\)\.\-]?\s+(.+?)(?=\n\s*[A-E]\s*[\)\.\-]?\s+\S|\z)/su', $body, $m)) {
            return trim(preg_replace('/\s+/u', ' ', $m[1]));
        }

        return null;
    }

    /** Enunciado sem a lista de alternativas, que ficam só no verso (a correta). */
    private function withoutAlternatives(string $body): string
    {
        if (preg_match('/^(.*?)(?=\n\s*A\s*[\)\.\-]?\s+\S[^\n]*\n\s*B\s*[\)\.\-]?\s+\S)/su', $body, $m)) {
            return trim($m[1]);
        }

        return $body;
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
                    $cards[] = ['front' => mb_substr($front, 0, self::MAX_FRONT), 'back' => mb_substr($back, 0, self::MAX_BACK)];
                }
            }
        }

        return $cards;
    }

    /** @return array<int, string> número => letra (primeira ocorrência de cada número) */
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

        return $found;
    }

    private function keyCards(array $key): array
    {
        return array_map(
            fn ($n, $letter) => ['front' => "Questão {$n}", 'back' => "Alternativa {$letter}"],
            array_keys($key), $key
        );
    }

    /** Normaliza espaços; com $keepLines preserva quebras de linha (para achar as alternativas). */
    private function clean(string $s, bool $keepLines = false): string
    {
        if (! $keepLines) {
            return trim(preg_replace('/\s+/u', ' ', $s));
        }
        $s = preg_replace('/[ \x{00A0}]+/u', ' ', $s);
        $s = preg_replace('/\n\s*\n+/u', "\n", $s);

        return trim($s);
    }
}
