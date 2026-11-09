<?php

namespace App\Models;

use App\Support\WorkspaceRoles;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable(['title', 'status', 'user_id'])]
class Workspace extends Model
{
    use HasFactory;

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->withTimestamps();
    }

    public function isOwnedBy(User $user): bool
    {
        return (int) $this->user_id === (int) $user->id;
    }

    public function hasMember(User $user): bool
    {
        return $this->isOwnedBy($user)
            || $this->members()
                ->whereKey($user->id)
                ->exists();
    }

    public function roleOf(User $user): ?string
    {
        if ($this->isOwnedBy($user)) {
            return 'owner';
        }

        if (! $this->hasMember($user)) {
            return null;
        }

        return WorkspaceRoles::within(
            $this,
            $user,
            fn (User $member) => $member->getRoleNames()->first()
        );
    }
}