<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable(['title', 'user_id', 'description', 'category', 'resource'])]
class TrainingSession extends Model
{
    /** @use HasFactory<\Database\Factories\TrainingSessionFactory> */
    use HasFactory;
 
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function workspaces(): BelongsToMany
    {
        return $this->belongsToMany(Workspace::class)
            ->withTimestamps();
    }
}
