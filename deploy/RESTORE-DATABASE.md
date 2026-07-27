# Database Restore Procedure

Always restore into a separate test environment first.

## Before restoring

```bash
php artisan down
php artisan queue:restart
```

Copy the selected backup directory somewhere safe before changing it.

Each backup directory contains a `manifest.json` file with SHA-256
checksums. Verify the files before restoration:

```bash
cd storage/app/private/production-backups/BACKUP_DIRECTORY
sha256sum database.sqlite
```

Compare the result with `manifest.json`.

## SQLite

1. Stop queue workers.
2. Back up the current database file.
3. Replace it with `database.sqlite` from the selected backup.
4. Correct ownership and permissions.
5. Clear cached configuration.

Example:

```bash
cp database/database.sqlite database/database-before-restore.sqlite
cp storage/app/private/production-backups/BACKUP_DIRECTORY/database.sqlite database/database.sqlite
chmod 600 database/database.sqlite

php artisan optimize:clear
php artisan migrate:status
php artisan up
```

## MySQL or MariaDB

Create a fresh empty database and import:

```bash
mysql -u DATABASE_USER -p DATABASE_NAME < database.sql
```

## PostgreSQL

Create a fresh empty database and import:

```bash
psql -U DATABASE_USER -d DATABASE_NAME < database.sql
```

## Private application files

Restore the archive outside the live directory first:

```bash
mkdir /tmp/consent-private-restore

tar -xzf private-files.tar.gz \
    -C /tmp/consent-private-restore
```

Inspect the extracted files before copying them into
`storage/app/private`.

## Final validation

```bash
php artisan optimize:clear
php artisan production:check
php artisan queue:restart
php artisan up
```
