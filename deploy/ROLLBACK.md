# Emergency Rollback

## Application code rollback

Keep the previous release directory or Git revision available.

1. Enable maintenance mode.
2. Stop or pause the queue worker.
3. Restore the previous application release.
4. Restore the pre-deployment database backup when the migration
   cannot safely be reversed.
5. Rebuild Laravel caches.
6. Restart queue workers.
7. Disable maintenance mode.

```bash
php artisan down --retry=60
php artisan optimize:clear

# Restore previous code release here.

php artisan optimize
php artisan queue:restart
php artisan up
```

## Migration rollback

Only use Laravel migration rollback when the migration's `down`
operation is known to be safe:

```bash
php artisan migrate:rollback --step=1 --force
```

For destructive or uncertain migrations, restore the verified
pre-deployment database backup instead.

## Failed deployment

The deployment script automatically attempts to disable maintenance
mode when a command fails. Confirm with:

```bash
php artisan up
```
