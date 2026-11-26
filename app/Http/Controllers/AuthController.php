<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\CartService;
use App\Services\SupportChatService;
use App\Services\WishlistService;
use App\Support\Visitor;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function __construct(private CartService $cart, private WishlistService $wishlist) {}

    public function showLogin(): View
    {
        return view('pages.auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $token = Visitor::peek();

        if (! Auth::attempt([...$credentials, 'is_active' => true], $request->boolean('remember'))) {
            throw ValidationException::withMessages(['email' => 'These credentials do not match our records.']);
        }

        $request->session()->regenerate();
        $this->adoptGuestData($request->user(), $token);

        return redirect()->intended(route('home'));
    }

    public function showRegister(): View
    {
        return view('pages.auth.register');
    }

    public function register(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);

        $token = Visitor::peek();
        $user = User::query()->create($data);

        Auth::login($user);
        $request->session()->regenerate();
        $this->adoptGuestData($user, $token);

        return redirect()->intended(route('home'))->with('status', 'Welcome to FoodMart!');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }

    private function adoptGuestData(User $user, ?string $token): void
    {
        app(SupportChatService::class)->adoptGuest($user);
        $this->cart->mergeGuestCart($user, $token);
        $this->wishlist->mergeGuestList($user, $token);
        Visitor::forget();
    }
}
