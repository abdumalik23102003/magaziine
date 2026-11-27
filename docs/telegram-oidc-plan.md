# Telegram OIDC login: evaluation and migration plan

Source: https://core.telegram.org/widgets/login (checked 2026-11-27). The website currently uses the legacy Login Widget
(`TelegramWidgetController`, signature in `App\Support\TelegramLoginWidget`). Telegram documents a newer OpenID Connect login.

## What the new flow is (from Telegram's page)

- Authorization Code flow with PKCE (S256). Discovery: `https://oauth.telegram.org/.well-known/openid-configuration`.
- Endpoints: `https://oauth.telegram.org/auth`, `https://oauth.telegram.org/token`, JWKS `https://oauth.telegram.org/.well-known/jwks.json`.
- Client ID and Client Secret come from @BotFather (Login Widget, Allowed URLs must include the redirect URI).
- Token request authenticates with HTTP Basic (`client_id:client_secret`). No UserInfo endpoint.
- Scopes: `openid` (required), `profile` (id, name, username, picture), `phone`, `telegram:bot_access`.
- ID token: validate signature via JWKS, `iss` = `https://oauth.telegram.org`, `aud` = bot id, `exp`. Default RS256.
- Claims with `profile`: `id`, `name`, `preferred_username`, `picture`. `sub` is the user identifier.

## Not stated by Telegram (verify against a real bot before relying on it)

Redirect URI matching rules, error response formats, key rotation / `kid` use, code lifetime and single use,
whether `client_secret_post` is accepted, whether `id` equals `users.telegram_id` for the same person
(it should, but confirm with a real login before linking accounts on it).

## Recommendation

Keep the legacy widget for now: it works, it is covered by tests, and Telegram does not announce its removal on that page.
Add OIDC next to it behind a config flag, then switch the login button after a real-bot test.

## Plan

1. `config/telegram.php`: add `oidc.enabled`, `oidc.client_id` (bot id), `oidc.client_secret`.
2. Register the redirect URI (`APP_URL/auth/telegram/oidc/callback`) in @BotFather, Login Widget, Allowed URLs.
3. `TelegramOidcController::redirect`: random `state` and PKCE `code_verifier` in the session, redirect to `/auth` with
   `response_type=code`, `scope=openid profile`, `code_challenge`, `code_challenge_method=S256`.
4. `callback`: compare `state`, exchange the code at `/token` (Basic auth, `code_verifier`), then validate `id_token`.
   `firebase/php-jwt` is already installed as a Socialite dependency: `JWT::decode($idToken, JWK::parseKeySet($jwks))`
   with the JWKS cached for a few minutes; check `iss`, `aud`, `exp`.
5. Map claims to the array `TelegramAuthService::userFor()` already accepts (`id`, `first_name`, `username`), then `WebSignIn::login()`.
6. Tests with a locally generated RSA key pair and a faked JWKS (`Http::fake`): valid token, bad signature, wrong `aud`,
   expired token, wrong `state`, reused code.
7. Switch the button in `pages/auth/partials/alternatives.blade.php`; remove the legacy widget once OIDC is verified in production.

## Risks

- Needs a public HTTPS domain (same as the widget). The JS popup library requires relaxing `Cross-Origin-Opener-Policy`;
  the server-side redirect flow above avoids that.
- Clock skew and JWKS caching must be handled explicitly.
