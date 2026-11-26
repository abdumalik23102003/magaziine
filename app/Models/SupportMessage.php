<?php

namespace App\Models;

use App\Enums\SenderType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupportMessage extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = ['conversation_id', 'sender_type', 'sender_id', 'body', 'read_at'];

    protected function casts(): array
    {
        return [
            'sender_type' => SenderType::class,
            'read_at' => 'datetime',
        ];
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(SupportConversation::class, 'conversation_id');
    }

    public function toPayload(): array
    {
        return [
            'id' => $this->id,
            'sender_type' => $this->sender_type->value,
            'body' => $this->body,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
