<?php

namespace App\Policies;

use App\Policies\Concerns\AdminOnlyByDefault;

/** Kategoriyalarni faqat admin boshqaradi. */
class CategoryPolicy
{
    use AdminOnlyByDefault;
}
