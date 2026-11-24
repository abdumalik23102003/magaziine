<?php

namespace App\Policies;

use App\Policies\Concerns\AdminOnlyByDefault;

/** O'lchov birliklarini faqat admin boshqaradi. */
class UnitPolicy
{
    use AdminOnlyByDefault;
}
