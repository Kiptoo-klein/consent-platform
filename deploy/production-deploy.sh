#!/usr/bin/env bash
set -Eeuo pipefail

PROJECT_ROOT="$(
    cd "$(dirname "${BASH_SOURCE[0]}")/.."
    pwd
)"

cd "$PROJECT_ROOT"

PHP_BIN="${PHP_BIN:-php}"
COMPOSER_BIN="${COMPOSER_BIN:-composer}"
RUN_FRONTEND_BUILD="${RUN_FRONTEND_BUILD:-1}"

MAINTENANCE_ENABLED=0

restore_access_on_failure() {
    local code=$?

    if [[ "$MAINTENANCE_ENABLED" -eq 1 ]]; then
        "$PHP_BIN" artisan up || true
    fi

    echo
    echo "Deployment failed with exit code $code."
    echo "Review deploy/ROLLBACK.md and the latest backup."
    exit "$code"
}

trap restore_access_on_failure ERR

echo "Running production-readiness checks..."
"$PHP_BIN" artisan production:check \
    --fail-on=critical

echo "Creating pre-deployment backup..."
"$PHP_BIN" artisan production:backup \
    --prune

echo "Enabling maintenance mode..."
"$PHP_BIN" artisan down \
    --retry=60 \
    --refresh=15

MAINTENANCE_ENABLED=1

if command -v "$COMPOSER_BIN" >/dev/null 2>&1; then
    echo "Installing production PHP dependencies..."

    "$COMPOSER_BIN" install \
        --no-dev \
        --prefer-dist \
        --no-interaction \
        --optimize-autoloader
fi

if [[ "$RUN_FRONTEND_BUILD" = "1" ]] \
    && [[ -f package.json ]] \
    && command -v npm >/dev/null 2>&1; then

    echo "Building frontend assets..."
    npm ci
    npm run build
fi

echo "Running database migrations..."

"$PHP_BIN" artisan migrate \
    --force \
    --isolated

echo "Building Laravel production caches..."
"$PHP_BIN" artisan optimize

echo "Restarting queue workers..."
"$PHP_BIN" artisan queue:restart

echo "Disabling maintenance mode..."
"$PHP_BIN" artisan up

MAINTENANCE_ENABLED=0
trap - ERR

echo
echo "Deployment completed successfully."
