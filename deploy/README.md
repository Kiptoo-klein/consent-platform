# Consent Platform VPS Deployment

This deployment pack targets an Ubuntu or Debian VPS using:

- Nginx
- PHP-FPM with PHP 8.3 or newer
- PostgreSQL
- Supervisor
- Laravel's database queue
- Laravel's scheduler through cron

The application must be deployed outside the web root. Nginx must expose only
`APP_PATH/public`.

## Files

- `.env.production.example`: production environment template
- `nginx-consent-platform.conf.example`: Nginx virtual host
- `supervisor-consent-platform.conf.example`: queue worker
- `cron-consent-platform.txt`: scheduler entry
- `production-deploy.sh`: repeatable deployment command
- `PRODUCTION-LAUNCH-CHECKLIST.md`: launch procedure
- `ROLLBACK.md`: emergency code rollback
- `RESTORE-DATABASE.md`: verified backup restoration
- `verify-deployment-pack.sh`: local pack validation

## Required server software

Install these before the first deployment:

- Git
- Nginx
- PHP-FPM and PHP CLI 8.3 or newer
- PHP extensions required by `composer check-platform-reqs`
- Composer
- PostgreSQL client tools, including `pg_dump` and `psql`
- Supervisor
- cron
- `tar`
- Node.js and npm when frontend assets are built on the server

## Placeholder replacement

Never install the example files unchanged.

Replace:

- `APP_PATH` with the absolute application path
- `SERVER_NAME` with the live domain
- `PHP_FPM_SOCKET` with the installed PHP-FPM socket
- `PHP_BIN` with the absolute PHP CLI path
- `APP_USER` with the non-root application user

Run:

```bash
bash deploy/verify-deployment-pack.sh
```

before committing changes to this directory.

## First deployment

After creating the production database, cloning the repository, configuring
`.env`, and installing the Nginx, Supervisor, and cron definitions:

```bash
INITIAL_DEPLOY=1 \
HEALTHCHECK_URL=https://YOUR_DOMAIN/up \
bash deploy/production-deploy.sh
```

Start or reload Supervisor only after the deployment succeeds.

## Later deployments

```bash
HEALTHCHECK_URL=https://YOUR_DOMAIN/up \
bash deploy/production-deploy.sh
```

The script creates a verified backup before maintenance mode, applies isolated
migrations, builds caches, restarts queue workers, and restores access if a
command fails.
