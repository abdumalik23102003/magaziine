#!/usr/bin/env bash
# Parolsiz kirish: email kod + Google/GitHub + Telegram (sayt) -- magaziine loyihasi uchun.
# Ishga tushirish: loyiha ildizida (artisan yonida):  bash apply-auth-upgrade.sh
set -euo pipefail

[ -f artisan ] && [ -f app/Services/TelegramAuthService.php ] || { echo "XATO: skriptni loyiha ildizida (artisan turgan papkada) ishga tushiring."; exit 1; }

MODIFIED=(
  ".env.example"
  "app/Filament/Resources/Users/Schemas/UserForm.php"
  "app/Http/Controllers/ProfileController.php"
  "app/Models/User.php"
  "app/Services/TelegramAuthService.php"
  "config/services.php"
  "config/telegram.php"
  "resources/views/pages/auth/login.blade.php"
  "resources/views/pages/profile.blade.php"
  "routes/web.php"
  "tests/Feature/TelegramAuthTest.php"
)

# Qo'lda o'zgartirilgan (commit qilinmagan) fayllar ustiga yozib yubormaslik uchun.
if command -v git >/dev/null 2>&1 && git rev-parse --is-inside-work-tree >/dev/null 2>&1; then
  if ! git diff --quiet -- "${MODIFIED[@]}" 2>/dev/null; then
    echo "XATO: quyidagi fayllarda commit qilinmagan o'zgarishlar bor. Avval commit/stash qiling:"
    git diff --name-only -- "${MODIFIED[@]}"
    exit 1
  fi
fi

BACKUP=".auth-upgrade-backup/$(date +%Y%m%d-%H%M%S)"
for f in "${MODIFIED[@]}"; do
  [ -f "$f" ] && mkdir -p "$BACKUP/$(dirname "$f")" && cp "$f" "$BACKUP/$f"
done
echo "Zaxira nusxa: $BACKUP"

echo "== Yangi fayllar =="

mkdir -p "app/Http/Controllers/Auth"
cat > "app/Http/Controllers/Auth/EmailCodeController.php" <<'__AUTH_EOF__'
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
__AUTH_EOF__
echo "  + app/Http/Controllers/Auth/EmailCodeController.php"

mkdir -p "app/Http/Controllers/Auth"
cat > "app/Http/Controllers/Auth/SocialAuthController.php" <<'__AUTH_EOF__'
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
__AUTH_EOF__
echo "  + app/Http/Controllers/Auth/SocialAuthController.php"

mkdir -p "app/Http/Controllers/Auth"
cat > "app/Http/Controllers/Auth/TelegramWidgetController.php" <<'__AUTH_EOF__'
<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\TelegramAuthService;
use App\Services\WebSignIn;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * Saytda "Telegram bilan kirish" tugmasi (Telegram Login Widget).
 * Mini App bilan bir xil telegram_id ga asoslanadi, shuning uchun bitta odam ikkala joyda ham bitta hisobga kiradi.
 */
class TelegramWidgetController extends Controller
{
    public function callback(Request $request, TelegramAuthService $telegram, WebSignIn $signIn): RedirectResponse
    {
        abort_unless(filled(config('telegram.bot_token')) && filled(config('telegram.bot_username')), 404);

        try {
            $user = $telegram->userFor($telegram->verifyWidget($request->query()));
        } catch (ValidationException $e) {
            return redirect()->route('login')->withErrors(['auth' => (string) collect($e->errors())->flatten()->first()]);
        }

        $signIn->login($request, $user);

        return redirect()->intended(route('home'));
    }
}
__AUTH_EOF__
echo "  + app/Http/Controllers/Auth/TelegramWidgetController.php"

mkdir -p "app/Mail"
cat > "app/Mail/LoginCodeMail.php" <<'__AUTH_EOF__'
<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/** Kirish kodi emaili. Ataylab navbatga qo'yilmaydi: foydalanuvchi darhol olishi kerak (queue worker shart emas). */
class LoginCodeMail extends Mailable
{
    public function __construct(public readonly string $code, public readonly int $minutes) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Your '.config('app.name').' sign-in code');
    }

    public function content(): Content
    {
        return new Content(markdown: 'mail.login-code');
    }
}
__AUTH_EOF__
echo "  + app/Mail/LoginCodeMail.php"

mkdir -p "app/Models"
cat > "app/Models/LoginCode.php" <<'__AUTH_EOF__'
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\MassPrunable;
use Illuminate\Database\Eloquent\Model;

/** Email orqali kirish uchun bir martalik kod (faqat hash saqlanadi). attempts va used_at servis orqali o'zgaradi. */
class LoginCode extends Model
{
    use MassPrunable;

    protected $fillable = ['email', 'code_hash', 'expires_at', 'ip_address'];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'used_at' => 'datetime',
        ];
    }

    /** Bir kundan eski yozuvlar `php artisan model:prune --model="App\Models\LoginCode"` bilan tozalanadi. */
    public function prunable(): Builder
    {
        return static::query()->where('created_at', '<', now()->subDay());
    }
}
__AUTH_EOF__
echo "  + app/Models/LoginCode.php"

mkdir -p "app/Models"
cat > "app/Models/SocialAccount.php" <<'__AUTH_EOF__'
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Foydalanuvchining tashqi provayderdagi (Google, GitHub) hisobi. Tokenlar saqlanmaydi. */
class SocialAccount extends Model
{
    protected $fillable = ['user_id', 'provider', 'provider_user_id', 'email', 'name'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
__AUTH_EOF__
echo "  + app/Models/SocialAccount.php"

mkdir -p "app/Services"
cat > "app/Services/EmailSignInService.php" <<'__AUTH_EOF__'
<?php

namespace App\Services;

use App\Models\User;
use App\Support\PasswordlessGuard;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/** Email egaligi kod bilan tasdiqlangach, unga mos hisobni topadi yoki yaratadi. */
class EmailSignInService
{
    /** @throws ValidationException hisob parolsiz kirish uchun yaramasa */
    public function userFor(string $email): User
    {
        $email = LoginCodeService::normalize($email);
        $user = User::query()->where('email', $email)->first();

        if ($user === null) {
            return $this->register($email);
        }

        PasswordlessGuard::ensureAllowed($user);

        // Parol bilan ro'yxatdan o'tgan va emaili tasdiqlanmagan hisobga kod bilan kirilmaydi:
        // kimdir boshqaning emailini oldindan band qilib qo'ygan bo'lishi mumkin.
        if ($user->email_verified_at === null) {
            throw ValidationException::withMessages([
                'auth' => 'This email is registered with a password and has not been verified yet. Please sign in with your password.',
            ]);
        }

        return $user;
    }

    private function register(string $email): User
    {
        $name = Str::squish(Str::title(str_replace(['.', '_', '-', '+'], ' ', Str::before($email, '@'))));

        $user = new User([
            'name' => Str::limit($name !== '' ? $name : 'Customer', 120, ''),
            'email' => $email,
            'password' => Str::random(48),
        ]);

        try {
            // Kod emailga yetib kelganini isbotlaydi, shuning uchun email darhol tasdiqlangan hisoblanadi.
            $user->forceFill(['email_verified_at' => now()])->save();
        } catch (UniqueConstraintViolationException) {
            return User::query()->where('email', $email)->firstOrFail();
        }

        return $user;
    }
}
__AUTH_EOF__
echo "  + app/Services/EmailSignInService.php"

mkdir -p "app/Services"
cat > "app/Services/LoginCodeService.php" <<'__AUTH_EOF__'
<?php

namespace App\Services;

use App\Mail\LoginCodeMail;
use App\Models\LoginCode;
use App\Support\OneTimeCode;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

/** Emailga bir martalik kod yuboradi va tekshiradi. Kod bazada faqat hash ko'rinishida turadi. */
class LoginCodeService
{
    public const TTL_MINUTES = 10;

    /** Bitta kod uchun noto'g'ri urinishlar limiti. */
    public const MAX_ATTEMPTS = 5;

    /** Bir emailga ketma-ket kod yuborish orasidagi pauza. */
    public const RESEND_SECONDS = 60;

    /** Bir emailga soatiga ko'pi bilan nechta kod. */
    public const MAX_PER_HOUR = 5;

    public static function normalize(string $email): string
    {
        return Str::lower(trim($email));
    }

    /** @throws ValidationException pauza yoki soatlik limit buzilsa, yoki xat jo'natib bo'lmasa */
    public function issue(string $email, ?string $ip = null): void
    {
        $email = self::normalize($email);

        $latest = LoginCode::query()->where('email', $email)->latest('id')->first();

        if ($latest !== null) {
            $wait = $latest->created_at->getTimestamp() + self::RESEND_SECONDS - now()->getTimestamp();

            if ($wait > 0) {
                throw ValidationException::withMessages(['email' => "Please wait {$wait} seconds before requesting another code."]);
            }
        }

        $lastHour = LoginCode::query()->where('email', $email)->where('created_at', '>', now()->subHour())->count();

        if ($lastHour >= self::MAX_PER_HOUR) {
            throw ValidationException::withMessages(['email' => 'Too many codes requested for this email. Please try again later.']);
        }

        $code = OneTimeCode::generate();

        $row = DB::transaction(function () use ($email, $code, $ip): LoginCode {
            // Yangi kod eskisini bekor qiladi: bir vaqtda faqat bitta kod ishlaydi.
            LoginCode::query()->where('email', $email)->whereNull('used_at')->update(['used_at' => now()]);

            return LoginCode::query()->create([
                'email' => $email,
                'code_hash' => OneTimeCode::hash($email, $code, (string) config('app.key')),
                'expires_at' => now()->addMinutes(self::TTL_MINUTES),
                'ip_address' => $ip,
            ]);
        });

        try {
            Mail::to($email)->send(new LoginCodeMail($code, self::TTL_MINUTES));
        } catch (Throwable $e) {
            report($e);
            $row->delete();

            throw ValidationException::withMessages(['email' => 'We could not send the email right now. Please try again in a minute.']);
        }
    }

    /** To'g'ri bo'lsa kodni sarflaydi (qayta ishlamaydi) va true qaytaradi. */
    public function verify(string $email, string $code): bool
    {
        $email = self::normalize($email);
        $code = preg_replace('/\D+/', '', $code) ?? '';

        if (strlen($code) !== OneTimeCode::LENGTH) {
            return false;
        }

        $row = LoginCode::query()
            ->where('email', $email)
            ->whereNull('used_at')
            ->where('expires_at', '>', now())
            ->latest('id')
            ->first();

        if ($row === null) {
            return false;
        }

        // Urinish atomik hisoblanadi: parallel so'rovlar limitni aylanib o'tolmasin.
        $counted = LoginCode::query()
            ->whereKey($row->id)
            ->where('attempts', '<', self::MAX_ATTEMPTS)
            ->increment('attempts');

        if ($counted === 0) {
            return false;
        }

        if (! hash_equals($row->code_hash, OneTimeCode::hash($email, $code, (string) config('app.key')))) {
            return false;
        }

        // Kod faqat bir marta ishlaydi (parallel ikki so'rovdan faqat bittasi yutadi).
        return LoginCode::query()->whereKey($row->id)->whereNull('used_at')->update(['used_at' => now()]) === 1;
    }
}
__AUTH_EOF__
echo "  + app/Services/LoginCodeService.php"

mkdir -p "app/Services"
cat > "app/Services/SocialAuthService.php" <<'__AUTH_EOF__'
<?php

namespace App\Services;

use App\Models\SocialAccount;
use App\Models\User;
use App\Support\PasswordlessGuard;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Laravel\Socialite\Contracts\User as SocialiteUser;

/** Google / GitHub (Laravel Socialite) orqali kelgan shaxsga mos hisobni topadi, bog'laydi yoki yaratadi. */
class SocialAuthService
{
    /** provayder kaliti (config/services.php) => ekrandagi nom */
    public const PROVIDERS = [
        'google' => 'Google',
        'github' => 'GitHub',
    ];

    /**
     * Faqat client_id va client_secret berilgan provayderlar ko'rsatiladi.
     *
     * @return array<string, string>
     */
    public function enabled(): array
    {
        return array_filter(
            self::PROVIDERS,
            fn (string $label, string $provider): bool => filled(config("services.{$provider}.client_id"))
                && filled(config("services.{$provider}.client_secret")),
            ARRAY_FILTER_USE_BOTH,
        );
    }

    public function isEnabled(string $provider): bool
    {
        return array_key_exists($provider, $this->enabled());
    }

    public function label(string $provider): string
    {
        return self::PROVIDERS[$provider] ?? ucfirst($provider);
    }

    /** @throws ValidationException hisob kirish uchun yaramasa */
    public function resolveUser(string $provider, SocialiteUser $social): User
    {
        $providerUserId = (string) $social->getId();

        if ($providerUserId === '') {
            throw ValidationException::withMessages(['auth' => "{$this->label($provider)} did not return an account id."]);
        }

        $account = SocialAccount::query()
            ->with('user')
            ->where('provider', $provider)
            ->where('provider_user_id', $providerUserId)
            ->first();

        if ($account !== null) {
            PasswordlessGuard::ensureAllowed($account->user);

            return $account->user;
        }

        $email = $this->verifiedEmail($provider, $social);
        $existing = $email !== null ? User::query()->where('email', $email)->first() : null;

        if ($existing !== null) {
            return $this->linkToExisting($existing, $provider, $providerUserId, $social, $email);
        }

        return $this->register($provider, $providerUserId, $social, $email);
    }

    private function linkToExisting(User $user, string $provider, string $providerUserId, SocialiteUser $social, string $email): User
    {
        PasswordlessGuard::ensureAllowed($user);

        // Faqat tasdiqlangan emailli hisobga bog'lanadi: tasdiqlanmagan hisob boshqa odam tomonidan oldindan ochilgan bo'lishi mumkin.
        if ($user->email_verified_at === null) {
            throw ValidationException::withMessages([
                'auth' => 'An account with this email already exists and has not been verified. Please sign in with your password.',
            ]);
        }

        if ($user->socialAccounts()->where('provider', $provider)->exists()) {
            throw ValidationException::withMessages([
                'auth' => "This account is already connected to a different {$this->label($provider)} account.",
            ]);
        }

        $this->createAccount($user, $provider, $providerUserId, $social, $email);

        return $user;
    }

    private function register(string $provider, string $providerUserId, SocialiteUser $social, ?string $email): User
    {
        $name = trim((string) ($social->getName() ?: $social->getNickname()));

        if ($name === '') {
            $name = $email !== null ? Str::before($email, '@') : $this->label($provider).' user';
        }

        try {
            return DB::transaction(function () use ($provider, $providerUserId, $social, $email, $name): User {
                $user = new User([
                    'name' => Str::limit($name, 120, ''),
                    'email' => $email,
                    'password' => Str::random(48),
                ]);

                // Provayder emailni tasdiqlagan bo'lsa (verifiedEmail), u bizda ham tasdiqlangan hisoblanadi.
                $user->forceFill(['email_verified_at' => $email !== null ? now() : null])->save();

                $this->createAccount($user, $provider, $providerUserId, $social, $email);

                return $user;
            });
        } catch (UniqueConstraintViolationException) {
            // Parallel so'rov xuddi shu hisobni allaqachon yaratgan bo'lishi mumkin.
            $account = SocialAccount::query()
                ->with('user')
                ->where('provider', $provider)
                ->where('provider_user_id', $providerUserId)
                ->first();

            if ($account === null) {
                throw ValidationException::withMessages(['auth' => 'Could not create your account. Please try again.']);
            }

            PasswordlessGuard::ensureAllowed($account->user);

            return $account->user;
        }
    }

    private function createAccount(User $user, string $provider, string $providerUserId, SocialiteUser $social, ?string $email): void
    {
        SocialAccount::query()->create([
            'user_id' => $user->id,
            'provider' => $provider,
            'provider_user_id' => $providerUserId,
            'email' => $email ?? (filter_var($social->getEmail(), FILTER_VALIDATE_EMAIL) ? Str::lower((string) $social->getEmail()) : null),
            'name' => $social->getName() ?: $social->getNickname(),
        ]);
    }

    /**
     * Provayder tasdiqlagan email (kichik harflarda) yoki null.
     * Tasdiqlanmagan emailga ishonilmaydi: u orqali boshqa odamning hisobiga kirib bo'lmasligi kerak.
     */
    private function verifiedEmail(string $provider, SocialiteUser $social): ?string
    {
        $email = Str::lower(trim((string) $social->getEmail()));

        if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return null;
        }

        $raw = property_exists($social, 'user') && is_array($social->user) ? $social->user : [];

        return match ($provider) {
            // Google userinfo javobidagi email_verified bayrog'i.
            'google' => filter_var($raw['email_verified'] ?? false, FILTER_VALIDATE_BOOLEAN) ? $email : null,
            // GitHub drayveri (user:email scope) faqat asosiy VA tasdiqlangan manzilni qaytaradi.
            'github' => $email,
            default => null,
        };
    }
}
__AUTH_EOF__
echo "  + app/Services/SocialAuthService.php"

mkdir -p "app/Services"
cat > "app/Services/WebSignIn.php" <<'__AUTH_EOF__'
<?php

namespace App\Services;

use App\Models\User;
use App\Support\Visitor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Sayt (web) sessiyasini ochadi va mehmon savati, sevimlilari hamda support chatini hisobga ko'chiradi.
 * AuthController::login bilan bir xil tartib: mehmon tokeni sessiya yangilanishidan OLDIN olinadi.
 */
class WebSignIn
{
    public function __construct(
        private CartService $cart,
        private WishlistService $wishlist,
        private SupportChatService $support,
    ) {}

    public function login(Request $request, User $user, bool $remember = false): void
    {
        $token = Visitor::peek();

        Auth::login($user, $remember);
        $request->session()->regenerate();

        $this->support->adoptGuest($user);
        $this->cart->mergeGuestCart($user, $token);
        $this->wishlist->mergeGuestList($user, $token);
        Visitor::forget();
    }
}
__AUTH_EOF__
echo "  + app/Services/WebSignIn.php"

mkdir -p "app/Support"
cat > "app/Support/OneTimeCode.php" <<'__AUTH_EOF__'
<?php

namespace App\Support;

/** Emailga yuboriladigan bir martalik raqamli kod. */
final class OneTimeCode
{
    public const LENGTH = 6;

    public static function generate(): string
    {
        return str_pad((string) random_int(0, (10 ** self::LENGTH) - 1), self::LENGTH, '0', STR_PAD_LEFT);
    }

    /** Kod bazada ochiq saqlanmaydi: email va kod berilgan kalit (APP_KEY) bilan HMAC qilinadi. */
    public static function hash(string $email, string $code, string $key): string
    {
        return hash_hmac('sha256', $email.'|'.$code, $key);
    }
}
__AUTH_EOF__
echo "  + app/Support/OneTimeCode.php"

mkdir -p "app/Support"
cat > "app/Support/PasswordlessGuard.php" <<'__AUTH_EOF__'
<?php

namespace App\Support;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Validation\ValidationException;

/**
 * Parolsiz kirish (email kod, Google, GitHub) faqat faol mijoz hisobiga ruxsat beradi.
 * Xodim va hamkor panellari faqat parol bilan ochiladi (Telegram'dagi qoida bilan bir xil).
 */
final class PasswordlessGuard
{
    public static function ensureAllowed(User $user): void
    {
        if (! $user->is_active) {
            throw ValidationException::withMessages(['auth' => 'This account is disabled.']);
        }

        if (! $user->hasRole(UserRole::Customer)) {
            throw ValidationException::withMessages(['auth' => 'Staff and partner accounts must sign in with email and password.']);
        }
    }
}
__AUTH_EOF__
echo "  + app/Support/PasswordlessGuard.php"

mkdir -p "app/Support"
cat > "app/Support/TelegramLoginWidget.php" <<'__AUTH_EOF__'
<?php

namespace App\Support;

/**
 * Telegram Login Widget (sayt uchun) imzosi.
 * https://core.telegram.org/widgets/login-legacy (Checking authorization)
 *
 * Mini App initData'dan farqi: maxfiy kalit HMAC("WebAppData") emas, to'g'ridan-to'g'ri SHA256(bot token).
 */
final class TelegramLoginWidget
{
    /** @param  array<string, string>  $data  `hash` dan tashqari barcha qabul qilingan maydonlar */
    public static function signature(array $data, string $botToken): string
    {
        unset($data['hash']);
        ksort($data, SORT_STRING);

        $lines = [];

        foreach ($data as $key => $value) {
            $lines[] = $key.'='.$value;
        }

        return hash_hmac('sha256', implode("\n", $lines), hash('sha256', $botToken, true));
    }

    /** @param  array<string, mixed>  $data  so'rov (query) maydonlari, `hash` bilan birga */
    public static function isValid(array $data, string $botToken): bool
    {
        $hash = $data['hash'] ?? null;

        if (! is_string($hash) || $hash === '' || $botToken === '') {
            return false;
        }

        // Telegram faqat oddiy matn maydonlarini yuboradi: massiv yoki boshqa tur kelsa, so'rov soxta.
        foreach ($data as $value) {
            if (! is_string($value)) {
                return false;
            }
        }

        return hash_equals(self::signature($data, $botToken), strtolower($hash));
    }
}
__AUTH_EOF__
echo "  + app/Support/TelegramLoginWidget.php"

mkdir -p "database/migrations"
cat > "database/migrations/2026_12_04_100000_make_users_email_nullable.php" <<'__AUTH_EOF__'
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Telegram orqali kirgan foydalanuvchining emaili bo'lmasligi mumkin. UNIQUE indeks saqlanadi (bir nechta NULL ruxsat).
        Schema::table('users', function (Blueprint $table) {
            $table->string('email')->nullable()->change();
        });

        // Avval Telegram uchun yaratilgan soxta manzillar (tg123.abc@telegram.invalid) endi kerak emas.
        DB::table('users')->where('email', 'like', '%@telegram.invalid')->update(['email' => null]);
    }

    public function down(): void
    {
        // NOT NULL ga qaytishdan oldin bo'sh emaillar to'ldiriladi.
        foreach (DB::table('users')->whereNull('email')->pluck('id') as $id) {
            DB::table('users')->where('id', $id)->update(['email' => "user{$id}@placeholder.invalid"]);
        }

        Schema::table('users', function (Blueprint $table) {
            $table->string('email')->nullable(false)->change();
        });
    }
};
__AUTH_EOF__
echo "  + database/migrations/2026_12_04_100000_make_users_email_nullable.php"

mkdir -p "database/migrations"
cat > "database/migrations/2026_12_04_100001_create_social_accounts_table.php" <<'__AUTH_EOF__'
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('social_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('provider', 32);
            $table->string('provider_user_id', 191);
            $table->string('email')->nullable();
            $table->string('name')->nullable();
            $table->timestamps();

            // Bitta tashqi hisob faqat bitta foydalanuvchiga, bitta foydalanuvchida har provayderdan bittadan.
            $table->unique(['provider', 'provider_user_id']);
            $table->unique(['user_id', 'provider']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('social_accounts');
    }
};
__AUTH_EOF__
echo "  + database/migrations/2026_12_04_100001_create_social_accounts_table.php"

mkdir -p "database/migrations"
cat > "database/migrations/2026_12_04_100002_create_login_codes_table.php" <<'__AUTH_EOF__'
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('login_codes', function (Blueprint $table) {
            $table->id();
            $table->string('email');
            $table->string('code_hash', 64);
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->timestamp('expires_at');
            $table->timestamp('used_at')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();

            $table->index(['email', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('login_codes');
    }
};
__AUTH_EOF__
echo "  + database/migrations/2026_12_04_100002_create_login_codes_table.php"

mkdir -p "resources/views/mail"
cat > "resources/views/mail/login-code.blade.php" <<'__AUTH_EOF__'
<x-mail::message>
# Your sign-in code

Use this code to sign in to {{ config('app.name') }}:

<x-mail::panel>
{{ $code }}
</x-mail::panel>

The code works once and expires in {{ $minutes }} minutes. If you did not request it, you can safely ignore this email.

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
__AUTH_EOF__
echo "  + resources/views/mail/login-code.blade.php"

mkdir -p "resources/views/pages/auth"
cat > "resources/views/pages/auth/code-request.blade.php" <<'__AUTH_EOF__'
@extends('layouts.app')

@section('title', 'Sign in with email code - FoodMart')

@section('content')
<div class="mx-auto max-w-md py-10">
    <div class="dark-glass-card rounded-3xl p-8">
        <h1 class="font-display font-extrabold text-3xl text-text-crisp">Email code</h1>
        <p class="mt-1 text-sm text-text-muted">We will email you a 6-digit code. No password needed, and a new account is created automatically.</p>

        <form method="POST" action="{{ route('login.code.store') }}" class="mt-8 space-y-5">
            @csrf
            <x-field name="email" label="Email" type="email" autocomplete="email" required />

            <button type="submit" class="water-ripple-target w-full rounded-full bg-primary hover:bg-emerald-400 py-3 font-display text-xs font-bold uppercase tracking-wider text-black transition-colors">Send code</button>
        </form>

        <p class="mt-6 text-center text-sm text-text-muted">
            Prefer a password? <a href="{{ route('login') }}" class="font-bold text-primary hover:underline">Sign in</a>
        </p>
    </div>
</div>
@endsection
__AUTH_EOF__
echo "  + resources/views/pages/auth/code-request.blade.php"

mkdir -p "resources/views/pages/auth"
cat > "resources/views/pages/auth/code-verify.blade.php" <<'__AUTH_EOF__'
@extends('layouts.app')

@section('title', 'Enter your code - FoodMart')

@section('content')
<div class="mx-auto max-w-md py-10">
    <div class="dark-glass-card rounded-3xl p-8">
        <h1 class="font-display font-extrabold text-3xl text-text-crisp">Enter your code</h1>
        <p class="mt-1 text-sm text-text-muted">We sent a 6-digit code to <span class="font-bold text-text-crisp">{{ $email }}</span>. It expires in {{ \App\Services\LoginCodeService::TTL_MINUTES }} minutes.</p>

        @if ($errors->has('auth') || $errors->has('email'))
            <p class="mt-4 rounded-xl border border-red-400/40 bg-red-400/10 px-4 py-3 text-sm text-red-300">{{ $errors->first('auth') ?: $errors->first('email') }}</p>
        @endif

        <form method="POST" action="{{ route('login.code.confirm') }}" class="mt-8 space-y-5">
            @csrf
            <x-field name="code" label="6-digit code" autocomplete="one-time-code" inputmode="numeric" maxlength="12" pattern="[0-9 ]*" required />

            <button type="submit" class="water-ripple-target w-full rounded-full bg-primary hover:bg-emerald-400 py-3 font-display text-xs font-bold uppercase tracking-wider text-black transition-colors">Sign in</button>
        </form>

        <form method="POST" action="{{ route('login.code.store') }}" class="mt-5 text-center">
            @csrf
            <input type="hidden" name="email" value="{{ $email }}">
            <button type="submit" class="text-sm font-bold text-primary hover:underline">Send a new code</button>
        </form>

        <p class="mt-4 text-center text-sm text-text-muted">
            Wrong email? <a href="{{ route('login.code') }}" class="font-bold text-primary hover:underline">Start over</a>
        </p>
    </div>
</div>
@endsection
__AUTH_EOF__
echo "  + resources/views/pages/auth/code-verify.blade.php"

mkdir -p "resources/views/pages/auth/partials"
cat > "resources/views/pages/auth/partials/alternatives.blade.php" <<'__AUTH_EOF__'
@php
    $socialProviders = app(\App\Services\SocialAuthService::class)->enabled();
    $telegramBot = filled(config('telegram.bot_token')) && filled(config('telegram.bot_username'))
        ? ltrim((string) config('telegram.bot_username'), '@')
        : null;
    $altButton = 'flex w-full items-center justify-center rounded-full border border-border-glass hover:border-primary/60 py-3 font-display text-xs font-bold uppercase tracking-wider text-text-crisp transition-colors';
@endphp

<div class="mt-8 flex items-center gap-3 text-[10px] font-mono font-bold uppercase tracking-widest text-text-muted">
    <span class="h-px flex-1 bg-border-glass"></span>
    <span>or continue with</span>
    <span class="h-px flex-1 bg-border-glass"></span>
</div>

<div class="mt-5 space-y-3">
    <a href="{{ route('login.code') }}" class="{{ $altButton }}">Email code</a>

    @foreach ($socialProviders as $provider => $label)
        <a href="{{ route('auth.social.redirect', $provider) }}" class="{{ $altButton }}">{{ $label }}</a>
    @endforeach

    @if ($telegramBot)
        <div class="flex justify-center pt-1">
            <script async src="https://telegram.org/js/telegram-widget.js?22" data-telegram-login="{{ $telegramBot }}" data-size="large" data-radius="999" data-auth-url="{{ route('auth.telegram.callback') }}"></script>
        </div>
    @endif
</div>
__AUTH_EOF__
echo "  + resources/views/pages/auth/partials/alternatives.blade.php"

mkdir -p "routes"
cat > "routes/passwordless.php" <<'__AUTH_EOF__'
<?php

use App\Http\Controllers\Auth\EmailCodeController;
use App\Http\Controllers\Auth\SocialAuthController;
use App\Http\Controllers\Auth\TelegramWidgetController;
use App\Services\SocialAuthService;
use Illuminate\Support\Facades\Route;

// Parolsiz kirish usullari: email kod, Google/GitHub, Telegram. web.php oxirida ulanadi.
Route::middleware('guest')->group(function () {
    Route::get('/login/code', [EmailCodeController::class, 'create'])->name('login.code');
    Route::post('/login/code', [EmailCodeController::class, 'store'])->middleware('throttle:6,1,login-code-send')->name('login.code.store');
    Route::get('/login/code/verify', [EmailCodeController::class, 'verifyForm'])->name('login.code.verify');
    Route::post('/login/code/verify', [EmailCodeController::class, 'verify'])->middleware('throttle:10,1,login-code-verify')->name('login.code.confirm');

    Route::get('/auth/telegram/callback', [TelegramWidgetController::class, 'callback'])->middleware('throttle:20,1,telegram-widget')->name('auth.telegram.callback');

    Route::get('/auth/{provider}/redirect', [SocialAuthController::class, 'redirect'])
        ->whereIn('provider', array_keys(SocialAuthService::PROVIDERS))
        ->middleware('throttle:20,1,social-redirect')
        ->name('auth.social.redirect');

    Route::get('/auth/{provider}/callback', [SocialAuthController::class, 'callback'])
        ->whereIn('provider', array_keys(SocialAuthService::PROVIDERS))
        ->middleware('throttle:20,1,social-callback')
        ->name('auth.social.callback');
});
__AUTH_EOF__
echo "  + routes/passwordless.php"

mkdir -p "tests/Feature"
cat > "tests/Feature/EmailCodeAuthTest.php" <<'__AUTH_EOF__'
<?php

namespace Tests\Feature;

use App\Mail\LoginCodeMail;
use App\Models\LoginCode;
use App\Models\User;
use App\Services\LoginCodeService;
use App\Support\OneTimeCode;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class EmailCodeAuthTest extends TestCase
{
    use RefreshDatabase;

    /** Kodni so'raydi va emailga yuborilgan ochiq kodni qaytaradi. */
    private function requestCode(string $email = 'new@example.test'): string
    {
        Mail::fake();

        $this->post('/login/code', ['email' => $email])->assertRedirect(route('login.code.verify'));

        $code = null;
        Mail::assertSent(LoginCodeMail::class, function (LoginCodeMail $mail) use (&$code, $email): bool {
            $code = $mail->code;

            return $mail->hasTo($email);
        });

        return (string) $code;
    }

    private function wrongCode(string $real): string
    {
        return $real === '000000' ? '111111' : '000000';
    }

    public function test_pages_load_for_guests(): void
    {
        $this->get('/login/code')->assertOk();
        $this->get('/login')->assertOk()->assertSee(route('login.code'), false);
    }

    public function test_verify_page_without_a_requested_email_redirects(): void
    {
        $this->get('/login/code/verify')->assertRedirect(route('login.code'));
    }

    public function test_signed_in_users_cannot_open_the_code_pages(): void
    {
        $this->actingAs(User::factory()->create())->get('/login/code')->assertRedirect();
    }

    public function test_only_a_hash_of_the_code_is_stored(): void
    {
        $code = $this->requestCode();

        $row = LoginCode::query()->firstOrFail();
        $this->assertNotSame($code, $row->code_hash);
        $this->assertSame(OneTimeCode::hash('new@example.test', $code, (string) config('app.key')), $row->code_hash);
        $this->assertTrue($row->expires_at->isFuture());
    }

    public function test_new_email_registers_a_verified_customer_and_signs_in(): void
    {
        $code = $this->requestCode('Fresh.User@Example.test');

        $this->post('/login/code/verify', ['code' => $code])->assertRedirect('/');

        $user = User::query()->where('email', 'fresh.user@example.test')->firstOrFail();
        $this->assertAuthenticatedAs($user);
        $this->assertNotNull($user->email_verified_at);
        $this->assertNull($user->telegram_id);
        $this->assertSame('Fresh User', $user->name);
    }

    public function test_existing_verified_customer_signs_in(): void
    {
        $user = User::factory()->create(['email' => 'login@example.test']);
        $code = $this->requestCode('login@example.test');

        $this->post('/login/code/verify', ['code' => $code])->assertRedirect('/');

        $this->assertAuthenticatedAs($user);
        $this->assertSame(1, User::query()->count());
    }

    public function test_code_with_spaces_is_accepted(): void
    {
        $code = $this->requestCode();

        $this->post('/login/code/verify', ['code' => substr($code, 0, 3).' '.substr($code, 3)])->assertRedirect('/');
        $this->assertAuthenticated();
    }

    public function test_unverified_password_account_is_refused(): void
    {
        User::factory()->unverified()->create(['email' => 'squat@example.test']);
        $code = $this->requestCode('squat@example.test');

        $this->post('/login/code/verify', ['code' => $code])->assertSessionHasErrors('auth');
        $this->assertGuest();
    }

    public function test_staff_account_is_refused(): void
    {
        User::factory()->admin()->create(['email' => 'boss@example.test']);
        $code = $this->requestCode('boss@example.test');

        $this->post('/login/code/verify', ['code' => $code])->assertSessionHasErrors('auth');
        $this->assertGuest();
    }

    public function test_wrong_code_is_rejected_and_counted(): void
    {
        $code = $this->requestCode();

        $this->post('/login/code/verify', ['code' => $this->wrongCode($code)])->assertSessionHasErrors('code');

        $this->assertGuest();
        $this->assertSame(1, (int) LoginCode::query()->firstOrFail()->attempts);
    }

    public function test_the_code_is_locked_after_too_many_wrong_attempts(): void
    {
        $code = $this->requestCode();

        for ($i = 0; $i < LoginCodeService::MAX_ATTEMPTS; $i++) {
            $this->post('/login/code/verify', ['code' => $this->wrongCode($code)]);
        }

        $this->post('/login/code/verify', ['code' => $code])->assertSessionHasErrors('code');
        $this->assertGuest();
    }

    public function test_expired_code_is_rejected(): void
    {
        $code = $this->requestCode();

        $this->travel(LoginCodeService::TTL_MINUTES + 1)->minutes();

        $this->post('/login/code/verify', ['code' => $code])->assertSessionHasErrors('code');
        $this->assertGuest();
    }

    public function test_a_code_works_only_once(): void
    {
        Mail::fake();
        $service = app(LoginCodeService::class);
        $service->issue('once@example.test');

        $code = null;
        Mail::assertSent(LoginCodeMail::class, function (LoginCodeMail $mail) use (&$code): bool {
            $code = $mail->code;

            return true;
        });

        $this->assertTrue($service->verify('once@example.test', $code));
        $this->assertFalse($service->verify('once@example.test', $code));
    }

    public function test_requesting_again_too_fast_is_blocked(): void
    {
        $this->requestCode();

        $this->post('/login/code', ['email' => 'new@example.test'])->assertSessionHasErrors('email');

        Mail::assertSent(LoginCodeMail::class, 1);
    }

    public function test_a_new_code_replaces_the_previous_one(): void
    {
        Mail::fake();
        $service = app(LoginCodeService::class);

        $service->issue('swap@example.test');
        $first = null;
        Mail::assertSent(LoginCodeMail::class, function (LoginCodeMail $mail) use (&$first): bool {
            $first = $mail->code;

            return true;
        });

        $this->travel(LoginCodeService::RESEND_SECONDS + 1)->seconds();
        Mail::fake();
        $service->issue('swap@example.test');
        $second = null;
        Mail::assertSent(LoginCodeMail::class, function (LoginCodeMail $mail) use (&$second): bool {
            $second = $mail->code;

            return true;
        });

        if ($first !== $second) {
            $this->assertFalse($service->verify('swap@example.test', $first));
        }
        $this->assertTrue($service->verify('swap@example.test', $second));
    }

    public function test_hourly_limit_per_email(): void
    {
        Mail::fake();
        $service = app(LoginCodeService::class);

        for ($i = 0; $i < LoginCodeService::MAX_PER_HOUR; $i++) {
            $service->issue('busy@example.test');
            $this->travel(LoginCodeService::RESEND_SECONDS + 1)->seconds();
        }

        $this->expectException(\Illuminate\Validation\ValidationException::class);
        $service->issue('busy@example.test');
    }
}
__AUTH_EOF__
echo "  + tests/Feature/EmailCodeAuthTest.php"

mkdir -p "tests/Feature"
cat > "tests/Feature/ProfileEmailTest.php" <<'__AUTH_EOF__'
<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileEmailTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_user_without_email_can_update_the_profile(): void
    {
        $user = User::factory()->create(['email' => null]);

        $this->actingAs($user)
            ->put('/profile', ['name' => 'Telegram Person', 'email' => '', 'phone' => '+998 90 111 22 33'])
            ->assertSessionHasNoErrors();

        $user->refresh();
        $this->assertSame('Telegram Person', $user->name);
        $this->assertNull($user->email);
    }

    public function test_a_user_with_an_email_cannot_clear_it(): void
    {
        $user = User::factory()->create(['email' => 'keep@example.test']);

        $this->actingAs($user)
            ->put('/profile', ['name' => $user->name, 'email' => ''])
            ->assertSessionHasErrors('email');
    }

    public function test_changing_the_email_resets_verification(): void
    {
        $user = User::factory()->create(['email' => 'old@example.test']);

        $this->actingAs($user)->put('/profile', ['name' => $user->name, 'email' => 'new@example.test'])->assertSessionHasNoErrors();

        $user->refresh();
        $this->assertSame('new@example.test', $user->email);
        $this->assertNull($user->email_verified_at);
    }

    public function test_keeping_the_same_email_keeps_verification(): void
    {
        $user = User::factory()->create(['email' => 'same@example.test']);

        $this->actingAs($user)->put('/profile', ['name' => 'Renamed', 'email' => 'same@example.test'])->assertSessionHasNoErrors();

        $this->assertNotNull($user->refresh()->email_verified_at);
    }
}
__AUTH_EOF__
echo "  + tests/Feature/ProfileEmailTest.php"

mkdir -p "tests/Feature"
cat > "tests/Feature/SocialAuthTest.php" <<'__AUTH_EOF__'
<?php

namespace Tests\Feature;

use App\Models\SocialAccount;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\RedirectResponse;
use Laravel\Socialite\Contracts\Factory;
use Laravel\Socialite\Contracts\Provider;
use Laravel\Socialite\Two\User as SocialiteUser;
use Mockery;
use Tests\TestCase;

class SocialAuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.github.client_id' => 'gh-id',
            'services.github.client_secret' => 'gh-secret',
            'services.google.client_id' => 'g-id',
            'services.google.client_secret' => 'g-secret',
        ]);
    }

    /** @param  callable(): SocialiteUser  $resolve */
    private function fakeProvider(string $provider, callable $resolve): void
    {
        $driver = Mockery::mock(Provider::class);
        $driver->shouldReceive('redirect')->andReturn(new RedirectResponse('https://provider.test/authorize'));
        $driver->shouldReceive('user')->andReturnUsing($resolve);

        $this->mock(Factory::class, function ($mock) use ($provider, $driver): void {
            $mock->shouldReceive('driver')->with($provider)->andReturn($driver);
        });
    }

    private function socialUser(string $id, ?string $email, array $raw = []): SocialiteUser
    {
        return (new SocialiteUser)
            ->setRaw($raw + ['email' => $email])
            ->map(['id' => $id, 'nickname' => 'nick', 'name' => 'Social Person', 'email' => $email, 'avatar' => null]);
    }

    public function test_redirect_goes_to_the_provider(): void
    {
        $this->fakeProvider('github', fn () => $this->socialUser('1', null));

        $this->get('/auth/github/redirect')->assertRedirect('https://provider.test/authorize');
    }

    public function test_provider_without_credentials_is_not_found(): void
    {
        config(['services.google.client_secret' => null]);

        $this->get('/auth/google/redirect')->assertNotFound();
        $this->get('/auth/google/callback')->assertNotFound();
    }

    public function test_unknown_provider_is_not_found(): void
    {
        $this->get('/auth/facebook/redirect')->assertNotFound();
    }

    public function test_login_page_lists_only_configured_providers(): void
    {
        config(['services.google.client_id' => null]);

        $this->get('/login')
            ->assertSee(route('auth.social.redirect', 'github'), false)
            ->assertDontSee(route('auth.social.redirect', 'google'), false);
    }

    public function test_new_visitor_gets_an_account_and_is_signed_in(): void
    {
        $this->fakeProvider('github', fn () => $this->socialUser('gh-1', 'Dev@Example.test'));

        $this->get('/auth/github/callback')->assertRedirect('/');

        $user = User::query()->where('email', 'dev@example.test')->firstOrFail();
        $this->assertAuthenticatedAs($user);
        $this->assertNotNull($user->email_verified_at);
        $this->assertDatabaseHas('social_accounts', ['user_id' => $user->id, 'provider' => 'github', 'provider_user_id' => 'gh-1']);
    }

    public function test_a_returning_visitor_reuses_the_same_account(): void
    {
        $this->fakeProvider('github', fn () => $this->socialUser('gh-1', 'dev@example.test'));

        $this->get('/auth/github/callback');
        $this->post('/logout');
        $this->get('/auth/github/callback')->assertRedirect('/');

        $this->assertAuthenticated();
        $this->assertSame(1, User::query()->count());
        $this->assertSame(1, SocialAccount::query()->count());
    }

    public function test_it_links_to_an_existing_customer_with_a_verified_email(): void
    {
        $existing = User::factory()->create(['email' => 'owner@example.test']);
        $this->fakeProvider('github', fn () => $this->socialUser('gh-9', 'owner@example.test'));

        $this->get('/auth/github/callback')->assertRedirect('/');

        $this->assertAuthenticatedAs($existing);
        $this->assertSame(1, User::query()->count());
        $this->assertDatabaseHas('social_accounts', ['user_id' => $existing->id, 'provider_user_id' => 'gh-9']);
    }

    public function test_it_never_links_to_an_unverified_account(): void
    {
        User::factory()->unverified()->create(['email' => 'squat@example.test']);
        $this->fakeProvider('github', fn () => $this->socialUser('gh-2', 'squat@example.test'));

        $this->get('/auth/github/callback')->assertRedirect(route('login'))->assertSessionHasErrors('auth');

        $this->assertGuest();
        $this->assertSame(0, SocialAccount::query()->count());
    }

    public function test_staff_accounts_are_never_linked(): void
    {
        User::factory()->admin()->create(['email' => 'boss@example.test']);
        $this->fakeProvider('github', fn () => $this->socialUser('gh-3', 'boss@example.test'));

        $this->get('/auth/github/callback')->assertRedirect(route('login'))->assertSessionHasErrors('auth');

        $this->assertGuest();
        $this->assertSame(0, SocialAccount::query()->count());
    }

    public function test_google_email_that_is_not_verified_is_not_trusted(): void
    {
        $owner = User::factory()->create(['email' => 'owner@example.test']);
        $this->fakeProvider('google', fn () => $this->socialUser('g-1', 'owner@example.test', ['email_verified' => false]));

        $this->get('/auth/google/callback')->assertRedirect('/');

        $account = SocialAccount::query()->firstOrFail();
        $this->assertNotSame($owner->id, $account->user_id);
        $this->assertNull($account->user->email);
        $this->assertSame(2, User::query()->count());
    }

    public function test_google_verified_email_links_to_the_existing_account(): void
    {
        $owner = User::factory()->create(['email' => 'owner@example.test']);
        $this->fakeProvider('google', fn () => $this->socialUser('g-2', 'owner@example.test', ['email_verified' => true]));

        $this->get('/auth/google/callback')->assertRedirect('/');

        $this->assertAuthenticatedAs($owner);
    }

    public function test_cancelled_sign_in_returns_to_the_login_page(): void
    {
        $this->get('/auth/github/callback?error=access_denied')->assertRedirect(route('login'))->assertSessionHasErrors('auth');

        $this->assertGuest();
    }

    public function test_provider_failures_do_not_leak_details(): void
    {
        $this->fakeProvider('github', fn () => throw new \RuntimeException('secret boom'));

        $this->get('/auth/github/callback')
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors(['auth' => 'Could not sign in with GitHub. Please try again.']);

        $this->assertGuest();
    }
}
__AUTH_EOF__
echo "  + tests/Feature/SocialAuthTest.php"

mkdir -p "tests/Feature"
cat > "tests/Feature/TelegramWidgetAuthTest.php" <<'__AUTH_EOF__'
<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\TelegramLoginWidget;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TelegramWidgetAuthTest extends TestCase
{
    use RefreshDatabase;

    private const TOKEN = '123456:TEST-bot-token';

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'telegram.bot_token' => self::TOKEN,
            'telegram.bot_username' => 'magaziine_bot',
            'telegram.widget_max_age' => 600,
        ]);
    }

    private function query(int $id = 4242, ?int $authDate = null, string $token = self::TOKEN): array
    {
        $data = [
            'id' => (string) $id,
            'first_name' => 'Ali',
            'last_name' => 'Valiyev',
            'username' => 'ali_v',
            'auth_date' => (string) ($authDate ?? now()->getTimestamp()),
        ];
        $data['hash'] = TelegramLoginWidget::signature($data, $token);

        return $data;
    }

    private function callback(array $query)
    {
        return $this->get('/auth/telegram/callback?'.http_build_query($query));
    }

    public function test_valid_widget_data_signs_in_and_creates_a_customer_without_email(): void
    {
        $this->callback($this->query())->assertRedirect('/');

        $user = User::query()->where('telegram_id', 4242)->firstOrFail();
        $this->assertAuthenticatedAs($user);
        $this->assertNull($user->email);
        $this->assertSame('ali_v', $user->telegram_username);
    }

    public function test_an_existing_telegram_user_is_reused(): void
    {
        $user = User::factory()->create();
        $user->forceFill(['telegram_id' => 4242])->save();

        $this->callback($this->query())->assertRedirect('/');

        $this->assertAuthenticatedAs($user);
        $this->assertSame(1, User::query()->count());
    }

    public function test_tampered_data_is_rejected(): void
    {
        $query = $this->query();
        $query['id'] = '1';

        $this->callback($query)->assertRedirect(route('login'))->assertSessionHasErrors('auth');

        $this->assertGuest();
        $this->assertSame(0, User::query()->count());
    }

    public function test_data_signed_with_another_token_is_rejected(): void
    {
        $this->callback($this->query(token: '999:OTHER'))->assertRedirect(route('login'))->assertSessionHasErrors('auth');

        $this->assertGuest();
    }

    public function test_stale_data_is_rejected(): void
    {
        $this->callback($this->query(authDate: now()->getTimestamp() - 3600))->assertRedirect(route('login'))->assertSessionHasErrors('auth');

        $this->assertGuest();
    }

    public function test_staff_with_a_telegram_id_cannot_sign_in(): void
    {
        $admin = User::factory()->admin()->create();
        $admin->forceFill(['telegram_id' => 4242])->save();

        $this->callback($this->query())->assertRedirect(route('login'))->assertSessionHasErrors('auth');

        $this->assertGuest();
    }

    public function test_callback_is_not_found_when_widget_is_not_configured(): void
    {
        config(['telegram.bot_username' => null]);

        $this->callback($this->query())->assertNotFound();
    }

    public function test_login_page_shows_the_widget_only_when_configured(): void
    {
        $this->get('/login')->assertSee('telegram-widget.js', false)->assertSee('data-telegram-login="magaziine_bot"', false);

        config(['telegram.bot_username' => null]);
        $this->get('/login')->assertDontSee('telegram-widget.js', false);
    }
}
__AUTH_EOF__
echo "  + tests/Feature/TelegramWidgetAuthTest.php"

mkdir -p "tests/Unit"
cat > "tests/Unit/TelegramLoginWidgetTest.php" <<'__AUTH_EOF__'
<?php

namespace Tests\Unit;

use App\Support\OneTimeCode;
use App\Support\TelegramLoginWidget;
use PHPUnit\Framework\TestCase;

/** Kutilgan qiymatlar PHP'dan mustaqil, Python (hashlib/hmac) bilan hisoblangan. */
class TelegramLoginWidgetTest extends TestCase
{
    private const TOKEN = '123456:TEST-bot-token';

    private function data(): array
    {
        return [
            'id' => '4242', 'first_name' => 'Ali', 'last_name' => 'Valiyev', 'username' => 'ali_v',
            'auth_date' => '1700000000', 'photo_url' => 'https://t.me/i/userpic/320/ali.jpg',
        ];
    }

    public function test_signature_matches_the_independent_vector(): void
    {
        $this->assertSame('f7a0ba69ff5a703be777fe54d6f52e1ba2a695b35a24f301921b31d745074d0d', TelegramLoginWidget::signature($this->data(), self::TOKEN));
    }

    public function test_valid_and_tampered_data(): void
    {
        $data = $this->data() + ['hash' => 'f7a0ba69ff5a703be777fe54d6f52e1ba2a695b35a24f301921b31d745074d0d'];
        $this->assertTrue(TelegramLoginWidget::isValid($data, self::TOKEN));

        $data['id'] = '1';
        $this->assertFalse(TelegramLoginWidget::isValid($data, self::TOKEN));
        $this->assertFalse(TelegramLoginWidget::isValid($this->data(), self::TOKEN), 'hash yo\'q');
        $this->assertFalse(TelegramLoginWidget::isValid($this->data() + ['hash' => 'x'], ''), 'token yo\'q');
        $this->assertFalse(TelegramLoginWidget::isValid($this->data() + ['hash' => ['a']], self::TOKEN));
    }

    public function test_one_time_code_hash_matches_the_independent_vector(): void
    {
        $this->assertSame('c67a84d72854c8cf7215eadbec01562b64f046c0197305e3800eacdaf8fa0f8b', OneTimeCode::hash('user@example.test', '123456', 'base64:test-key'));
    }

    public function test_generated_code_is_six_digits(): void
    {
        for ($i = 0; $i < 50; $i++) {
            $this->assertMatchesRegularExpression('/^\d{6}$/', OneTimeCode::generate());
        }
    }
}
__AUTH_EOF__
echo "  + tests/Unit/TelegramLoginWidgetTest.php"

echo "== O'zgartirilgan fayllar =="

mkdir -p "."
cat > ".env.example" <<'__AUTH_EOF__'
APP_NAME=Magaziine
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://localhost:8000

APP_LOCALE=en
APP_FALLBACK_LOCALE=en
APP_FAKER_LOCALE=en_US

APP_MAINTENANCE_DRIVER=file
# APP_MAINTENANCE_STORE=database

# PHP_CLI_SERVER_WORKERS=4

BCRYPT_ROUNDS=12

LOG_CHANNEL=stack
LOG_STACK=single
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=debug

DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=magaziine
DB_USERNAME=sail
DB_PASSWORD=password

SESSION_DRIVER=database
SESSION_LIFETIME=120
SESSION_ENCRYPT=false
SESSION_PATH=/
SESSION_DOMAIN=null

BROADCAST_CONNECTION=log
FILESYSTEM_DISK=local
QUEUE_CONNECTION=database

CACHE_STORE=database
# CACHE_PREFIX=

MEMCACHED_HOST=127.0.0.1

REDIS_CLIENT=phpredis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

MAIL_MAILER=log
MAIL_SCHEME=null
MAIL_HOST=127.0.0.1
MAIL_PORT=2525
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_FROM_ADDRESS="hello@example.com"
MAIL_FROM_NAME="${APP_NAME}"

AWS_ACCESS_KEY_ID=
AWS_SECRET_ACCESS_KEY=
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=
AWS_USE_PATH_STYLE_ENDPOINT=false

VITE_APP_NAME="${APP_NAME}"

LOCATION_DEFAULT="Select location"
LOCATION_USER_AGENT="FoodMart/1.0 (you@example.com)"
SUPPORT_EMAIL=
APP_STORE_URL=
PLAY_STORE_URL=

# Checkout xaritasi / geokodlash (config/geocoding.php)
GEOCODING_DRIVER=nominatim
# LOCATION_USER_AGENT ichida HAQIQIY aloqa manzilingiz bo'lishi shart (ommaviy Nominatim talabi)
GEOCODING_COUNTRYCODES=
CHECKOUT_REQUIRE_PIN=false
MAP_CENTER_LAT=41.2995
MAP_CENTER_LNG=69.2401
MAP_ZOOM=12

TELEGRAM_BOT_TOKEN=
TELEGRAM_WEBAPP_URL=
TRUSTED_PROXIES=

# Email kod bilan kirish: MAIL_MAILER=log bo'lsa kod storage/logs/laravel.log ichida chiqadi.

# Google / GitHub orqali kirish (config/services.php). Bo'sh bo'lsa, tugma ko'rinmaydi.
# Callback: APP_URL + /auth/google/callback va /auth/github/callback
GOOGLE_CLIENT_ID=
GOOGLE_CLIENT_SECRET=
GITHUB_CLIENT_ID=
GITHUB_CLIENT_SECRET=

# Saytdagi "Telegram bilan kirish" (Login Widget). Ochiq HTTPS domen va @BotFather da /setdomain kerak.
TELEGRAM_BOT_USERNAME=
TELEGRAM_WIDGET_MAX_AGE=600
__AUTH_EOF__
echo "  + .env.example"

mkdir -p "app/Filament/Resources/Users/Schemas"
cat > "app/Filament/Resources/Users/Schemas/UserForm.php" <<'__AUTH_EOF__'
<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Enums\UserRole;
use App\Models\User;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        $isSelf = fn (?User $record): bool => $record !== null && $record->is(auth()->user());

        return $schema
            ->components([
                TextInput::make('name')->required()->maxLength(120),
                // Telegram orqali kirgan mijozlarda email bo'lmasligi mumkin; yangi va boshqa hisoblarda majburiy.
                TextInput::make('email')->email()->required(fn (?User $record): bool => $record?->telegram_id === null)->maxLength(255)->unique(ignoreRecord: true),
                TextInput::make('phone')->tel()->maxLength(32),
                TextInput::make('address')->maxLength(255),
                TextInput::make('password')
                    ->password()
                    ->revealable()
                    ->minLength(8)
                    ->required(fn (string $operation): bool => $operation === 'create')
                    ->dehydrated(fn (?string $state): bool => filled($state))
                    ->helperText('Tahrirlashda bo\'sh qoldirilsa parol o\'zgarmaydi.'),
                Select::make('role')
                    ->options(UserRole::class)
                    ->default(UserRole::Customer->value)
                    ->required()
                    ->live()
                    ->disabled($isSelf)
                    ->helperText('O\'z rolingizni o\'zgartira olmaysiz.'),
                Select::make('brands')
                    ->relationship('brands', 'name')
                    ->multiple()
                    ->preload()
                    ->visible(fn (Get $get): bool => in_array($get('role'), [UserRole::Partner, UserRole::Partner->value], true))
                    ->helperText('Hamkor faqat shu brendlarni boshqaradi.'),
                Toggle::make('is_active')
                    ->label('Active')
                    ->default(true)
                    ->disabled($isSelf)
                    ->helperText('O\'chirilsa, foydalanuvchi tizimga kira olmaydi.'),
            ]);
    }
}
__AUTH_EOF__
echo "  + app/Filament/Resources/Users/Schemas/UserForm.php"

mkdir -p "app/Http/Controllers"
cat > "app/Http/Controllers/ProfileController.php" <<'__AUTH_EOF__'
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
            // Emaili bor foydalanuvchi uni o'chira olmaydi (kirish emaili shu). Telegram orqali kirganlarda email bo'sh bo'lishi mumkin.
            'email' => [$user->email !== null ? 'required' : 'nullable', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'phone' => ['nullable', 'string', 'max:32', 'regex:/^[0-9+()\-\s]+$/'],
            'address' => ['nullable', 'string', 'max:255'],
        ]);

        $user->fill($data);

        // Email o'zgarsa, yangi manzil tasdiqlanmagan hisoblanadi (email kod va Google/GitHub bog'lash uchun muhim).
        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

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
__AUTH_EOF__
echo "  + app/Http/Controllers/ProfileController.php"

mkdir -p "app/Models"
cat > "app/Models/User.php" <<'__AUTH_EOF__'
<?php

namespace App\Models;

use App\Enums\UserRole;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasAvatar;
use Filament\Panel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;

/** role va is_active ataylab $fillable da yo'q: oddiy forma orqali o'zgarmasin. */
#[Fillable(['name', 'email', 'password', 'phone', 'address'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements FilamentUser, HasAvatar
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected $attributes = [
        'role' => 'customer',
        'is_active' => true,
    ];

    /** @var array<int, int>|null */
    protected ?array $brandIdsCache = null;

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
            'is_active' => 'boolean',
        ];
    }

    public function hasRole(UserRole ...$roles): bool
    {
        return in_array($this->role, $roles, true);
    }

    public function isAdmin(): bool
    {
        return $this->hasRole(UserRole::Admin);
    }

    /** Admin panelga kira oladigan xodimlar. */
    public function isStaff(): bool
    {
        return $this->hasRole(UserRole::Admin, UserRole::Moderator, UserRole::Operator);
    }

    public function canAccessPanel(Panel $panel): bool
    {
        if (! $this->is_active) {
            return false;
        }

        return match ($panel->getId()) {
            'admin' => $this->isStaff(),
            'partner' => $this->hasRole(UserRole::Partner, UserRole::Admin),
            default => false,
        };
    }

    /** Profil rasmi URL i (yo'q bo'lsa null: sayt bosh harf, Filament standart avatar ko'rsatadi). */
    public function avatarUrl(): ?string
    {
        return $this->avatar_path ? Storage::disk('public')->url($this->avatar_path) : null;
    }

    /** Filament panellaridagi avatar (admin va partner). */
    public function getFilamentAvatarUrl(): ?string
    {
        return $this->avatarUrl();
    }

    /** Google / GitHub hisoblari (Telegram bu yerda emas: u users.telegram_id da). */
    public function socialAccounts(): HasMany
    {
        return $this->hasMany(SocialAccount::class);
    }

    public function carts(): HasMany
    {
        return $this->hasMany(Cart::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    /** Hamkor boshqaradigan brendlar. */
    public function brands(): BelongsToMany
    {
        return $this->belongsToMany(Brand::class)->withTimestamps();
    }

    public function ownsBrand(?int $brandId): bool
    {
        if ($brandId === null) {
            return false;
        }

        $this->brandIdsCache ??= $this->brands()->pluck('brands.id')->map(fn ($id): int => (int) $id)->all();

        return in_array($brandId, $this->brandIdsCache, true);
    }
}
__AUTH_EOF__
echo "  + app/Models/User.php"

mkdir -p "app/Services"
cat > "app/Services/TelegramAuthService.php" <<'__AUTH_EOF__'
<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Models\User;
use App\Support\TelegramLoginWidget;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/** Telegram Mini App: initData imzosini tekshiradi va Telegram foydalanuvchisiga mos hisobni beradi. */
class TelegramAuthService
{
    /** auth_date serverdan shuncha soniya "kelajakda" bo'lsa ham qabul qilinadi (soat farqi). */
    private const CLOCK_SKEW = 300;

    /**
     * https://core.telegram.org/bots/webapps (Validating data received via the Mini App)
     *
     * @return array<string, mixed> Telegram "user" obyekti
     */
    public function verify(string $initData): array
    {
        $token = (string) config('telegram.bot_token');

        if ($token === '') {
            Log::warning('Telegram sign-in: TELEGRAM_BOT_TOKEN is not set.');
            $this->fail('Telegram sign-in is not configured yet.');
        }

        $fields = $this->parse($initData);
        $hash = $fields['hash'] ?? '';
        unset($fields['hash']);
        ksort($fields, SORT_STRING);

        $check = collect($fields)->map(fn ($value, $key): string => "{$key}={$value}")->implode("\n");
        $secret = hash_hmac('sha256', $token, 'WebAppData', true);

        if ($hash === '' || ! hash_equals(hash_hmac('sha256', $check, $secret), $hash)) {
            $this->fail('Telegram data could not be verified.');
        }

        $age = now()->getTimestamp() - (int) ($fields['auth_date'] ?? 0);

        if ($age > (int) config('telegram.init_data_max_age') || $age < -self::CLOCK_SKEW) {
            $this->fail('Telegram session expired. Close the shop and open it again.');
        }

        $user = json_decode($fields['user'] ?? '', true);

        if (! is_array($user) || ! is_int($user['id'] ?? null) || $user['id'] <= 0) {
            $this->fail('Telegram data could not be verified.');
        }

        return $user;
    }

    /**
     * Saytdagi Telegram Login Widget (data-auth-url bo'yicha kelgan so'rov maydonlari) imzosini tekshiradi.
     * https://core.telegram.org/widgets/login-legacy
     *
     * @param  array<string, mixed>  $query
     * @return array<string, mixed> userFor() ga mos Telegram "user" massivi
     */
    public function verifyWidget(array $query): array
    {
        $token = (string) config('telegram.bot_token');

        if ($token === '') {
            Log::warning('Telegram sign-in: TELEGRAM_BOT_TOKEN is not set.');
            $this->fail('Telegram sign-in is not configured yet.');
        }

        if (! TelegramLoginWidget::isValid($query, $token)) {
            $this->fail('Telegram data could not be verified.');
        }

        $age = now()->getTimestamp() - (int) ($query['auth_date'] ?? 0);

        if ($age > (int) config('telegram.widget_max_age') || $age < -self::CLOCK_SKEW) {
            $this->fail('Telegram session expired. Please try again.');
        }

        $id = $query['id'] ?? '';

        if (! is_string($id) || ! ctype_digit($id) || (int) $id <= 0) {
            $this->fail('Telegram data could not be verified.');
        }

        return [
            'id' => (int) $id,
            'first_name' => (string) ($query['first_name'] ?? ''),
            'last_name' => (string) ($query['last_name'] ?? ''),
            'username' => isset($query['username']) ? (string) $query['username'] : null,
        ];
    }

    /** @param  array<string, mixed>  $telegram  verify() qaytargan massiv */
    public function userFor(array $telegram): User
    {
        $user = User::query()->where('telegram_id', $telegram['id'])->first() ?? $this->register($telegram);

        if (! $user->is_active) {
            $this->fail('This account is disabled.');
        }

        // Parolsiz kirish faqat mijoz hisobiga: xodim va hamkor panellari Telegram orqali ochilmasin.
        if (! $user->hasRole(UserRole::Customer)) {
            $this->fail('Staff accounts must sign in on the website.');
        }

        $username = is_string($telegram['username'] ?? null) ? Str::limit($telegram['username'], 64, '') : null;
        $user->forceFill(['telegram_username' => $username])->save();

        return $user;
    }

    /** @param  array<string, mixed>  $telegram */
    private function register(array $telegram): User
    {
        $name = Str::squish(($telegram['first_name'] ?? '').' '.($telegram['last_name'] ?? ''));

        // Telegram email bermaydi: email bo'sh (NULL) qoladi. Haqiqiy emailni foydalanuvchi keyin o'zi qo'shadi.
        $user = new User([
            'name' => Str::limit($name !== '' ? $name : 'Telegram user', 120, ''),
            'password' => Str::random(48),
        ]);

        try {
            $user->forceFill(['telegram_id' => $telegram['id']])->save();
        } catch (UniqueConstraintViolationException) {
            return User::query()->where('telegram_id', $telegram['id'])->firstOrFail();
        }

        return $user;
    }

    /** @return array<string, string> */
    private function parse(string $initData): array
    {
        $fields = [];

        foreach (explode('&', $initData) as $pair) {
            [$key, $value] = array_pad(explode('=', $pair, 2), 2, '');

            if ($key !== '') {
                $fields[urldecode($key)] = urldecode($value);
            }
        }

        return $fields;
    }

    private function fail(string $message): never
    {
        throw ValidationException::withMessages(['init_data' => $message]);
    }
}
__AUTH_EOF__
echo "  + app/Services/TelegramAuthService.php"

mkdir -p "config"
cat > "config/services.php" <<'__AUTH_EOF__'
<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Resend, Postmark, AWS, and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    /* Google / GitHub orqali kirish (Laravel Socialite). Bo'sh bo'lsa, tugma login sahifasida ko'rinmaydi. */
    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect' => env('GOOGLE_REDIRECT_URI', rtrim((string) env('APP_URL', 'http://localhost'), '/').'/auth/google/callback'),
    ],

    'github' => [
        'client_id' => env('GITHUB_CLIENT_ID'),
        'client_secret' => env('GITHUB_CLIENT_SECRET'),
        'redirect' => env('GITHUB_REDIRECT_URI', rtrim((string) env('APP_URL', 'http://localhost'), '/').'/auth/github/callback'),
    ],

];
__AUTH_EOF__
echo "  + config/services.php"

mkdir -p "config"
cat > "config/telegram.php" <<'__AUTH_EOF__'
<?php

return [

    /* @BotFather bergan token. Faqat .env da saqlanadi. */
    'bot_token' => env('TELEGRAM_BOT_TOKEN'),

    /* Mini App ning ochiq HTTPS manzili. Bo'sh bo'lsa APP_URL + /tma. */
    'webapp_url' => env('TELEGRAM_WEBAPP_URL'),

    /* initData necha soniya yaroqli. */
    'init_data_max_age' => (int) env('TELEGRAM_INIT_DATA_MAX_AGE', 86400),

    /* Saytdagi "Telegram bilan kirish" tugmasi uchun bot username (@ siz). Bo'sh bo'lsa tugma ko'rinmaydi. */
    'bot_username' => env('TELEGRAM_BOT_USERNAME'),

    /* Login Widget imzosi necha soniya yaroqli (initData dan qisqaroq: URL ga tushadi). */
    'widget_max_age' => (int) env('TELEGRAM_WIDGET_MAX_AGE', 600),

    /* Bot menyu tugmasidagi yozuv. */
    'menu_button_text' => env('TELEGRAM_MENU_TEXT', 'Shop'),

    /* Tunnel (cloudflared/ngrok) yoki reverse proxy orqasida: lokalda "*", serverda proxy IP si. */
    'trusted_proxies' => env('TRUSTED_PROXIES'),

];
__AUTH_EOF__
echo "  + config/telegram.php"

mkdir -p "resources/views/pages/auth"
cat > "resources/views/pages/auth/login.blade.php" <<'__AUTH_EOF__'
@extends('layouts.app')

@section('title', 'Sign in - FoodMart')

@section('content')
<div class="mx-auto max-w-md py-10">
    <div class="dark-glass-card rounded-3xl p-8">
        <h1 class="font-display font-extrabold text-3xl text-text-crisp">Sign in</h1>
        <p class="mt-1 text-sm text-text-muted">Welcome back. Your cart and favorites will follow you.</p>

        @error('auth')
            <p class="mt-4 rounded-xl border border-red-400/40 bg-red-400/10 px-4 py-3 text-sm text-red-300">{{ $message }}</p>
        @enderror

        <form method="POST" action="{{ route('login.store') }}" class="mt-8 space-y-5">
            @csrf
            <x-field name="email" label="Email" type="email" autocomplete="email" required />
            <x-field name="password" label="Password" type="password" autocomplete="current-password" required />

            <label class="flex items-center gap-3 text-sm text-text-crisp cursor-pointer">
                <input type="checkbox" name="remember" value="1" class="h-4 w-4 rounded border-border-glass bg-surface-carbon text-primary focus:ring-primary/30">
                Remember me
            </label>

            <button type="submit" class="water-ripple-target w-full rounded-full bg-primary hover:bg-emerald-400 py-3 font-display text-xs font-bold uppercase tracking-wider text-black transition-colors">Sign in</button>
        </form>

        @include('pages.auth.partials.alternatives')

        <p class="mt-6 text-center text-sm text-text-muted">
            New here? <a href="{{ route('register') }}" class="font-bold text-primary hover:underline">Create an account</a>
        </p>
    </div>
</div>
@endsection
__AUTH_EOF__
echo "  + resources/views/pages/auth/login.blade.php"

mkdir -p "resources/views/pages"
cat > "resources/views/pages/profile.blade.php" <<'__AUTH_EOF__'
@extends('layouts.app')

@section('title', 'My profile - FoodMart')

@section('content')
<x-breadcrumbs :items="[['label' => 'Home', 'url' => route('home')], ['label' => 'Profile', 'url' => null]]" />

<section class="mt-6 dark-glass-card rounded-3xl p-6 md:p-8 flex flex-col md:flex-row md:items-center justify-between gap-6">
    <div class="flex items-center gap-5">
        <x-avatar :user="$user" initial size="w-20 h-20" text="text-3xl" class="shadow-[0_0_24px_var(--primary-glow)]" />
        <div>
            <h1 class="font-display font-extrabold text-2xl sm:text-3xl text-text-crisp">{{ $user->name }}</h1>
            <p class="text-sm text-text-muted">{{ $user->email ?: 'No email added' }} &middot; member since {{ $user->created_at->format('M Y') }}</p>
            <a href="{{ route('settings') }}" class="mt-1 inline-block text-xs font-bold text-primary hover:underline">Change photo, theme &amp; fonts</a>
        </div>
    </div>
    <div class="flex items-center gap-2">
        @if ($user->isStaff())
            <a href="{{ url('/admin') }}" class="rounded-full border border-border-glass px-5 py-2.5 text-xs font-mono font-bold uppercase tracking-wider text-text-muted hover:text-primary hover:border-primary/40 transition-colors">Admin panel</a>
        @endif
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="rounded-full border border-red-400/40 px-5 py-2.5 text-xs font-mono font-bold uppercase tracking-wider text-red-300 hover:bg-red-500/10 transition-colors">Sign out</button>
        </form>
    </div>
</section>

<div class="mt-6 grid grid-cols-1 sm:grid-cols-3 gap-4">
    <x-profile-stat label="Orders" icon="inventory_2" :value="$stats['orders']" sub="All time" />
    <x-profile-stat label="Total spent" icon="payments" icon-class="text-accent-electric-lime" :value="'$'.$stats['spent']" sub="Without cancelled orders" />
    <x-profile-stat label="Saved items" icon="favorite" :value="$stats['saved']" sub="In your favorites" />
</div>

<div class="mt-8 grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
    <section class="lg:col-span-7 dark-glass-card rounded-3xl p-6">
        <h2 class="font-display font-bold text-xl text-text-crisp">My orders</h2>

        @if ($orders->isNotEmpty())
            <div class="mt-2 divide-y divide-border-glass">
                @foreach ($orders as $order)
                    <x-profile-order :order="$order" />
                @endforeach
            </div>
            {{ $orders->links() }}
        @else
            <p class="mt-4 text-sm text-text-muted">No orders yet. <a href="{{ route('catalog.index') }}" class="font-bold text-primary hover:underline">Start shopping</a></p>
        @endif
    </section>

    <div class="lg:col-span-5 space-y-6">
        <section class="dark-glass-card rounded-3xl p-6">
            <h2 class="font-display font-bold text-xl text-text-crisp mb-5">Profile details</h2>
            <form method="POST" action="{{ route('profile.update') }}" class="space-y-4">
                @csrf
                @method('PUT')
                <x-field name="name" label="Full name" :value="$user->name" autocomplete="name" required />
                <x-field name="email" label="Email" type="email" :value="$user->email" autocomplete="email" required />
                <x-field name="phone" label="Phone" type="tel" :value="$user->phone" autocomplete="tel" />
                <x-field name="address" label="Default address" :value="$user->address" autocomplete="street-address" />
                <button type="submit" class="water-ripple-target rounded-full bg-primary hover:bg-emerald-400 px-6 py-2.5 font-display text-xs font-bold uppercase tracking-wider text-black transition-colors">Save changes</button>
            </form>
        </section>

        <section class="dark-glass-card rounded-3xl p-6">
            <h2 class="font-display font-bold text-xl text-text-crisp mb-5">Change password</h2>
            <form method="POST" action="{{ route('profile.password') }}" class="space-y-4">
                @csrf
                @method('PUT')
                <x-field name="current_password" label="Current password" type="password" autocomplete="current-password" required />
                <x-field name="password" label="New password" type="password" autocomplete="new-password" required />
                <x-field name="password_confirmation" label="Repeat new password" type="password" autocomplete="new-password" required />
                <button type="submit" class="water-ripple-target rounded-full border border-primary/40 bg-primary/10 hover:bg-primary hover:text-black px-6 py-2.5 font-display text-xs font-bold uppercase tracking-wider text-primary transition-colors">Update password</button>
            </form>
        </section>
    </div>
</div>
@endsection
__AUTH_EOF__
echo "  + resources/views/pages/profile.blade.php"

mkdir -p "routes"
cat > "routes/web.php" <<'__AUTH_EOF__'
<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BlogController;
use App\Http\Controllers\BrandController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CatalogController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\CheckoutGeocodeController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\LocationController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\WishlistController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');

Route::get('/catalog', [CatalogController::class, 'index'])->name('catalog.index');
Route::get('/categories/{category:slug}', [CatalogController::class, 'category'])->name('catalog.category');
Route::get('/products/{product:slug}', [ProductController::class, 'show'])->name('products.show');

Route::get('/brands', [BrandController::class, 'index'])->name('brands.index');
Route::get('/brands/{brand:slug}', [BrandController::class, 'show'])->name('brands.show');

Route::get('/blog', [BlogController::class, 'index'])->name('blog.index');
Route::get('/blog/{post:slug}', [BlogController::class, 'show'])->name('blog.show');

// Savat (mehmon ham, foydalanuvchi ham)
Route::get('/cart', [CartController::class, 'show'])->name('cart.show');
Route::get('/cart/summary', [CartController::class, 'summary'])->name('cart.summary');
Route::post('/cart/items', [CartController::class, 'store'])->name('cart.items.store');
Route::patch('/cart/items/{variantId}', [CartController::class, 'update'])->whereNumber('variantId')->name('cart.items.update');
Route::delete('/cart/items/{variantId}', [CartController::class, 'destroy'])->whereNumber('variantId')->name('cart.items.destroy');
Route::delete('/cart', [CartController::class, 'clear'])->name('cart.clear');

// Sevimlilar (mehmon ham, foydalanuvchi ham)
Route::get('/wishlist', [WishlistController::class, 'show'])->name('wishlist.show');
Route::get('/wishlist/summary', [WishlistController::class, 'summary'])->name('wishlist.summary');
Route::post('/wishlist/move-to-cart', [WishlistController::class, 'moveToCart'])->name('wishlist.move');
Route::delete('/wishlist', [WishlistController::class, 'clear'])->name('wishlist.clear');
Route::post('/wishlist/{product}', [WishlistController::class, 'toggle'])->whereNumber('product')->name('wishlist.toggle');
Route::delete('/wishlist/{productId}', [WishlistController::class, 'destroy'])->whereNumber('productId')->name('wishlist.destroy');

// Joylashuv (header tugmasi)
Route::post('/location/resolve', [LocationController::class, 'resolve'])->middleware('throttle:10,1')->name('location.resolve');

// Sozlamalar: hamma uchun (mehmon ham). Rasm yuklash/o'chirish faqat kirganlar uchun (pastda).
Route::get('/settings', [SettingsController::class, 'show'])->name('settings');

// Mehmonlar uchun
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:6,1')->name('login.store');
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:6,1')->name('register.store');
});

// Faqat tizimga kirganlar
Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    Route::get('/checkout', [CheckoutController::class, 'show'])->name('checkout');
    Route::post('/checkout', [CheckoutController::class, 'store'])->name('checkout.store');
    Route::post('/checkout/geocode/reverse', [CheckoutGeocodeController::class, 'reverse'])->middleware('throttle:30,1')->name('checkout.geocode.reverse');
    Route::post('/checkout/geocode/search', [CheckoutGeocodeController::class, 'search'])->middleware('throttle:12,1')->name('checkout.geocode.search');

    Route::get('/orders/{order}', [OrderController::class, 'show'])->name('orders.show');
    Route::post('/orders/{order}/cancel', [OrderController::class, 'cancel'])->name('orders.cancel');
    Route::post('/orders/{order}/reorder', [OrderController::class, 'reorder'])->name('orders.reorder');

    Route::get('/profile', [ProfileController::class, 'show'])->name('profile');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profile/password', [ProfileController::class, 'password'])->name('profile.password');

    Route::post('/settings/avatar', [SettingsController::class, 'updateAvatar'])->middleware('throttle:10,1')->name('settings.avatar.update');
    Route::delete('/settings/avatar', [SettingsController::class, 'destroyAvatar'])->name('settings.avatar.destroy');
});

require __DIR__.'/support.php';
require __DIR__.'/tma.php';
require __DIR__.'/passwordless.php';

require __DIR__.'/engagement.php';
__AUTH_EOF__
echo "  + routes/web.php"

mkdir -p "tests/Feature"
cat > "tests/Feature/TelegramAuthTest.php" <<'__AUTH_EOF__'
<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TelegramAuthTest extends TestCase
{
    use RefreshDatabase;

    private const TOKEN = '123456:TEST-bot-token';

    protected function setUp(): void
    {
        parent::setUp();

        config(['telegram.bot_token' => self::TOKEN, 'telegram.init_data_max_age' => 86400]);
    }

    private function initData(int $id = 777, array $user = [], ?int $authDate = null, string $token = self::TOKEN): string
    {
        $fields = [
            'auth_date' => (string) ($authDate ?? now()->getTimestamp()),
            'query_id' => 'AAH-test',
            'signature' => 'third-party-signature',
            'user' => json_encode(['id' => $id] + $user + ['first_name' => 'Ali', 'last_name' => 'Valiyev', 'username' => 'ali_v']),
        ];
        ksort($fields);

        $check = collect($fields)->map(fn (string $value, string $key): string => "{$key}={$value}")->implode("\n");
        $fields['hash'] = hash_hmac('sha256', $check, hash_hmac('sha256', $token, 'WebAppData', true));

        return http_build_query($fields, '', '&', PHP_QUERY_RFC3986);
    }

    public function test_login_page_loads_the_telegram_script(): void
    {
        $this->get('/tma/login')
            ->assertOk()
            ->assertSee('telegram.org/js/telegram-web-app.js', false)
            ->assertSee('js/pages/tma-login.js', false)
            ->assertSee('name="csrf-token"', false);
    }

    public function test_guests_are_sent_to_the_login_page(): void
    {
        $this->get('/tma')->assertRedirect(route('tma.login'));
    }

    public function test_website_users_without_telegram_are_sent_to_the_login_page(): void
    {
        $this->actingAs(User::factory()->create())->get('/tma')->assertRedirect(route('tma.login'));
    }

    public function test_valid_data_creates_a_customer_and_signs_in(): void
    {
        $this->postJson('/tma/auth', ['init_data' => $this->initData()])
            ->assertOk()
            ->assertJson(['redirect' => route('tma.home')]);

        $user = User::query()->where('telegram_id', 777)->firstOrFail();

        $this->assertAuthenticatedAs($user);
        $this->assertSame('Ali Valiyev', $user->name);
        $this->assertSame('ali_v', $user->telegram_username);
        $this->assertSame(UserRole::Customer, $user->role);
        $this->assertNull($user->email);
        $this->get('/tma')->assertOk()->assertSee('Ali Valiyev');
    }

    public function test_second_sign_in_reuses_the_account(): void
    {
        $this->postJson('/tma/auth', ['init_data' => $this->initData()])->assertOk();
        $this->postJson('/tma/auth', ['init_data' => $this->initData(user: ['username' => 'new_name'])])->assertOk();

        $this->assertSame(1, User::query()->count());
        $this->assertSame('new_name', User::query()->firstOrFail()->telegram_username);
    }

    public function test_the_requested_page_is_opened_after_sign_in(): void
    {
        $this->get('/tma?from=menu')->assertRedirect(route('tma.login'));

        $response = $this->postJson('/tma/auth', ['init_data' => $this->initData()])->assertOk();

        $this->assertStringContainsString('/tma?from=menu', $response->json('redirect'));
    }

    public function test_tampered_data_is_rejected(): void
    {
        $tampered = str_replace('777', '778', $this->initData());

        $this->postJson('/tma/auth', ['init_data' => $tampered])->assertStatus(422);

        $this->assertGuest();
        $this->assertSame(0, User::query()->count());
    }

    public function test_data_signed_with_another_bot_is_rejected(): void
    {
        $this->postJson('/tma/auth', ['init_data' => $this->initData(token: '999:other')])->assertStatus(422);
        $this->postJson('/tma/auth', ['init_data' => 'user=%7B%22id%22%3A1%7D'])->assertStatus(422);
        $this->postJson('/tma/auth', [])->assertStatus(422);

        $this->assertGuest();
    }

    public function test_old_and_future_data_is_rejected(): void
    {
        $this->postJson('/tma/auth', ['init_data' => $this->initData(authDate: now()->subDays(2)->getTimestamp())])->assertStatus(422);
        $this->postJson('/tma/auth', ['init_data' => $this->initData(authDate: now()->addHour()->getTimestamp())])->assertStatus(422);

        $this->assertGuest();
    }

    public function test_missing_bot_token_rejects_every_sign_in(): void
    {
        config(['telegram.bot_token' => null]);

        $this->postJson('/tma/auth', ['init_data' => $this->initData(token: '')])->assertStatus(422);

        $this->assertGuest();
    }

    public function test_disabled_and_staff_accounts_cannot_use_telegram_sign_in(): void
    {
        User::factory()->create()->forceFill(['telegram_id' => 1, 'is_active' => false])->save();
        User::factory()->create()->forceFill(['telegram_id' => 2, 'role' => UserRole::Admin])->save();

        $this->postJson('/tma/auth', ['init_data' => $this->initData(1)])->assertStatus(422);
        $this->postJson('/tma/auth', ['init_data' => $this->initData(2)])->assertStatus(422);

        $this->assertGuest();
    }

    public function test_switching_telegram_accounts_switches_the_user(): void
    {
        $this->postJson('/tma/auth', ['init_data' => $this->initData(1)])->assertOk();
        $this->postJson('/tma/auth', ['init_data' => $this->initData(2)])->assertOk();

        $this->assertSame(2, (int) auth()->user()->telegram_id);
    }

    public function test_guest_cart_moves_to_the_telegram_account(): void
    {
        $variant = ProductVariant::factory()->create(['stock' => 10]);
        $this->postJson('/cart/items', ['variant_id' => $variant->id, 'quantity' => 2])->assertOk();

        $this->postJson('/tma/auth', ['init_data' => $this->initData()])->assertOk();

        $this->getJson('/cart/summary')->assertOk()->assertJsonPath('cart.count', 2);
        $this->assertDatabaseHas('carts', ['user_id' => auth()->id()]);
    }

    public function test_setup_command_connects_the_menu_button(): void
    {
        Http::fake(['api.telegram.org/*' => Http::response(['ok' => true, 'result' => true])]);

        $this->artisan('telegram:setup', ['url' => 'https://shop.test/tma'])->assertSuccessful();

        Http::assertSent(fn ($request) => str_ends_with($request->url(), '/setChatMenuButton')
            && $request['menu_button']['web_app']['url'] === 'https://shop.test/tma');
    }

    public function test_setup_command_requires_https_and_reports_rejection(): void
    {
        Http::fake(['api.telegram.org/*' => Http::response(['ok' => false, 'description' => 'Unauthorized'], 401)]);

        $this->artisan('telegram:setup', ['url' => 'http://localhost/tma'])->assertFailed();
        Http::assertNothingSent();

        $this->artisan('telegram:setup', ['url' => 'https://shop.test/tma'])->assertFailed();
    }
}
__AUTH_EOF__
echo "  + tests/Feature/TelegramAuthTest.php"

# .env ga yangi kalitlarni qo'shamiz (agar yo'q bo'lsa)
if [ -f .env ] && ! grep -q "^GOOGLE_CLIENT_ID=" .env; then
  cat >> .env <<'__ENV_EOF__'

GOOGLE_CLIENT_ID=
GOOGLE_CLIENT_SECRET=
GITHUB_CLIENT_ID=
GITHUB_CLIENT_SECRET=
TELEGRAM_BOT_USERNAME=
TELEGRAM_WIDGET_MAX_AGE=600
__ENV_EOF__
  echo "  + .env ga yangi kalitlar qo'shildi"
fi

cat <<'__DONE__'

TAYYOR. Endi shu buyruqlarni ketma-ket bajaring:

  ./vendor/bin/sail composer require laravel/socialite
  ./vendor/bin/sail artisan migrate
  ./vendor/bin/sail artisan config:clear
  ./vendor/bin/sail artisan test --filter="EmailCodeAuthTest|SocialAuthTest|TelegramWidgetAuthTest|ProfileEmailTest|TelegramLoginWidgetTest|TelegramAuthTest"
__DONE__
