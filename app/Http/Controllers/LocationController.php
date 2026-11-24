<?php

namespace App\Http\Controllers;

use App\Http\Requests\ResolveLocationRequest;
use App\Services\LocationService;
use Illuminate\Http\JsonResponse;

class LocationController extends Controller
{
    public function resolve(ResolveLocationRequest $request, LocationService $locations): JsonResponse
    {
        $name = $locations->shortName((float) $request->validated('lat'), (float) $request->validated('lng'));

        if ($name === null) {
            return response()->json(['message' => 'Could not detect your city. Please try again.'], 422);
        }

        $request->session()->put('location', $name);

        return response()->json(['name' => $name]);
    }
}
