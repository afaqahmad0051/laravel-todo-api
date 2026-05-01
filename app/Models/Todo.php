<?php

namespace App\Models;

use App\Enums\TaskStatus;
use Database\Factories\TodoFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

#[Fillable(['user_id', 'title', 'description', 'status'])]
class Todo extends Model
{
    /** @use HasFactory<TodoFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => TaskStatus::class,
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Returns true when the given user owns this ToDo.
     *
     * Used by FormRequests to authorize per-resource actions
     * (view / update / delete).
     */
    public function isOwnedBy(?User $user): bool
    {
        return $user !== null && $user->getKey() === $this->user_id;
    }
}
