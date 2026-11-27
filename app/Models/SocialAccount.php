<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Foydalanuvchining tashqi provayderdagi (Google, GitHub) hisobi. Tokenlar saqlanmaydi. */
class SocialAccount extends Model
{
    protected $fillable = ['user_id', 'provider', 'provider_user_id', 'email', 'name'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
