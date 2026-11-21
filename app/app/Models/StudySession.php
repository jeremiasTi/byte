<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudySession extends Model
{
    protected $fillable = ['subject_id', 'studied_on', 'minutes', 'notes'];

    protected function casts(): array
    {
        return ['studied_on' => 'date:Y-m-d'];
    }

    public function subject(): BelongsTo { return $this->belongsTo(Subject::class); }
}
