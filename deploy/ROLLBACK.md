# Emergency Rollback

Prefer release directories or a verified Git revision so the previous code is
always available.

## Before rollback

Record:

- the current Git commit
- the previous known-good Git commit
- the latest verified backup directory
- the migrations introduced by the failed release

## Application code rollback

```bash
php artisan down --retry=60
php artisan queue:restart
php artisan optimize:clear

git switch --detach PREVIOUS_KNOWN_GOOD_COMMIT

composer install \
    --no-dev \
    --prefer-dist \
    --no-interaction \
    --optimize-autoloader

npm ci
npm run build

php artisan optimize
php artisan queue:restart
php artisan up
```

Do not run `git reset --hard` when the server contains unreviewed tracked
changes.

## Database rollback

Use Laravel migration rollback only when every affected migration has a tested,
non-destructive `down` method:

```bash
php artisan migrate:rollback \
    --step=NUMBER_OF_RELEASE_MIGRATIONS \
    --force
```

When rollback may lose or corrupt data, restore the verified pre-deployment
database backup instead.

## Failed deployment

`deploy/production-deploy.sh` attempts to disable maintenance mode whenever a
command fails. Confirm access manually:

```bash
php artisan up
php artisan production:check
```

Inspect the queue worker and Nginx logs before retrying.
