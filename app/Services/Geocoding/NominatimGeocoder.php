<?php

namespace App\Services\Geocoding;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\RateLimiter;

/**
 * OpenStreetMap Nominatim (yoki unga mos provayder). Ommaviy servis siyosati:
 * ~1 so'rov/soniya (shu yerda majburlanadi), aniq User-Agent, avtomatik to'ldirish yo'q, natijalar keshlanadi
 * (CachingGeocoder). Kesh bu klassdan tashqarida, shuning uchun kesh urishlari cheklovni sarflamaydi.
 */
class NominatimGeocoder implements Geocoder
{
    /** @param  array<string, mixed>  $config */
    public function __construct(private array $config) {}

    public function reverse(float $lat, float $lng): ?GeocodedAddress
    {
        $json = $this->get((string) $this->config['reverse_url'], [
            'format' => 'jsonv2',
            'lat' => $lat,
            'lon' => $lng,
            'zoom' => 18,
            'addressdetails' => 1,
        ]);

        // Nominatim topilmasa 200 va {"error": "..."} qaytaradi.
        if (! is_array($json) || isset($json['error'])) {
            return null;
        }

        $address = GeocodedAddress::fromNominatim($json);

        // Koordinatani foydalanuvchi tanlagan nuqtada qoldiramiz (provayder uni binoga "yopishtirishi" mumkin).
        return $address === null ? null : new GeocodedAddress(
            $lat, $lng, $address->displayName, $address->country, $address->countryCode, $address->region,
            $address->city, $address->district, $address->street, $address->houseNumber, $address->postcode,
        );
    }

    public function search(string $query): array
    {
        $params = [
            'q' => $query,
            'format' => 'jsonv2',
            'addressdetails' => 1,
            'limit' => 5,
        ];

        if (! empty($this->config['countrycodes'])) {
            $params['countrycodes'] = (string) $this->config['countrycodes'];
        }

        $json = $this->get((string) $this->config['search_url'], $params);

        if (! is_array($json) || ! array_is_list($json)) {
            return [];
        }

        $results = [];

        foreach ($json as $item) {
            if (is_array($item) && ($address = GeocodedAddress::fromNominatim($item)) !== null) {
                $results[] = $address;
            }
        }

        return $results;
    }

    /**
     * @param  array<string, mixed>  $params
     * @return array<mixed>|null
     */
    private function get(string $url, array $params): ?array
    {
        $perSecond = (int) ($this->config['rate_per_second'] ?? 1);

        if ($perSecond > 0 && ! RateLimiter::attempt('geocoder:nominatim', $perSecond, fn () => true, 1)) {
            throw GeocodingException::busy();
        }

        $params['accept-language'] = (string) ($this->config['language'] ?? 'en');

        if (! empty($this->config['email'])) {
            $params['email'] = (string) $this->config['email'];
        }

        if (! empty($this->config['api_key'])) {
            $params[(string) ($this->config['api_key_param'] ?? 'key')] = (string) $this->config['api_key'];
        }

        try {
            $response = $this->client()->get($url, $params)->throw();
        } catch (ConnectionException|RequestException $e) {
            throw GeocodingException::unavailable($e);
        }

        $json = $response->json();

        return is_array($json) ? $json : null;
    }

    private function client(): PendingRequest
    {
        return Http::withHeaders(['User-Agent' => (string) $this->config['user_agent']])
            ->acceptJson()
            ->timeout((int) ($this->config['timeout'] ?? 5));
    }
}
