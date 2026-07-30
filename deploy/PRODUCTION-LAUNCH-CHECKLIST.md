# Production Launch Checklist

## Server prerequisites

- [ ] A non-root application user owns the application directory.
- [ ] Nginx is installed.
- [ ] PHP-FPM and PHP CLI 8.3 or newer are installed.
- [ ] `composer check-platform-reqs` passes.
- [ ] Composer is installed.
- [ ] PostgreSQL and its client tools (`pg_dump`, `psql`) are installed.
- [ ] Supervisor is installed and enabled.
- [ ] cron is installed and enabled.
- [ ] `tar` is installed.
- [ ] Node.js and npm are installed when assets build on the server.

## Application

- [ ] The repository is cloned to the final `APP_PATH`.
- [ ] `.env` was created from `deploy/.env.production.example`.
- [ ] `.env` is not tracked by Git and is readable only by the application user.
- [ ] `APP_ENV=production`.
- [ ] `APP_DEBUG=false`.
- [ ] `APP_KEY` is generated and securely backed up.
- [ ] `APP_URL` contains the final HTTPS URL.
- [ ] `php artisan production:check` has no critical failures.
- [ ] `php artisan optimize` succeeds.
- [ ] All migrations are applied.

## PostgreSQL

- [ ] A dedicated database and least-privilege database user exist.
- [ ] `DB_CONNECTION=pgsql`.
- [ ] Database host, port, name, username, and password are configured.
- [ ] `DB_SSLMODE=require` is used when required by the provider.
- [ ] The server has a compatible `pg_dump` executable.

## Database and backups

- [ ] A verified database backup exists.
- [ ] Private consent files are included in backups.
- [ ] Backup retention has been tested.
- [ ] Restore procedure has been tested in a separate environment.
- [ ] Backup files are copied to storage outside the VPS.

## Nginx and HTTPS

- [ ] Every placeholder in the Nginx template was replaced.
- [ ] Nginx exposes only `APP_PATH/public`.
- [ ] The Nginx configuration passes `nginx -t`.
- [ ] The final domain resolves to the server.
- [ ] A trusted HTTPS certificate is installed.
- [ ] HTTP redirects to HTTPS.
- [ ] `/up` returns a successful response.

## Queue and scheduler

- [ ] Every placeholder in the Supervisor template was replaced.
- [ ] Supervisor keeps the queue worker running.
- [ ] The scheduler cron is installed for the application user.
- [ ] Scheduler heartbeat is current.
- [ ] Queue heartbeat is current.
- [ ] No unexpected failed jobs exist.

## Storage and permissions

- [ ] `storage` is writable by the application user and web-server group.
- [ ] `bootstrap/cache` is writable.
- [ ] `public/storage` points to `storage/app/public`.
- [ ] Sufficient free disk space is available.
- [ ] Log retention works.

## Security

- [ ] `SESSION_ENCRYPT=true`.
- [ ] `SESSION_SECURE_COOKIE=true`.
- [ ] `SECURITY_FORCE_HTTPS=true`.
- [ ] `SECURITY_ALLOWED_HOSTS` contains only the live hostnames.
- [ ] CSP starts in `report-only`.
- [ ] CSP is changed to `enforce` after compatibility verification.
- [ ] The completed `.env` is not committed or copied into support messages.

## Email

- [ ] A live SMTP or email API provider is configured.
- [ ] The sending domain is verified.
- [ ] SPF is valid.
- [ ] DKIM is valid.
- [ ] DMARC is valid.
- [ ] Signed PDF delivery works to Gmail, Outlook, and Yahoo.
- [ ] Invoice reminder delivery and retries work.
- [ ] Reply-To behavior works.

## Initial deployment

- [ ] Run `bash deploy/verify-deployment-pack.sh`.
- [ ] Run `INITIAL_DEPLOY=1 bash deploy/production-deploy.sh`.
- [ ] Install or reload Nginx after `nginx -t` succeeds.
- [ ] Install or reload Supervisor.
- [ ] Confirm the scheduler cron is running.
- [ ] Confirm the queue worker processes the heartbeat job.

## Final end-to-end tests

- [ ] Platform administrator can manage organizations.
- [ ] Organization users cannot access platform-only tools.
- [ ] A consent template can be published.
- [ ] A public kiosk consent can be completed.
- [ ] A signed PDF is generated.
- [ ] Delivery status is recorded.
- [ ] Kiosk abandonment is tracked.
- [ ] Cross-organization access is blocked.
- [ ] Invoice reminders reach every configured recipient once.
- [ ] Backup and recovery commands work.
