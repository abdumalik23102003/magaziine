<?php

use App\Models\LoginCode;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Eski email kirish kodlarini (1 kundan eski) har kuni tozalaymiz.
Schedule::command('model:prune', ['--model' => [LoginCode::class]])->daily();
