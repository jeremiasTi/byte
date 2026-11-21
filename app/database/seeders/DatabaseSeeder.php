<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /** Usuário de demonstração: demo@byte.test / password */
    public function run(): void
    {
        mt_srand(42); // dados de estudo reprodutíveis

        $u = User::create(['name' => 'Aluno Demo', 'email' => 'demo@byte.test', 'password' => 'password']);
        $u->forceFill(['weekly_goal_minutes' => 600])->save();

        $subjects = collect([
            ['Engenharia de Software', 'Prof. Ricardo Santos', '#2150a8'],
            ['Banco de Dados', 'Profa. Marta Oliveira', '#0f7b4d'],
            ['Estruturas de Dados', 'Prof. Paulo Mendes', '#b4690e'],
            ['Redes de Computadores', 'Prof. Lima Duarte', '#0e7490'],
        ])->map(fn ($s) => $u->subjects()->create(['name' => $s[0], 'professor' => $s[1], 'color' => $s[2]]));

        $tasks = [
            ['Entregar diagrama de classes', 0, '+0 day 21:00', 'high', 'doing'],
            ['Lista de exercícios de SQL', 1, '+2 days 23:59', 'medium', 'todo'],
            ['Prova de Estruturas de Dados', 2, '+4 days 19:00', 'high', 'todo'],
            ['Resumo do capítulo 5 (TCP/IP)', 3, '+3 days 12:00', 'low', 'todo'],
            ['Protótipo das telas do Byte', 0, '+6 days 20:00', 'medium', 'doing'],
            ['Seminário de Redes', 3, '-1 day 10:00', 'high', 'todo'],
            ['Estudar normalização (1FN–3FN)', 1, '+1 day 18:00', 'medium', 'todo'],
            ['Implementar árvore binária', 2, '+8 days 22:00', 'medium', 'todo'],
            ['Relatório de modelagem ER', 1, '-3 days 18:00', 'medium', 'done'],
            ['Exercícios de pilhas e filas', 2, '-2 days 18:00', 'low', 'done'],
            ['Casos de uso do Byte', 0, '-5 days 18:00', 'high', 'done'],
            ['Configurar laboratório de redes', 3, '-4 days 15:00', 'low', 'done'],
        ];
        foreach ($tasks as [$title, $i, $due, $prio, $status]) {
            $t = $u->tasks()->make([
                'title' => $title, 'subject_id' => $subjects[$i]->id, 'priority' => $prio,
                'status' => $status, 'due_at' => now()->modify($due),
            ]);
            $t->completed_at = $status === 'done' ? now()->subDays(1) : null;
            $t->save();
        }

        // 12 semanas de histórico (mais intenso em dias úteis; alguns dias em branco).
        for ($ago = 0; $ago < 84; $ago++) {
            $day = now()->subDays($ago);
            if ($ago > 6 && mt_rand(1, 100) <= ($day->isWeekend() ? 55 : 25)) {
                continue;
            }
            $u->studySessions()->create([
                'subject_id' => $subjects[mt_rand(0, 3)]->id,
                'studied_on' => $day->toDateString(),
                'minutes' => [25, 30, 45, 50, 60, 75, 90, 120][mt_rand(0, 7)],
                'notes' => ['Revisão de conteúdo', 'Lista de exercícios', 'Leitura do material', 'Preparação para prova'][mt_rand(0, 3)],
            ]);
        }

        $decks = [
            ['SQL Essencial', 1, [
                ['O que significa ACID?', 'Atomicidade, Consistência, Isolamento e Durabilidade.', 5],
                ['Diferença entre INNER JOIN e LEFT JOIN?', 'INNER traz só correspondências; LEFT mantém todas as linhas da tabela da esquerda.', 3],
                ['Para que serve uma chave estrangeira?', 'Garante integridade referencial entre duas tabelas.', 4],
                ['O que é normalização?', 'Organizar tabelas para reduzir redundância e anomalias.', 1],
                ['O que faz o GROUP BY?', 'Agrupa linhas com o mesmo valor para aplicar funções de agregação.', 2],
                ['HAVING x WHERE?', 'WHERE filtra linhas antes do agrupamento; HAVING filtra grupos depois.', 1],
            ]],
            ['Estruturas de Dados', 2, [
                ['Qual a política de uma pilha?', 'LIFO – o último a entrar é o primeiro a sair.', 5],
                ['Qual a política de uma fila?', 'FIFO – o primeiro a entrar é o primeiro a sair.', 4],
                ['Complexidade da busca binária?', 'O(log n), exige vetor ordenado.', 2],
                ['Vantagem da lista encadeada?', 'Inserção e remoção em O(1) quando se tem o nó, sem deslocar elementos.', 1],
            ]],
            ['Redes e Protocolos', 3, [
                ['Quantas camadas tem o modelo OSI?', 'Sete: física, enlace, rede, transporte, sessão, apresentação e aplicação.', 3],
                ['TCP x UDP?', 'TCP é confiável e orientado a conexão; UDP é leve e sem garantia de entrega.', 2],
                ['Para que serve o DNS?', 'Traduz nomes de domínio em endereços IP.', 1],
            ]],
        ];
        foreach ($decks as [$name, $si, $cards]) {
            $deck = $u->decks()->create(['name' => $name, 'subject_id' => $subjects[$si]->id]);
            foreach ($cards as [$f, $b, $box]) {
                $deck->flashcards()->create([
                    'front' => $f, 'back' => $b,
                ]);
            }
            foreach ($deck->flashcards as $c) {
                $box = collect($cards)->firstWhere(0, $c->front)[2];
                $c->forceFill([
                    'box' => $box,
                    // caixas altas voltam só no futuro; as baixas já estão para revisão
                    'next_review_at' => $box >= 4 ? now()->addDays($box * 2) : now()->subHour(),
                ])->save();
            }
        }
    }
}
