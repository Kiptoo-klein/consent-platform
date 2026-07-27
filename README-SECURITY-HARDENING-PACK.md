# Security Hardening Pack

This feature adds defense-in-depth protections to the consent platform.

## Enforced protections

- Public signing-station request throttling.
- Public consent request throttling.
- Strict signing-station token validation.
- Strict consent UUID validation.
- PNG signature format, size and dimension checks.
- Duplicate consent-submission protection.
- Cross-organization route-model protection.
- Trusted-host enforcement when configured.
- Security response headers.
- No-store caching for public consent pages.
- HSTS when running securely in production.
- Configurable Content Security Policy.
- Automated cleanup for stale public-station records.
- Failed-job and queue visibility.
- A Security Status dashboard.

## Security Status

Open:

`/security/status`

or select **Security Status** in the organization navigation.

## Production environment example

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://consent.example.com

SESSION_SECURE_COOKIE=true
SESSION_HTTP_ONLY=true
SESSION_SAME_SITE=lax

SECURITY_FORCE_HTTPS=true
SECURITY_ALLOWED_HOSTS=consent.example.com
SECURITY_CSP_MODE=enforce

QUEUE_CONNECTION=database
CACHE_STORE=database
```

Do not force HTTPS until the live TLS certificate works correctly.

## Cleanup

Preview:

```bash
php artisan security:cleanup --dry-run
```

Run:

```bash
php artisan security:cleanup
```

The command is scheduled daily at 02:15.

The server must run Laravel's scheduler every minute:

```cron
* * * * * cd /path/to/consent-platform && php artisan schedule:run >> /dev/null 2>&1
```
