<?php

use App\Providers\AppServiceProvider;
use App\Providers\Filament\AdminPanelProvider;
use App\Providers\Filament\PartnerPanelProvider;
use App\Providers\PlatformServiceProvider;

return [
    AppServiceProvider::class,
    PlatformServiceProvider::class,
    AdminPanelProvider::class,
    PartnerPanelProvider::class,
    App\Providers\TelegramBotServiceProvider::class,
];
