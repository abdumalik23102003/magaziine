<?php

namespace App\Support;

/** Bildirishnoma va mail matnlari (config('audit.mail_locale'): en yoki uz). */
final class NotificationText
{
    private const LINES = [
        'en' => [
            'submitted_title' => 'New :kind awaiting review',
            'submitted_body' => '":label" was submitted for moderation.',
            'approved_title' => 'Your :kind was approved',
            'approved_body' => '":label" is now live.',
            'rejected_title' => 'Your :kind was rejected',
            'rejected_body' => '":label" was rejected.',
            'reason' => 'Reason: :reason',
            'conversation_title' => 'New support conversation',
            'conversation_body' => 'A customer started a new conversation.',
            'product' => 'product',
            'post' => 'post',
        ],
        'uz' => [
            'submitted_title' => 'Yangi :kind ko\'rib chiqishni kutmoqda',
            'submitted_body' => '":label" moderatsiyaga yuborildi.',
            'approved_title' => ':kind tasdiqlandi',
            'approved_body' => '":label" saytda e\'lon qilindi.',
            'rejected_title' => ':kind rad etildi',
            'rejected_body' => '":label" rad etildi.',
            'reason' => 'Sabab: :reason',
            'conversation_title' => 'Yangi support suhbati',
            'conversation_body' => 'Mijoz yangi suhbat boshladi.',
            'product' => 'mahsulot',
            'post' => 'post',
        ],
    ];

    public static function t(string $key, array $replace = []): string
    {
        $locale = (string) config('audit.mail_locale', 'en');
        $line = self::LINES[$locale][$key] ?? self::LINES['en'][$key] ?? $key;

        foreach ($replace as $name => $value) {
            $line = str_replace(':'.$name, (string) $value, $line);
        }

        return $line;
    }
}
