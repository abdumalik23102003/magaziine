<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum UserRole: string implements HasColor, HasLabel
{
    case Customer = 'customer';
    case Admin = 'admin';
    case Moderator = 'moderator';
    case Operator = 'operator';
    case Partner = 'partner';

    public function getLabel(): string
    {
        return ucfirst($this->value);
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Customer => 'gray',
            self::Admin => 'danger',
            self::Moderator => 'warning',
            self::Operator => 'info',
            self::Partner => 'success',
        };
    }
}
