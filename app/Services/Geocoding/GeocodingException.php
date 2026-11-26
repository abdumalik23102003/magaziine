<?php

namespace App\Services\Geocoding;

use RuntimeException;
use Throwable;

/** Provayder ishlamayapti yoki so'rovlar cheklovi oshdi (manzil topilmasa, bu xato emas: null qaytadi). */
class GeocodingException extends RuntimeException
{
    public function __construct(string $message, public readonly bool $busy = false, ?Throwable $previous = null)
    {
        parent::__construct($message, 0, $previous);
    }

    public static function busy(): self
    {
        return new self('The address service is busy. Please try again in a moment.', true);
    }

    public static function unavailable(?Throwable $previous = null): self
    {
        return new self('The address service is temporarily unavailable.', false, $previous);
    }
}
