# Database Restore Procedure

Always restore into a separate test environment first.

Each backup set is stored below:

```text
storage/app/private/production-backups/backup-*
```

A PostgreSQL backup contains:

- `database.sql`
- optionally `private-files.tar.gz`
- `manifest.json` with SHA-256 checksums

## Verify the backup manifest

From the repository root:

```bash
BACKUP_DIRECTORY="storage/app/private/production-backups/BACKUP_DIRECTORY"

php -r '
$directory = $argv[1];
$manifest = json_decode(
    file_get_contents($directory."/manifest.json"),
    true,
    512,
    JSON_THROW_ON_ERROR
);

foreach ($manifest["files"] as $file) {
    $path = $directory."/".$file["name"];
    $actual = hash_file("sha256", $path);

    if (! hash_equals($file["sha256"], $actual)) {
        fwrite(STDERR, "Checksum failed: ".$file["name"].PHP_EOL);
        exit(1);
    }

    echo "Verified: ".$file["name"].PHP_EOL;
}
' "$BACKUP_DIRECTORY"
```

Do not continue if any checksum fails.

## PostgreSQL restore

1. Enable maintenance mode.
2. Stop the Supervisor queue worker.
3. Back up the current database.
4. Restore into a new empty database.
5. Point the application to the restored database.
6. Validate migrations and application health.

Example:

```bash
php artisan down --retry=60
sudo supervisorctl stop consent-platform-worker:*

createdb \
    --host=DB_HOST \
    --port=5432 \
    --username=DB_ADMIN_USER \
    RESTORED_DATABASE_NAME

psql \
    --host=DB_HOST \
    --port=5432 \
    --username=DB_ADMIN_USER \
    --dbname=RESTORED_DATABASE_NAME \
    --set=ON_ERROR_STOP=1 \
    --file="$BACKUP_DIRECTORY/database.sql"
```

Use environment variables or a protected PostgreSQL password file. Do not place
database passwords directly in shell history.

## Private application files

Extract outside the live directory first:

```bash
RESTORE_DIRECTORY="$(mktemp -d)"

tar -xzf \
    "$BACKUP_DIRECTORY/private-files.tar.gz" \
    -C "$RESTORE_DIRECTORY"
```

Inspect the extracted files before copying them into
`storage/app/private`. Preserve ownership and restrictive permissions.

## Final validation

```bash
php artisan optimize:clear
php artisan migrate:status
php artisan production:check
php artisan queue:restart
php artisan up

sudo supervisorctl start consent-platform-worker:*
```

Verify `/up`, a queued PDF job, email delivery, and the production heartbeat.
