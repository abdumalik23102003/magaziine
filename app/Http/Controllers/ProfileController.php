<?php

namespace App\Http\Controllers;

use App\Enums\OrderStatus;
use App\Services\WishlistService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class ProfileController extends Controller
{
    public function show(Request $request, WishlistService $wishlist): View
    {
        $user = $request->user();

        $orders = $user->orders()->withCount('items')->latest('id')->paginate(6)->withQueryString();

        $spent = $user->orders()
            ->where('status', '!=', OrderStatus::Cancelled->value)
            ->sum('total_price');

        return view('pages.profile', [
            'user' => $user,
            'orders' => $orders,
            'stats' => [
                'orders' => $user->orders()->count(),
                'spent' => number_format((float) $spent, 2, '.', ''),
                'saved' => $wishlist->products()->count(),
            ],
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'phone' => ['nullable', 'string', 'max:32', 'regex:/^[0-9+()\-\s]+$/'],
            'address' => ['nullable', 'string', 'max:255'],
        ]);

        $user->update($data);

        return back()->with('status', 'Profile updated.');
    }

    public function password(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);

        $request->user()->update(['password' => $data['password']]);

        return back()->with('status', 'Password changed.');
    }
}
