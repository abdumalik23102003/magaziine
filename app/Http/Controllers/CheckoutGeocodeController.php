<?php

namespace App\Http\Controllers;

use App\Http\Requests\ResolveLocationRequest;
use App\Services\Geocoding\Geocoder;
use App\Services\Geocoding\GeocodingException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Checkout xaritasi uchun brauzer -> bizning server -> provayder. Brauzer provayderga to'g'ridan-to'g'ri bormaydi,
 * shuning uchun User-Agent, kesh va tezlik cheklovi bir joyda, provayderni almashtirish esa JS ga ta'sir qilmaydi.
 */
class CheckoutGeocodeController extends Controller
{
    public function reverse(ResolveLocationRequest $request, Geocoder $geocoder): JsonResponse
    {
        try {
            $address = $geocoder->reverse((float) $request->validated('lat'), (float) $request->validated('lng'));
        } catch (GeocodingException $e) {
            return $this->failure($e);
        }

        if ($address === null) {
            return response()->json(['message' => 'We could not find an address for this spot. Move the pin or fill in the fields manually.'], 404);
        }

        return response()->json(['address' => $address->toArray()]);
    }

    public function search(Request $request, Geocoder $geocoder): JsonResponse
    {
        $data = $request->validate(['q' => ['required', 'string', 'min:3', 'max:120']]);

        try {
            $results = $geocoder->search($data['q']);
        } catch (GeocodingException $e) {
            return $this->failure($e);
        }

        return response()->json(['results' => array_map(fn ($address): array => $address->toArray(), $results)]);
    }

    private function failure(GeocodingException $e): JsonResponse
    {
        return $e->busy
            ? response()->json(['message' => $e->getMessage()], 429)->header('Retry-After', '2')
            : response()->json(['message' => $e->getMessage()], 503);
    }
}
