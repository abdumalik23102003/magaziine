<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\MassPrunable;
use Illuminate\Database\Eloquent\Model;

/** Email orqali kirish uchun bir martalik kod (faqat hash saqlanadi). attempts va used_at servis orqali o'zgaradi. */
class LoginCode extends Model
{
    use MassPrunable;

    protected $fillable = ['email', 'code_hash', 'expires_at', 'ip_address'];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'used_at' => 'datetime',
        ];
    }

    /** Bir kundan eski yozuvlar `php artisan model:prune --model="App\Models\LoginCode"` bilan tozalanadi. */
    public function prunable(): Builder
    {
        return static::query()->where('created_at', '<', now()->subDay());
    }
}
