<?php

namespace App\Services\Geocoding;

/** Provayderdan mustaqil, normallashtirilgan manzil. Kalitlar checkout forma maydonlariga mos. */
final class GeocodedAddress
{
    public function __construct(
        public readonly float $lat,
        public readonly float $lng,
        public readonly ?string $displayName = null,
        public readonly ?string $country = null,
        public readonly ?string $countryCode = null,
        public readonly ?string $region = null,
        public readonly ?string $city = null,
        public readonly ?string $district = null,
        public readonly ?string $street = null,
        public readonly ?string $houseNumber = null,
        public readonly ?string $postcode = null,
    ) {}

    /**
     * Nominatim (jsonv2, addressdetails=1) elementidan. Maydon uzunliklari checkout validatsiyasiga mos kesiladi.
     *
     * @param  array<string, mixed>  $item
     */
    public static function fromNominatim(array $item): ?self
    {
        if (! isset($item['lat'], $item['lon']) || ! is_numeric($item['lat']) || ! is_numeric($item['lon'])) {
            return null;
        }

        $address = is_array($item['address'] ?? null) ? $item['address'] : [];

        $pick = static function (array $keys, int $max) use ($address): ?string {
            foreach ($keys as $key) {
                $value = $address[$key] ?? null;

                if (is_string($value) && trim($value) !== '') {
                    return mb_substr(trim($value), 0, $max);
                }
            }

            return null;
        };

        $code = isset($address['country_code']) && is_string($address['country_code']) && strlen($address['country_code']) === 2
            ? strtoupper($address['country_code'])
            : null;

        return new self(
            lat: (float) $item['lat'],
            lng: (float) $item['lon'],
            displayName: isset($item['display_name']) && is_string($item['display_name']) ? mb_substr($item['display_name'], 0, 255) : null,
            country: $pick(['country'], 80),
            countryCode: $code,
            region: $pick(['state', 'region', 'province', 'state_district'], 80),
            city: $pick(['city', 'town', 'village', 'municipality', 'hamlet'], 50),
            district: $pick(['city_district', 'suburb', 'district', 'borough', 'quarter', 'neighbourhood'], 80),
            street: $pick(['road', 'pedestrian', 'footway', 'path', 'cycleway', 'residential', 'street'], 150),
            houseNumber: $pick(['house_number'], 20),
            postcode: $pick(['postcode'], 20),
        );
    }

    /** @param  array<string, mixed>  $data */
    public static function fromArray(array $data): self
    {
        return new self(
            lat: (float) $data['lat'],
            lng: (float) $data['lng'],
            displayName: $data['display_name'] ?? null,
            country: $data['country'] ?? null,
            countryCode: $data['country_code'] ?? null,
            region: $data['region'] ?? null,
            city: $data['city'] ?? null,
            district: $data['district'] ?? null,
            street: $data['street'] ?? null,
            houseNumber: $data['house_number'] ?? null,
            postcode: $data['postcode'] ?? null,
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'lat' => $this->lat,
            'lng' => $this->lng,
            'display_name' => $this->displayName,
            'country' => $this->country,
            'country_code' => $this->countryCode,
            'region' => $this->region,
            'city' => $this->city,
            'district' => $this->district,
            'street' => $this->street,
            'house_number' => $this->houseNumber,
            'postcode' => $this->postcode,
        ];
    }
}
