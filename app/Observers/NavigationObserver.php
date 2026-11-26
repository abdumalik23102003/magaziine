<?php

namespace App\Observers;

use App\Services\NavigationService;
use Illuminate\Database\Eloquent\Model;

/** Kategoriya, brend, post yoki mahsulot o'zgarganda Katalog dropdowni keshi tozalanadi. */
class NavigationObserver
{
    public function saved(Model $model): void
    {
        NavigationService::flush();
    }

    public function deleted(Model $model): void
    {
        NavigationService::flush();
    }

    public function restored(Model $model): void
    {
        NavigationService::flush();
    }
}
