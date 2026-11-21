<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subjects', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->string('name');
            $t->string('professor')->nullable();
            $t->string('color', 9)->default('#2150a8');
            $t->timestamps();
        });

        Schema::create('tasks', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->foreignId('subject_id')->nullable()->constrained()->nullOnDelete();
            $t->string('title');
            $t->text('description')->nullable();
            $t->dateTime('due_at');
            $t->string('priority', 10)->default('medium'); // low | medium | high
            $t->string('status', 10)->default('todo');      // todo | doing | done
            $t->dateTime('reminder_sent_at')->nullable();
            $t->dateTime('completed_at')->nullable();
            $t->timestamps();
            $t->index(['user_id', 'due_at']);
        });

        Schema::create('decks', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->foreignId('subject_id')->nullable()->constrained()->nullOnDelete();
            $t->string('name');
            $t->timestamps();
        });

        Schema::create('flashcards', function (Blueprint $t) {
            $t->id();
            $t->foreignId('deck_id')->constrained()->cascadeOnDelete();
            $t->text('front');
            $t->text('back');
            $t->unsignedTinyInteger('box')->default(1); // Leitner 1..5
            $t->dateTime('next_review_at')->nullable();
            $t->dateTime('last_reviewed_at')->nullable();
            $t->timestamps();
        });

        Schema::create('study_sessions', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->foreignId('subject_id')->nullable()->constrained()->nullOnDelete();
            $t->date('studied_on');
            $t->unsignedSmallInteger('minutes');
            $t->string('notes')->nullable();
            $t->timestamps();
            $t->index(['user_id', 'studied_on']);
        });
    }

    public function down(): void
    {
        foreach (['study_sessions', 'flashcards', 'decks', 'tasks', 'subjects'] as $t) {
            Schema::dropIfExists($t);
        }
    }
};
