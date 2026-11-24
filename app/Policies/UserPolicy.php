<?php

namespace App\Policies;

use App\Policies\Concerns\AdminOnlyByDefault;

/** Foydalanuvchilar, rollar va faollashtirish: faqat admin. */
class UserPolicy
{
    use AdminOnlyByDefault;
}
