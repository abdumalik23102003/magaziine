<?php

namespace App\Services\Geocoding;

use Illuminate\Contracts\Cache\Repository;

/** Istalgan Geocoder ni o'raydi: bir xil nuqta/so'rov provayderga ikkinchi marta bormaydi. Xatolar keshlanmaydi. */
class CachingGeocoder implements Geocoder
{
    public function __construct(
        private Geocoder $inner,
        private Repository $cache,
        private int $ttl,
        private int $missTtl,
        private string $namespace = 'geocoding',
    ) {}

    public function reverse(float $lat, float $lng): ?GeocodedAddress
    {
        // 5 xona ~ 1 metr: bir xil uy uchun kesh urishi, qo'shni uyga noto'g'ri manzil bermaydi.
        $key = sprintf('%s:rev:%s:%s', $this->namespace, number_format($lat, 5, '.', ''), number_format($lng, 5, '.', ''));

        $hit = $this->cache->get($key);

        if (is_array($hit)) {
            return isset($hit['none']) ? null : GeocodedAddress::fromArray($hit);
        }

        $address = $this->inner->reverse($lat, $lng);

        $this->cache->put($key, $address?->toArray() ?? ['none' => true], $address ? $this->ttl : $this->missTtl);

        return $address;
    }

    public function search(string $query): array
    {
        $key = $this->namespace.':search:'.md5(mb_strtolower(trim($query)));

        $hit = $this->cache->get($key);

        if (is_array($hit)) {
            return array_map(fn (array $row): GeocodedAddress => GeocodedAddress::fromArray($row), $hit);
        }

        $results = $this->inner->search($query);

        $this->cache->put($key, array_map(fn (GeocodedAddress $a): array => $a->toArray(), $results), $results !== [] ? $this->ttl : $this->missTtl);

        return $results;
    }
}
