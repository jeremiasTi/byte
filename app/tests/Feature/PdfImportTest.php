<?php

namespace Tests\Feature;

use App\Models\Deck;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class PdfImportTest extends TestCase
{
    use RefreshDatabase;

    private function pdf(array $lines): UploadedFile
    {
        $c = "BT /F1 12 Tf 50 780 Td 16 TL\n";
        foreach ($lines as $l) {
            $c .= "($l) '\n";
        }
        $c .= 'ET';
        $o = [
            1 => '<< /Type /Catalog /Pages 2 0 R >>',
            2 => '<< /Type /Pages /Kids [3 0 R] /Count 1 >>',
            3 => '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Contents 4 0 R /Resources << /Font << /F1 5 0 R >> >> >>',
            4 => '<< /Length '.strlen($c)."  >>\nstream\n$c\nendstream",
            5 => '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>',
        ];
        $b = "%PDF-1.4\n";
        $off = [];
        foreach ($o as $i => $x) {
            $off[$i] = strlen($b);
            $b .= "$i 0 obj\n$x\nendobj\n";
        }
        $x = strlen($b);
        $b .= "xref\n0 6\n0000000000 65535 f \n";
        foreach ($off as $v) {
            $b .= sprintf("%010d 00000 n \n", $v);
        }
        $b .= "trailer\n<< /Size 6 /Root 1 0 R >>\nstartxref\n$x\n%%EOF";

        return UploadedFile::fake()->createWithContent('gabarito.pdf', $b);
    }

    public function test_gabarito_preview_and_bulk_save(): void
    {
        $user = User::factory()->create();
        $deck = $user->decks()->create(['name' => 'ENEM']);

        $res = $this->actingAs($user)->postJson("/api/decks/{$deck->id}/import/preview", ['file' => $this->pdf(['91 A 92 C', '93 - E'])]);
        $res->assertOk()->assertJsonPath('mode', 'gabarito')->assertJsonCount(3, 'cards');

        $this->actingAs($user)->postJson("/api/decks/{$deck->id}/cards/bulk", ['cards' => $res->json('cards')])->assertCreated();
        $this->assertSame(3, $deck->flashcards()->count());
    }

    public function test_prova_with_gabarito_builds_cards_with_statement(): void
    {
        $user = User::factory()->create();
        $deck = $user->decks()->create(['name' => 'ENEM']);
        $prova = $this->pdf([
            'QUESTAO 91', 'Qual organela produz energia para a celula nos seres vivos eucariontes?',
            'A nucleo', 'B mitocondria', 'C ribossomo',
            'QUESTAO 92', 'Qual gas e consumido na fotossintese pelas plantas verdes em geral?',
            'A oxigenio', 'B nitrogenio', 'C gas carbonico',
            'QUESTAO 93', 'Qual o principal componente da parede celular das plantas em geral?',
            'A celulose', 'B quitina', 'C lipidio',
        ]);

        $res = $this->actingAs($user)->postJson("/api/decks/{$deck->id}/import/preview", ['file' => $prova, 'gabarito' => $this->pdf(['91 B 92 C 93 A'])]);
        $res->assertOk()->assertJsonPath('mode', 'prova')->assertJsonCount(3, 'cards');
        $this->assertStringContainsString('organela', $res->json('cards.0.front'));
        $this->assertStringContainsString('Alternativa B', $res->json('cards.0.back'));
        $this->assertStringContainsString('mitocondria', $res->json('cards.0.back'));
    }

    public function test_prova_without_gabarito_is_rejected(): void
    {
        $user = User::factory()->create();
        $deck = $user->decks()->create(['name' => 'ENEM']);
        $prova = $this->pdf(['QUESTAO 91', 'Enunciado longo da primeira questao aqui', 'QUESTAO 92', 'Enunciado longo da segunda questao aqui', 'QUESTAO 93', 'Enunciado longo da terceira questao aqui']);

        $this->actingAs($user)->postJson("/api/decks/{$deck->id}/import/preview", ['file' => $prova])->assertStatus(422);
    }

    public function test_other_users_deck_is_not_accessible(): void
    {
        $deck = User::factory()->create()->decks()->create(['name' => 'X']);

        $this->actingAs(User::factory()->create())
            ->postJson("/api/decks/{$deck->id}/import/preview", ['file' => $this->pdf(['91 A'])])
            ->assertNotFound();
    }

    public function test_pdf_without_cards_is_rejected(): void
    {
        $user = User::factory()->create();
        $deck = $user->decks()->create(['name' => 'Vazio']);

        $this->actingAs($user)->postJson("/api/decks/{$deck->id}/import/preview", ['file' => $this->pdf(['Texto sem gabarito'])])->assertStatus(422);
    }
}
