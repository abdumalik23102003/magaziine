<?php

namespace App\Models;

use App\Enums\UserRole;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasAvatar;
use Filament\Panel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;

/** role va is_active ataylab $fillable da yo'q: oddiy forma orqali o'zgarmasin. */
#[Fillable(['name', 'email', 'password', 'phone', 'address'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements FilamentUser, HasAvatar
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected $attributes = [
        'role' => 'customer',
        'is_active' => true,
    ];

    /** @var array<int, int>|null */
    protected ?array $brandIdsCache = null;

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
            'is_active' => 'boolean',
        ];
    }

    public function hasRole(UserRole ...$roles): bool
    {
        return in_array($this->role, $roles, true);
    }

    public function isAdmin(): bool
    {
        return $this->hasRole(UserRole::Admin);
    }

    /** Admin panelga kira oladigan xodimlar. */
    public function isStaff(): bool
    {
        return $this->hasRole(UserRole::Admin, UserRole::Moderator, UserRole::Operator);
    }

    public function canAccessPanel(Panel $panel): bool
    {
        if (! $this->is_active) {
            return false;
        }

        return match ($panel->getId()) {
            'admin' => $this->isStaff(),
            'partner' => $this->hasRole(UserRole::Partner, UserRole::Admin),
            default => false,
        };
    }

    /** Profil rasmi URL i (yo'q bo'lsa null: sayt bosh harf, Filament standart avatar ko'rsatadi). */
    public function avatarUrl(): ?string
    {
        return $this->avatar_path ? Storage::disk('public')->url($this->avatar_path) : null;
    }

    /** Filament panellaridagi avatar (admin va partner). */
    public function getFilamentAvatarUrl(): ?string
    {
        return $this->avatarUrl();
    }

    public function carts(): HasMany
    {
        return $this->hasMany(Cart::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    /** Hamkor boshqaradigan brendlar. */
    public function brands(): BelongsToMany
    {
        return $this->belongsToMany(Brand::class)->withTimestamps();
    }

    public function ownsBrand(?int $brandId): bool
    {
        if ($brandId === null) {
            return false;
        }

        $this->brandIdsCache ??= $this->brands()->pluck('brands.id')->map(fn ($id): int => (int) $id)->all();

        return in_array($brandId, $this->brandIdsCache, true);
    }
}
