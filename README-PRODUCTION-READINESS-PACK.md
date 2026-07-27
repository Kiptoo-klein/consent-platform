# Production Readiness Pack

Platform-admin-only operational tooling for the consent platform.

## Included

- Production readiness dashboard.
- Application, database, storage and queue checks.
- Scheduler and queue-worker heartbeats.
- Verified database backups.
- Optional private-file backup archive.
- SHA-256 backup manifests.
- Backup, log and failed-job retention.
- Maintenance-mode controls with administrator bypass.
- Queue restart and production optimization controls.
- Production deployment script.
- Supervisor configuration example.
- Nginx configuration example.
- Scheduler cron entry.
- Production environment template.
- Database restore guide.
- Emergency rollback guide.
- Launch checklist.

## Platform page

`/platform/production-readiness`

## Commands

```bash
php artisan production:check
php artisan production:check --json
php artisan production:heartbeat
php artisan production:backup
php artisan production:backup --database-only
php artisan production:backup --prune
php artisan production:prune
php artisan production:prune --dry-run
```

## Local heartbeat testing

Terminal 1:

```bash
php artisan queue:work database \
    --queue=emails,default \
    --tries=3 \
    --backoff=60 \
    --timeout=120
```

Terminal 2:

```bash
php artisan schedule:work
```

## Scheduled tasks

- Heartbeat: every minute.
- Database and private-file backup: daily at 01:30.
- Retention cleanup: daily at 02:15.

The production server must run:

```cron
* * * * * cd /var/www/consent-platform && php artisan schedule:run >> /dev/null 2>&1
```

## Domain stage

Domain, HTTPS, secure cookies, SPF, DKIM, DMARC and live delivery
remain intentionally deferred.
