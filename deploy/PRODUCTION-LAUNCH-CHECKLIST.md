# Production Launch Checklist

## Application

- [ ] APP_ENV is production.
- [ ] APP_DEBUG is false.
- [ ] APP_KEY is present.
- [ ] `php artisan production:check` has no critical failures.
- [ ] `php artisan optimize` succeeds.
- [ ] All migrations are applied.

## Database and backups

- [ ] A verified database backup exists.
- [ ] Private consent files are included in backups.
- [ ] Backup retention has been tested.
- [ ] Restore procedure has been tested in a separate environment.

## Queue and scheduler

- [ ] Supervisor keeps the queue worker running.
- [ ] The scheduler cron runs every minute.
- [ ] Scheduler heartbeat is current.
- [ ] Queue heartbeat is current.
- [ ] No unexpected failed jobs exist.

## Storage

- [ ] storage is writable by the web-server user.
- [ ] bootstrap/cache is writable.
- [ ] public storage link exists when required.
- [ ] Sufficient free disk space is available.
- [ ] Log retention works.

## Domain and HTTPS — later

- [ ] Final domain is connected.
- [ ] HTTPS certificate is installed.
- [ ] APP_URL uses HTTPS.
- [ ] SESSION_SECURE_COOKIE is true.
- [ ] SECURITY_FORCE_HTTPS is true.
- [ ] Trusted host is configured.
- [ ] CSP is changed from report-only to enforce after testing.

## Email — later

- [ ] Sending domain is verified.
- [ ] SMTP or email API credentials are configured.
- [ ] SPF is valid.
- [ ] DKIM is valid.
- [ ] DMARC is valid.
- [ ] Signed PDF delivery works to Gmail, Outlook and Yahoo.
- [ ] Reply-To behavior works.
- [ ] Failed delivery and retry behavior works.

## Final end-to-end tests

- [ ] Platform administrator can manage organizations.
- [ ] Organization users cannot access platform-only tools.
- [ ] A consent template can be published.
- [ ] A public kiosk consent can be completed.
- [ ] A signed PDF is generated.
- [ ] Delivery status is recorded.
- [ ] Kiosk abandonment is tracked.
- [ ] Cross-organization access is blocked.
- [ ] Backup and recovery commands work.
