set -euo pipefail

fail() {
  echo "Xato: $1" >&2
  exit 1
}

[ -f artisan ] && [ -f routes/web.php ] || fail "loyiha ildizida ishga tushiring"

if grep -qF "require __DIR__.'/tma.php';" routes/web.php; then
  echo "A qism allaqachon qo'llangan."
  exit 0
fi

ROUTES_ANCHOR="require __DIR__.'/support.php';"
PROVIDER_ANCHOR="        Paginator::defaultView('vendor.pagination.neon');"

grep -qF "$ROUTES_ANCHOR" routes/web.php || fail "routes/web.php ichida anchor topilmadi"
grep -qF "$PROVIDER_ANCHOR" app/Providers/AppServiceProvider.php || fail "AppServiceProvider ichida anchor topilmadi"

patch_after() {
  ANCHOR="$2" ADD="$3" perl -0pi -e '$found ||= s/\Q$ENV{ANCHOR}\E/$ENV{ANCHOR}$ENV{ADD}/; END { die "Anchor topilmadi\n" unless $found }' "$1" || fail "$1 ichida anchor topilmadi"
}

mkdir -p app/Services app/Http/Controllers/Tma app/Http/Middleware app/Console/Commands resources/views/tma public/js/pages public/css tests/Feature config database/migrations routes

cat > config/telegram.php <<'EOF'
<?php

return [

    /* @BotFather bergan token. Faqat .env da saqlanadi. */
    'bot_token' => env('TELEGRAM_BOT_TOKEN'),

    /* Mini App ning ochiq HTTPS manzili. Bo'sh bo'lsa APP_URL + /tma. */
    'webapp_url' => env('TELEGRAM_WEBAPP_URL'),

    /* initData necha soniya yaroqli. */
    'init_data_max_age' => (int) env('TELEGRAM_INIT_DATA_MAX_AGE', 86400),

    /* Bot menyu tugmasidagi yozuv. */
    'menu_button_text' => env('TELEGRAM_MENU_TEXT', 'Shop'),

    /* Tunnel (cloudflared/ngrok) yoki reverse proxy orqasida: lokalda "*", serverda proxy IP si. */
    'trusted_proxies' => env('TRUSTED_PROXIES'),

];
EOF

cat > database/migrations/2026_12_02_100000_add_telegram_to_users_table.php <<'EOF'
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedBigInteger('telegram_id')->nullable()->unique()->after('email');
            $table->string('telegram_username', 64)->nullable()->after('telegram_id');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['telegram_id']);
            $table->dropColumn(['telegram_id', 'telegram_username']);
        });
    }
};
EOF

cat > app/Services/TelegramAuthService.php <<'EOF'
<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Models\User;
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

        // Telegram email bermaydi: .invalid zonasi hech qachon haqiqiy manzil bo'lmaydi.
        $user = new User([
            'name' => Str::limit($name !== '' ? $name : 'Telegram user', 120, ''),
            'email' => sprintf('tg%d.%s@telegram.invalid', $telegram['id'], Str::lower(Str::random(12))),
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
EOF

cat > app/Http/Controllers/Tma/AuthController.php <<'EOF'
<?php

namespace App\Http\Controllers\Tma;

use App\Http\Controllers\Controller;
use App\Services\CartService;
use App\Services\SupportChatService;
use App\Services\TelegramAuthService;
use App\Services\WishlistService;
use App\Support\Visitor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function store(
        Request $request,
        TelegramAuthService $telegram,
        CartService $cart,
        WishlistService $wishlist,
        SupportChatService $support,
    ): JsonResponse {
        $data = $request->validate(['init_data' => ['required', 'string', 'max:8192']]);

        $user = $telegram->userFor($telegram->verify($data['init_data']));
        $intended = $request->session()->pull('tma.intended');

        if (! $request->user()?->is($user)) {
            $guestToken = Visitor::peek();

            // Boshqa hisob ochiq qolgan bo'lsa, uning sessiyasi yangi foydalanuvchiga o'tmasin.
            if (Auth::check()) {
                Auth::logout();
                $request->session()->invalidate();
                $guestToken = null;
                $intended = null;
            }

            Auth::login($user);
            $request->session()->regenerate();

            $support->adoptGuest($user);
            $cart->mergeGuestCart($user, $guestToken);
            $wishlist->mergeGuestList($user, $guestToken);
            Visitor::forget();
        }

        return response()->json(['redirect' => $intended ?? route('tma.home')]);
    }
}
EOF

cat > app/Http/Middleware/EnsureTelegramUser.php <<'EOF'
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Mini App sahifalari faqat Telegram orqali kirgan foydalanuvchiga ochiladi. */
class EnsureTelegramUser
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()?->telegram_id === null) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Open the shop from Telegram.'], 401);
            }

            if ($request->isMethod('GET')) {
                $request->session()->put('tma.intended', $request->fullUrl());
            }

            return redirect()->route('tma.login');
        }

        return $next($request);
    }
}
EOF

cat > app/Console/Commands/TelegramSetup.php <<'EOF'
<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TelegramSetup extends Command
{
    protected $signature = 'telegram:setup {url? : Mini App HTTPS manzili}';

    protected $description = 'Botning menyu tugmasini Mini App ga ulaydi';

    public function handle(): int
    {
        $token = (string) config('telegram.bot_token');
        $url = (string) ($this->argument('url')
            ?: config('telegram.webapp_url')
            ?: rtrim((string) config('app.url'), '/').'/tma');

        if ($token === '') {
            $this->error('TELEGRAM_BOT_TOKEN .env da yo\'q.');

            return self::FAILURE;
        }

        if (! str_starts_with($url, 'https://')) {
            $this->error("Telegram faqat HTTPS manzilni qabul qiladi: {$url}");

            return self::FAILURE;
        }

        try {
            $response = Http::timeout(10)->post("https://api.telegram.org/bot{$token}/setChatMenuButton", [
                'menu_button' => [
                    'type' => 'web_app',
                    'text' => (string) config('telegram.menu_button_text'),
                    'web_app' => ['url' => $url],
                ],
            ]);
        } catch (ConnectionException) {
            // Xatolik matnida token bor URL bo'ladi: logga va ekranga chiqarilmaydi.
            Log::warning('Telegram setChatMenuButton: connection failed.');
            $this->error('Telegram serveriga ulanib bo\'lmadi.');

            return self::FAILURE;
        }

        if (! $response->successful() || $response->json('ok') !== true) {
            $reason = (string) $response->json('description', 'unknown error');
            Log::warning('Telegram setChatMenuButton failed.', ['status' => $response->status(), 'description' => $reason]);
            $this->error("Telegram rad etdi: {$reason}");

            return self::FAILURE;
        }

        $this->info("Menyu tugmasi ulandi: {$url}");

        return self::SUCCESS;
    }
}
EOF

cat > routes/tma.php <<'EOF'
<?php

use App\Http\Controllers\Tma\AuthController;
use App\Http\Middleware\EnsureTelegramUser;
use Illuminate\Support\Facades\Route;

Route::prefix('tma')->name('tma.')->group(function () {
    Route::view('login', 'tma.login')->name('login');
    Route::post('auth', [AuthController::class, 'store'])->middleware('throttle:20,1,tma-auth')->name('auth');

    Route::middleware(EnsureTelegramUser::class)->group(function () {
        Route::view('/', 'tma.home')->name('home');
    });
});
EOF

cat > resources/views/tma/layout.blade.php <<'EOF'
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="tg-user-id" content="{{ auth()->user()?->telegram_id }}">
    <title>@yield('title', 'FoodMart')</title>
    <script src="https://telegram.org/js/telegram-web-app.js?64"></script>
    <link rel="stylesheet" href="{{ asset('css/tma.css') }}">
</head>
<body data-login="{{ route('tma.login') }}" data-back="@yield('back')" @hasSection('bare') data-bare @endif>
    <main class="page">
        @if (session('status'))
            <p class="note" role="status">{{ session('status') }}</p>
        @endif
        @foreach ($errors->all() as $error)
            <p class="note note-error" role="alert">{{ $error }}</p>
        @endforeach

        @yield('content')
    </main>

    <div id="tma-toast" class="toast" role="status" hidden></div>

    @unless (View::hasSection('bare'))
        @includeIf('tma.nav')
    @endunless

    <script src="{{ asset('js/pages/tma.js') }}"></script>
    @stack('scripts')
</body>
</html>
EOF

cat > resources/views/tma/login.blade.php <<'EOF'
@extends('tma.layout')

@section('bare', '1')

@section('content')
<div id="tma-login" class="center" data-url="{{ route('tma.auth') }}">
    <h1>FoodMart</h1>
    <p id="tma-status" class="muted" role="status">Signing you in...</p>
    <button type="button" id="tma-retry" class="btn" hidden>Try again</button>
    <noscript><p>JavaScript is required.</p></noscript>
</div>
@endsection

@push('scripts')
    <script src="{{ asset('js/pages/tma-login.js') }}"></script>
@endpush
EOF

cat > resources/views/tma/home.blade.php <<'EOF'
@extends('tma.layout')

@section('content')
<div class="center">
    <h1>Hi, {{ auth()->user()->name }}</h1>
    <p class="muted">You are signed in with Telegram.</p>
</div>
@endsection
EOF

cat > public/js/pages/tma-login.js <<'EOF'
// Mini App kirish sahifasi: Telegram initData serverga yuboriladi, tekshiruv serverda.
(function () {
  const box = document.getElementById('tma-login');
  if (!box) return;

  const tg = window.Telegram && window.Telegram.WebApp;
  const status = document.getElementById('tma-status');
  const retry = document.getElementById('tma-retry');
  const KEY = 'tma_signins';

  function show(message, canRetry) {
    status.textContent = message;
    retry.hidden = !canRetry;
  }

  // Cookie bloklansa "kirish -> sahifa -> yana kirish" aylanishi bo'ladi: 30 soniyada 3 martadan keyin to'xtaymiz.
  function recent() {
    try {
      return JSON.parse(sessionStorage.getItem(KEY) || '[]').filter((t) => Date.now() - t < 30000);
    } catch (e) {
      return [];
    }
  }
  function remember(list) {
    try { sessionStorage.setItem(KEY, JSON.stringify(list)); } catch (e) { /* storage blocked */ }
  }

  async function signIn() {
    if (!tg || !tg.initData) {
      show('Open this shop from the Telegram bot.', false);
      return;
    }
    if (recent().length >= 3) {
      show('Could not keep you signed in. Cookies may be blocked: try the Telegram mobile app.', true);
      return;
    }

    show('Signing you in...', false);

    try {
      const res = await fetch(box.dataset.url, {
        method: 'POST',
        credentials: 'same-origin',
        headers: {
          Accept: 'application/json',
          'Content-Type': 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
        },
        body: JSON.stringify({ init_data: tg.initData }),
      });
      const data = await res.json().catch(() => ({}));

      if (res.status === 419) throw new Error('Session could not be created. Close the shop and open it again.');
      if (!res.ok || !data.redirect) throw new Error(data.message || 'Sign in failed.');

      remember(recent().concat(Date.now()));
      window.location.replace(data.redirect);
    } catch (e) {
      show(e.message, true);
    }
  }

  retry.addEventListener('click', () => {
    remember([]);
    signIn();
  });

  signIn();
})();
EOF

cat > public/js/pages/tma.js <<'EOF'
// Mini App ning umumiy skripti: Telegram tugmalari, savatga qo'shish, formalar.
(function () {
  const tg = window.Telegram && window.Telegram.WebApp;
  const body = document.body;
  const meta = (name) => {
    const el = document.querySelector('meta[name="' + name + '"]');
    return el ? el.content : '';
  };

  if (tg) {
    tg.ready();
    tg.expand();
  }

  // Telegramda hisob almashgan bo'lsa (cookie eski hisobniki), qayta kiramiz. Server baribir imzoni tekshiradi.
  const mine = meta('tg-user-id');
  const current = tg && tg.initDataUnsafe && tg.initDataUnsafe.user;
  if (mine && current && String(current.id) !== mine && !('bare' in body.dataset)) {
    window.location.replace(body.dataset.login);
    return;
  }

  if (tg && tg.initData && tg.BackButton) {
    if (body.dataset.back) {
      tg.BackButton.show();
      tg.BackButton.onClick(() => window.location.assign(body.dataset.back));
    } else {
      tg.BackButton.hide();
    }
  }

  function haptic(type) {
    if (tg && tg.initData && tg.HapticFeedback) tg.HapticFeedback.notificationOccurred(type);
  }

  const toast = document.getElementById('tma-toast');
  let timer;
  function say(message, isError) {
    if (!toast) return;
    toast.textContent = message;
    toast.classList.toggle('toast-error', Boolean(isError));
    toast.hidden = false;
    clearTimeout(timer);
    timer = setTimeout(() => { toast.hidden = true; }, 2500);
  }

  function badge(count) {
    const el = document.getElementById('tma-cart-count');
    if (!el) return;
    el.textContent = count;
    el.hidden = !count;
  }

  document.querySelectorAll('form[data-ajax]').forEach((form) => {
    form.addEventListener('submit', async (event) => {
      event.preventDefault();
      const button = form.querySelector('[type="submit"]');
      if (button.disabled) return;
      button.disabled = true;

      try {
        const res = await fetch(form.getAttribute('action'), {
          method: 'POST',
          credentials: 'same-origin',
          headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': meta('csrf-token') },
          body: new FormData(form),
        });
        if (res.status === 419) {
          window.location.reload();
          return;
        }
        const data = await res.json().catch(() => ({}));
        if (!res.ok || !data.cart) throw new Error(data.message || 'Something went wrong.');

        badge(data.cart.count);
        say(data.message || 'Added to cart');
        haptic('success');
      } catch (e) {
        say(e.message, true);
        haptic('error');
      } finally {
        button.disabled = false;
      }
    });
  });

  // Ikki marta bosilsa ikkinchi buyurtma ketmasin.
  document.querySelectorAll('form[data-once]').forEach((form) => {
    form.addEventListener('submit', () => {
      const button = form.querySelector('[type="submit"]');
      setTimeout(() => { button.disabled = true; }, 0);
    });
  });
  window.addEventListener('pageshow', (event) => {
    if (event.persisted) window.location.reload();
  });

  document.querySelectorAll('form[data-confirm]').forEach((form) => {
    form.addEventListener('submit', (event) => {
      event.preventDefault();
      const question = form.dataset.confirm;
      if (tg && tg.initData && tg.showConfirm && tg.isVersionAtLeast('6.2')) {
        tg.showConfirm(question, (yes) => { if (yes) form.submit(); });
      } else if (window.confirm(question)) {
        form.submit();
      }
    });
  });

  // Checkout: tanlangan yetkazish narxiga qarab jami summa (server qayta hisoblaydi).
  const total = document.getElementById('tma-total');
  if (total) {
    const render = () => {
      const picked = document.querySelector('input[name="delivery_method"]:checked');
      const fee = picked ? parseFloat(picked.dataset.fee || '0') : 0;
      total.textContent = '$' + (parseFloat(total.dataset.base) + fee).toFixed(2);
    };
    document.querySelectorAll('input[name="delivery_method"]').forEach((r) => r.addEventListener('change', render));
    render();
  }

  // Checkout: ixtiyoriy joylashuv (kuryer uchun koordinata).
  const locate = document.getElementById('tma-locate');
  if (locate) {
    const info = document.getElementById('tma-locate-info');
    locate.addEventListener('click', () => {
      if (!navigator.geolocation) {
        info.textContent = 'Location is not available on this device.';
        return;
      }
      locate.disabled = true;
      info.textContent = 'Locating...';
      navigator.geolocation.getCurrentPosition(
        (pos) => {
          document.getElementById('delivery_lat').value = pos.coords.latitude.toFixed(7);
          document.getElementById('delivery_lng').value = pos.coords.longitude.toFixed(7);
          info.textContent = 'Location added.';
          locate.disabled = false;
        },
        () => {
          info.textContent = 'Could not get your location. Allow access and try again.';
          locate.disabled = false;
        },
        { enableHighAccuracy: true, timeout: 10000 }
      );
    });
  }
})();
EOF

cat > public/css/tma.css <<'EOF'
:root {
  --bg: var(--tg-theme-bg-color, #0b0f0d);
  --card: var(--tg-theme-secondary-bg-color, #151c18);
  --text: var(--tg-theme-text-color, #eef5f1);
  --muted: var(--tg-theme-hint-color, #8d9a93);
  --accent: var(--tg-theme-button-color, #00f59b);
  --accent-text: var(--tg-theme-button-text-color, #04120b);
  --line: rgba(128, 128, 128, .25);
  --danger: #e5484d;
}
* { box-sizing: border-box; }
[hidden] { display: none !important; }
body { margin: 0; background: var(--bg); color: var(--text); font: 15px/1.45 -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; -webkit-tap-highlight-color: transparent; }
a { color: inherit; text-decoration: none; }
img { max-width: 100%; }
h1 { font-size: 22px; line-height: 1.25; margin: 4px 0 8px; }
h2 { font-size: 16px; margin: 20px 0 8px; }
.page { max-width: 640px; margin: 0 auto; padding: 12px 12px calc(84px + env(safe-area-inset-bottom)); }
.muted { color: var(--muted); }
.small { font-size: 13px; }
.center { text-align: center; padding: 56px 16px; }
.note { margin: 0 0 10px; padding: 10px 12px; border-radius: 12px; background: var(--card); border: 1px solid var(--accent); }
.note-error { border-color: var(--danger); }
.btn { display: inline-block; border: 0; border-radius: 12px; padding: 11px 16px; background: var(--accent); color: var(--accent-text); font: inherit; font-weight: 700; text-align: center; cursor: pointer; }
.btn:disabled { opacity: .5; }
.btn-block { display: block; width: 100%; margin-top: 12px; }
.btn-ghost { background: var(--card); color: var(--text); border: 1px solid var(--line); }
.btn-danger { background: transparent; color: var(--danger); border: 1px solid var(--danger); }
.btn-small { padding: 7px 10px; font-size: 13px; }
input, textarea, select { width: 100%; border: 1px solid var(--line); border-radius: 12px; padding: 11px 12px; background: var(--card); color: var(--text); font: inherit; font-size: 16px; }
input[type="radio"] { width: auto; accent-color: var(--accent); }
label { display: block; }
.field { margin-bottom: 10px; }
.field span { display: block; margin-bottom: 4px; color: var(--muted); font-size: 13px; }
.search { display: flex; gap: 8px; margin-bottom: 10px; }
.chips { display: flex; gap: 8px; overflow-x: auto; padding-bottom: 8px; scrollbar-width: none; }
.chips a { flex: none; padding: 7px 14px; border-radius: 999px; background: var(--card); border: 1px solid var(--line); font-size: 13px; }
.chips a.on { background: var(--accent); color: var(--accent-text); border-color: var(--accent); font-weight: 700; }
.grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 10px; }
.card { display: flex; flex-direction: column; gap: 6px; padding: 10px; border-radius: 16px; background: var(--card); min-width: 0; }
.thumb { display: flex; align-items: center; justify-content: center; aspect-ratio: 1; border-radius: 12px; overflow: hidden; background: var(--bg); font-size: 40px; }
.thumb img { width: 100%; height: 100%; object-fit: contain; }
.card h3 { margin: 0; font-size: 14px; overflow-wrap: anywhere; }
.card form { margin-top: auto; }
.card .btn { width: 100%; }
.price { font-weight: 800; }
.old { margin-left: 6px; color: var(--muted); text-decoration: line-through; font-weight: 400; font-size: 13px; }
.pager { display: flex; justify-content: space-between; gap: 8px; margin-top: 14px; }
.gallery { display: flex; gap: 8px; overflow-x: auto; scroll-snap-type: x mandatory; scrollbar-width: none; }
.gallery .thumb { flex: 0 0 100%; scroll-snap-align: center; aspect-ratio: 4 / 3; font-size: 72px; background: var(--card); }
.option { display: flex; align-items: center; gap: 10px; padding: 12px; margin-bottom: 8px; border-radius: 12px; background: var(--card); border: 1px solid var(--line); }
.option:has(input:checked) { border-color: var(--accent); }
.option .grow { flex: 1; min-width: 0; }
.line { display: flex; gap: 10px; padding: 10px; margin-bottom: 8px; border-radius: 16px; background: var(--card); }
.line .thumb { flex: 0 0 64px; height: 64px; font-size: 26px; }
.line .grow { flex: 1; min-width: 0; }
.stepper { display: flex; align-items: center; gap: 10px; margin-top: 8px; }
.stepper form { margin: 0; }
.stepper button { min-width: 36px; }
.row { display: flex; justify-content: space-between; gap: 12px; padding: 5px 0; }
.sep { border-top: 1px solid var(--line); margin-top: 6px; padding-top: 10px; }
.total { border-top: 1px solid var(--line); margin-top: 6px; padding-top: 10px; font-size: 18px; font-weight: 800; }
.status { display: inline-block; padding: 2px 10px; border-radius: 999px; border: 1px solid var(--line); font-size: 12px; font-weight: 700; }
.status-pending { color: #d99a00; }
.status-processing, .status-shipped { color: #3b9eff; }
.status-completed { color: #2fb36b; }
.status-cancelled { color: var(--danger); }
.order { display: block; padding: 12px; margin-bottom: 8px; border-radius: 16px; background: var(--card); }
.tabbar { position: fixed; left: 0; right: 0; bottom: 0; z-index: 10; display: flex; padding-bottom: env(safe-area-inset-bottom); background: var(--card); border-top: 1px solid var(--line); }
.tabbar a { position: relative; flex: 1; padding: 10px 4px; text-align: center; font-size: 12px; color: var(--muted); }
.tabbar a b { display: block; font-size: 19px; line-height: 1.3; }
.tabbar a.on { color: var(--text); font-weight: 700; }
.count { position: absolute; top: 5px; left: calc(50% + 8px); min-width: 18px; padding: 0 5px; border-radius: 999px; background: var(--accent); color: var(--accent-text); font-size: 11px; font-style: normal; font-weight: 800; line-height: 18px; }
.toast { position: fixed; left: 50%; bottom: calc(88px + env(safe-area-inset-bottom)); transform: translateX(-50%); z-index: 20; max-width: 90%; padding: 10px 16px; border-radius: 999px; background: var(--accent); color: var(--accent-text); font-weight: 700; font-size: 13px; text-align: center; }
.toast-error { background: var(--danger); color: #fff; }
EOF

cat > tests/Feature/TelegramAuthTest.php <<'EOF'
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
        $this->assertStringEndsWith('@telegram.invalid', $user->email);
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
EOF

patch_after routes/web.php "$ROUTES_ANCHOR" "
require __DIR__.'/tma.php';"

patch_after app/Providers/AppServiceProvider.php "$PROVIDER_ANCHOR" "

        // Tunnel yoki reverse proxy orqasida havolalar https bo'lib chiqsin (config/telegram.php).
        if (\$proxies = config('telegram.trusted_proxies')) {
            \\Illuminate\\Http\\Middleware\\TrustProxies::at(\$proxies);
        }"

if ! grep -qF "TELEGRAM_BOT_TOKEN=" .env.example; then
cat >> .env.example <<'EOF'

TELEGRAM_BOT_TOKEN=
TELEGRAM_WEBAPP_URL=
TRUSTED_PROXIES=
EOF
fi

echo "A qism qo'llandi."
