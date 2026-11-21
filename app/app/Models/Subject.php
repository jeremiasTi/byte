<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Subject extends Model
{
    protected $fillable = ['name', 'professor', 'color'];

    public function tasks(): HasMany { return $this->hasMany(Task::class); }
    public function studySessions(): HasMany { return $this->hasMany(StudySession::class); }
}
