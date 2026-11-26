<?php

namespace Tests\Unit;

use App\Services\Geocoding\GeocodedAddress;
use PHPUnit\Framework\TestCase;

class GeocodedAddressTest extends TestCase
{
    public function test_it_maps_a_full_nominatim_result(): void
    {
        $address = GeocodedAddress::fromNominatim([
            'lat' => '41.3', 'lon' => '69.24', 'display_name' => 'Amir Temur 10, Tashkent',
            'address' => [
                'house_number' => '10', 'road' => 'Amir Temur', 'suburb' => 'Mirzo Ulugbek', 'city' => 'Tashkent',
                'state' => 'Tashkent Region', 'postcode' => '100000', 'country' => 'Uzbekistan', 'country_code' => 'uz',
            ],
        ]);

        $this->assertEquals(
            ['street' => 'Amir Temur', 'house_number' => '10', 'district' => 'Mirzo Ulugbek', 'city' => 'Tashkent', 'region' => 'Tashkent Region', 'postcode' => '100000', 'country' => 'Uzbekistan', 'country_code' => 'UZ'],
            array_intersect_key($address->toArray(), array_flip(['street', 'house_number', 'district', 'city', 'region', 'postcode', 'country', 'country_code'])),
        );
        $this->assertSame(41.3, $address->lat);
    }

    public function test_missing_components_stay_null_and_fallback_keys_are_used(): void
    {
        $address = GeocodedAddress::fromNominatim(['lat' => 1, 'lon' => 2, 'address' => ['village' => 'Small Village', 'footway' => 'Path Rd']]);

        $this->assertSame('Small Village', $address->city);
        $this->assertSame('Path Rd', $address->street);
        $this->assertNull($address->houseNumber);
        $this->assertNull($address->postcode);
        $this->assertNull($address->countryCode);
    }

    public function test_invalid_coordinates_are_rejected(): void
    {
        $this->assertNull(GeocodedAddress::fromNominatim(['address' => ['city' => 'X']]));
        $this->assertNull(GeocodedAddress::fromNominatim(['lat' => 'abc', 'lon' => '1']));
    }

    public function test_fields_are_trimmed_to_checkout_limits(): void
    {
        $address = GeocodedAddress::fromNominatim(['lat' => 1, 'lon' => 2, 'address' => ['city' => str_repeat('a', 90), 'house_number' => str_repeat('1', 40)]]);

        $this->assertSame(50, mb_strlen($address->city));
        $this->assertSame(20, mb_strlen($address->houseNumber));
    }

    public function test_array_round_trip(): void
    {
        $address = new GeocodedAddress(1.5, 2.5, 'X', 'Uzbekistan', 'UZ', 'R', 'C', 'D', 'S', '9', '1');

        $this->assertEquals($address, GeocodedAddress::fromArray($address->toArray()));
    }
}
