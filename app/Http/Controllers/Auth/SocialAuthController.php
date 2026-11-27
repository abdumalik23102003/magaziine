<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\SocialAuthService;
use App\Services\WebSignIn;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Laravel\Socialite\Contracts\Factory as Socialite;
use Symfony\Component\HttpFoundation\RedirectResponse as ProviderRedirect;
use Throwable;

/** Google va GitHub orqali kirish (Laravel Socialite). Provayderlar config/services.php da sozlanadi. */
class SocialAuthController extends Controller
{
    public function redirect(string $provider, SocialAuthService $social, Socialite $socialite): ProviderRedirect
    {
        abort_unless($social->isEnabled($provider), 404);

        return $socialite->driver($provider)->redirect();
    }

    public function callback(string $provider, Request $request, SocialAuthService $social, Socialite $socialite, WebSignIn $signIn): RedirectResponse
    {
        abort_unless($social->isEnabled($provider), 404);

        $label = $social->label($provider);

        // Foydalanuvchi provayder sahifasida "Cancel" bosgan bo'lsa.
        if ($request->has('error')) {
            return $this->failed("Sign in with {$label} was cancelled.");
        }

        try {
            $user = $social->resolveUser($provider, $socialite->driver($provider)->user());
        } catch (ValidationException $e) {
            return $this->failed((string) collect($e->errors())->flatten()->first());
        } catch (Throwable $e) {
            // Yaroqsiz state, muddati o'tgan kod, provayder xatosi: tafsilot logga, foydalanuvchiga umumiy xabar.
            report($e);

            return $this->failed("Could not sign in with {$label}. Please try again.");
        }

        $signIn->login($request, $user);

        return redirect()->intended(route('home'));
    }

    private function failed(string $message): RedirectResponse
    {
        return redirect()->route('login')->withErrors(['auth' => $message]);
    }
}
