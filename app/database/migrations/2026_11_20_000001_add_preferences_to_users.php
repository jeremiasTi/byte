<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $t) {
            $t->unsignedSmallInteger('weekly_goal_minutes')->default(600); // meta semanal de estudo
            $t->boolean('reminders_enabled')->default(true);
        });
    }

    public function down(): void
    {
        Schema::table('users', fn (Blueprint $t) => $t->dropColumn(['weekly_goal_minutes', 'reminders_enabled']));
    }
};
