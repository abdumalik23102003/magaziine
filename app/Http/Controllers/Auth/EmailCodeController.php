<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\EmailSignInService;
use App\Services\LoginCodeService;
use App\Services\WebSignIn;
use App\Support\OneTimeCode;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/** Email kod bilan kirish: 1) email kiritiladi, 2) emailga kelgan kod kiritiladi. Yangi email bo'lsa hisob yaratiladi. */
class EmailCodeController extends Controller
{
    private const SESSION_KEY = 'login_code.email';

    public function create(): View
    {
        return view('pages.auth.code-request');
    }

    public function store(Request $request, LoginCodeService $codes): RedirectResponse
    {
        $data = $request->validate(['email' => ['required', 'email:rfc', 'max:255']]);
        $email = LoginCodeService::normalize($data['email']);

        $codes->issue($email, $request->ip());

        $request->session()->put(self::SESSION_KEY, $email);

        return redirect()->route('login.code.verify')->with('status', "We sent a 6-digit code to {$email}.");
    }

    public function verifyForm(Request $request): View|RedirectResponse
    {
        $email = $request->session()->get(self::SESSION_KEY);

        if (! is_string($email) || $email === '') {
            return redirect()->route('login.code');
        }

        return view('pages.auth.code-verify', ['email' => $email]);
    }

    public function verify(Request $request, LoginCodeService $codes, EmailSignInService $accounts, WebSignIn $signIn): RedirectResponse
    {
        $email = $request->session()->get(self::SESSION_KEY);

        if (! is_string($email) || $email === '') {
            return redirect()->route('login.code');
        }

        $digits = preg_replace('/\D+/', '', (string) $request->input('code')) ?? '';

        if (strlen($digits) !== OneTimeCode::LENGTH) {
            throw ValidationException::withMessages(['code' => 'Enter the 6-digit code from the email.']);
        }

        if (! $codes->verify($email, $digits)) {
            throw ValidationException::withMessages(['code' => 'That code is invalid or has expired. You can request a new one.']);
        }

        $user = $accounts->userFor($email);

        $request->session()->forget(self::SESSION_KEY);
        $signIn->login($request, $user);

        return redirect()->intended(route('home'));
    }
}
