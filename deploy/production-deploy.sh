#!/usr/bin/env bash
set -Eeuo pipefail

PROJECT_ROOT="$(
    cd "$(dirname "${BASH_SOURCE[0]}")/.."
    pwd
)"

cd "$PROJECT_ROOT"

PHP_BIN="${PHP_BIN:-php}"
COMPOSER_BIN="${COMPOSER_BIN:-composer}"
NPM_BIN="${NPM_BIN:-npm}"

RUN_FRONTEND_BUILD="${RUN_FRONTEND_BUILD:-1}"
INITIAL_DEPLOY="${INITIAL_DEPLOY:-0}"
REQUIRE_CLEAN_GIT="${REQUIRE_CLEAN_GIT:-1}"
HEALTHCHECK_URL="${HEALTHCHECK_URL:-}"

MAINTENANCE_ENABLED=0

restore_access_on_failure() {
    local code=$?

    if [[ "$MAINTENANCE_ENABLED" -eq 1 ]]; then
        "$PHP_BIN" artisan up || true
    fi

    echo
    echo "Deployment failed with exit code $code."
    echo "Review deploy/ROLLBACK.md and the latest verified backup."
    exit "$code"
}

trap restore_access_on_failure ERR

fail() {
    echo "Stopped: $1" >&2
    exit 1
}

require_command() {
    command -v "$1" >/dev/null 2>&1 \
        || fail "required executable was not found: $1"
}

install_php_dependencies() {
    require_command "$COMPOSER_BIN"

    "$COMPOSER_BIN" install \
        --no-dev \
        --prefer-dist \
        --no-interaction \
        --optimize-autoloader
}

assert_production_runtime() {
    local runtime

    runtime="$(
        "$PHP_BIN" <<'PHP'
<?php

require __DIR__.'/vendor/autoload.php';

$app = require __DIR__.'/bootstrap/app.php';

$kernel = $app->make(
    Illuminate\Contracts\Console\Kernel::class
);

$kernel->bootstrap();

echo app()->environment(), PHP_EOL;
echo config('app.debug') ? 'debug' : 'no-debug', PHP_EOL;
PHP
    )"

    local environment
    local debug_state

    environment="$(sed -n '1p' <<< "$runtime")"
    debug_state="$(sed -n '2p' <<< "$runtime")"

    [[ "$environment" == "production" ]] \
        || fail "runtime APP_ENV must be production."

    [[ "$debug_state" == "no-debug" ]] \
        || fail "runtime APP_DEBUG must be false."
}

if [[ "$(id -u)" -eq 0 ]]; then
    fail "run deployments as the application user, not root."
fi

[[ -f .env ]] \
    || fail "the production .env file is missing."

[[ -f composer.json ]] \
    || fail "composer.json is missing."

[[ -f artisan ]] \
    || fail "artisan is missing."

require_command "$PHP_BIN"

if [[ "$REQUIRE_CLEAN_GIT" == "1" ]] \
    && command -v git >/dev/null 2>&1
then
    if [[ -n "$(git status --porcelain --untracked-files=no)" ]]; then
        git status --short
        fail "tracked repository changes are present."
    fi
fi

if [[ ! -f vendor/autoload.php ]]; then
    echo "Installing initial production PHP dependencies..."
    install_php_dependencies
fi

assert_production_runtime

if [[ "$INITIAL_DEPLOY" != "1" ]]; then
    echo "Running pre-deployment production checks..."

    "$PHP_BIN" artisan production:check \
        --fail-on=critical
else
    echo "Initial deployment mode: pre-migration readiness checks are deferred."
fi

echo "Creating verified pre-deployment backup..."

"$PHP_BIN" artisan production:backup \
    --prune

echo "Enabling maintenance mode..."

"$PHP_BIN" artisan down \
    --retry=60 \
    --refresh=15

MAINTENANCE_ENABLED=1

echo "Installing production PHP dependencies..."
install_php_dependencies

if [[ "$RUN_FRONTEND_BUILD" == "1" ]] \
    && [[ -f package.json ]]
then
    require_command "$NPM_BIN"

    [[ -f package-lock.json ]] \
        || fail "package-lock.json is required for npm ci."

    echo "Installing locked frontend dependencies..."
    "$NPM_BIN" ci

    echo "Building frontend assets..."
    "$NPM_BIN" run build

    [[ -f public/build/manifest.json ]] \
        || fail "Vite manifest was not generated."
fi

echo "Running isolated database migrations..."

"$PHP_BIN" artisan migrate \
    --force \
    --isolated

echo "Ensuring the public storage link exists..."

"$PHP_BIN" artisan storage:link \
    --force

echo "Building Laravel production caches..."

"$PHP_BIN" artisan optimize

echo "Restarting queue workers..."

"$PHP_BIN" artisan queue:restart

echo "Running post-deployment production checks..."

"$PHP_BIN" artisan production:check \
    --fail-on=critical

if [[ -n "$HEALTHCHECK_URL" ]]; then
    require_command curl

    echo "Checking application health endpoint..."

    curl \
        --fail \
        --silent \
        --show-error \
        --retry 5 \
        --retry-delay 2 \
        --max-time 20 \
        "$HEALTHCHECK_URL" \
        >/dev/null
fi

echo "Disabling maintenance mode..."

"$PHP_BIN" artisan up

MAINTENANCE_ENABLED=0
trap - ERR

echo
echo "Deployment completed successfully."
