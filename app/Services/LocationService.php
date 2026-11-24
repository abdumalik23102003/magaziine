<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/** Koordinatadan qisqa joy nomi ("Chicago, IL") beradi. Provayder: OSM Nominatim. */
class LocationService
{
    public function shortName(float $lat, float $lng): ?string
    {
        $key = sprintf('location:%s:%s', number_format($lat, 2, '.', ''), number_format($lng, 2, '.', ''));

        $cached = Cache::get($key);
        if (is_string($cached)) {
            return $cached;
        }

        $name = $this->lookup($lat, $lng);

        if ($name !== null) {
            Cache::put($key, $name, config('location.cache_ttl'));
        }

        return $name;
    }

    private function lookup(float $lat, float $lng): ?string
    {
        try {
            $response = Http::withHeaders(['User-Agent' => config('location.user_agent')])
                ->acceptJson()
                ->timeout(5)
                ->get(config('location.nominatim_url'), [
                    'format' => 'jsonv2',
                    'lat' => $lat,
                    'lon' => $lng,
                    'zoom' => 10,
                    'addressdetails' => 1,
                    'accept-language' => 'en',
                ]);
        } catch (ConnectionException) {
            return null;
        }

        if (! $response->successful()) {
            return null;
        }

        $address = $response->json('address');

        return is_array($address) ? $this->shorten($address) : null;
    }

    /** @param array<string, mixed> $address */
    public function shorten(array $address): ?string
    {
        $place = $address['city'] ?? $address['town'] ?? $address['village']
            ?? $address['municipality'] ?? $address['suburb'] ?? $address['county']
            ?? $address['state'] ?? $address['country'] ?? null;

        if (! is_string($place) || $place === '') {
            return null;
        }

        $country = isset($address['country_code']) ? strtolower((string) $address['country_code']) : null;
        $region = null;

        if ($country !== null && in_array($country, config('location.region_code_countries'), true)) {
            $iso = $address['ISO3166-2-lvl4'] ?? null;
            if (is_string($iso) && str_contains($iso, '-')) {
                $region = Str::after($iso, '-');
            }
        }

        $region ??= $country !== null ? strtoupper($country) : null;

        $label = $region !== null && $region !== '' && $region !== $place
            ? $place.', '.$region
            : $place;

        return Str::limit($label, config('location.label_max'), '…');
    }
}
