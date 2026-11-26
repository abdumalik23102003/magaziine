<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Mahsulot yoki post izohi. parent_id bo'lsa, bu javob (chuqurlik cheklanmagan).
 * user_id, parent_id, rating va commentable_* ataylab $fillable da yo'q: faqat CommentService belgilaydi.
 */
class Comment extends Model
{
    protected $fillable = ['body'];

    public function commentable(): MorphTo
    {
        return $this->morphTo();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function replies(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function isReply(): bool
    {
        return $this->parent_id !== null;
    }
}
