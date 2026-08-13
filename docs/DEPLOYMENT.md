# Deployment

## Prerequisites

- PHP 8.2+ with extensions: `bcmath`, `ctype`, `curl`, `dom`, `fileinfo`, `json`, `mbstring`, `openssl`, `pdo_mysql`, `tokenizer`, `xml`
- Composer 2
- Node.js 20+ (build assets)
- MySQL 8+
- Redis (recommended for cache/queue/session in production)

## Required environment (production)

Copy `.env.example` → `.env`, then set at minimum:

| Variable | Purpose |
|----------|---------|
| `APP_ENV=production` | Enables production guards |
| `APP_DEBUG=false` | Never expose stack traces |
| `APP_URL` | Public HTTPS origin (no trailing slash issues for OAuth/Paystack) |
| `APP_KEY` | `php artisan key:generate` |
| `DB_*` | MySQL connection |
| `PAYSTACK_PUBLIC_KEY` / `PAYSTACK_SECRET_KEY` | Live wallet deposits (required; simulated deposit is blocked in production) |
| `GOOGLE_CLIENT_ID` / `GOOGLE_CLIENT_SECRET` | Google OAuth (if enabled in UI) |
| `GOOGLE_REDIRECT_URI` | Must match Cloud Console exactly, e.g. `https://domain/auth/google/callback` |
| `MAIL_*` | Real SMTP/API mailer (not `log`) for password resets |
| `CYRA_ALLOW_DEMO_SEEDING` | Leave unset or `false` — request-time demo catalogs stay off in production |
| `MESSAGING_SMS_DRIVER` | `log` (default) or `termii` |
| `TERMII_API_KEY` / `TERMII_SENDER_ID` | Required when SMS driver is `termii` |

Optional but recommended: Redis for `CACHE_STORE`, `QUEUE_CONNECTION`, `SESSION_DRIVER`; `SESSION_SECURE_COOKIE=true` behind HTTPS.

## Build steps

```bash
git clone <repository-url> cyra-agrolink
cd cyra-agrolink
composer install --no-dev --optimize-autoloader
cp .env.example .env
php artisan key:generate
# configure .env (see table above)
php artisan migrate --force
# php artisan db:seed --force   # only for controlled demo/staging datasets — not routine production
npm ci
npm run build
php artisan storage:link
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
```

## Process supervision

Run these workers in production:

```bash
php artisan queue:work --tries=3 --timeout=90
php artisan schedule:work   # or system cron: * * * * * php artisan schedule:run
```

## Web server

Document root must point to `/public`.

### Apache (XAMPP / production)

Ensure `mod_rewrite` is enabled and `AllowOverride All` for the vhost.

### Nginx

```nginx
location / {
    try_files $uri $uri/ /index.php?$query_string;
}
```

## Payment webhooks

In the Paystack dashboard:

1. Callback URL: `{APP_URL}/wallet/paystack/callback`
2. Webhook URL: `{APP_URL}/webhooks/paystack` (CSRF-exempt)

After deploy, run a small test deposit in Paystack test mode before switching to live keys.

## Security checklist

- [ ] `APP_DEBUG=false`
- [ ] Strong `APP_KEY`
- [ ] HTTPS only
- [ ] Secure session cookies
- [ ] Rate limiting enabled
- [ ] Database user least privilege
- [ ] Secrets not committed
- [ ] Backups scheduled
- [ ] `CYRA_ALLOW_DEMO_SEEDING` unset or `false` in production
- [ ] `PAYSTACK_SECRET_KEY` / `PAYSTACK_PUBLIC_KEY` set for live wallet funding
- [ ] Paystack webhook + callback URLs configured
- [ ] Google OAuth redirect URI matches production `APP_URL`
- [ ] SMS driver configured (`log` or `termii`) — no silent “always delivered” fake SMS in production when Termii is set
