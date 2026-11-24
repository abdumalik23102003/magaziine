<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum OrderStatus: string implements HasColor, HasLabel
{
    case Pending = 'pending';
    case Processing = 'processing';
    case Shipped = 'shipped';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return ucfirst($this->value);
    }

    public function getLabel(): string
    {
        return $this->label();
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Pending => 'warning',
            self::Processing => 'info',
            self::Shipped => 'primary',
            self::Completed => 'success',
            self::Cancelled => 'danger',
        };
    }

    /** Storefront (Tailwind) uchun nishon ranglari. */
    public function badgeClass(): string
    {
        return match ($this) {
            self::Pending => 'bg-amber-400/10 text-amber-300 border-amber-300/30',
            self::Processing => 'bg-sky-400/10 text-sky-300 border-sky-300/30',
            self::Shipped => 'bg-indigo-400/10 text-indigo-300 border-indigo-300/30',
            self::Completed => 'bg-primary/10 text-primary border-primary/30',
            self::Cancelled => 'bg-red-500/10 text-red-300 border-red-400/30',
        };
    }
}
