<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'reminders_enabled' => 'boolean',
            'weekly_goal_minutes' => 'integer',
        ];
    }

    public function subjects(): HasMany { return $this->hasMany(Subject::class); }
    public function tasks(): HasMany { return $this->hasMany(Task::class); }
    public function decks(): HasMany { return $this->hasMany(Deck::class); }
    public function studySessions(): HasMany { return $this->hasMany(StudySession::class); }
}
