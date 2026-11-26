<?php

namespace App\Notifications;

use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

/** Filament paneli o'qiy oladigan database bildirishnoma (navbat orqali yuboriladi). */
abstract class BaseNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public string $title, public string $body)
    {
        $this->afterCommit();
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return FilamentNotification::make()->title($this->title)->body($this->body)->getDatabaseMessage();
    }
}
