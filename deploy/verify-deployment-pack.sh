#!/usr/bin/env bash
set -Eeuo pipefail

cd "$(
    cd "$(dirname "${BASH_SOURCE[0]}")/.."
    pwd
)"

FILES=(
    "deploy/.env.production.example"
    "deploy/cron-consent-platform.txt"
    "deploy/nginx-consent-platform.conf.example"
    "deploy/production-deploy.sh"
    "deploy/PRODUCTION-LAUNCH-CHECKLIST.md"
    "deploy/README.md"
    "deploy/RESTORE-DATABASE.md"
    "deploy/ROLLBACK.md"
    "deploy/supervisor-consent-platform.conf.example"
)

for file in "${FILES[@]}"; do
    [[ -s "$file" ]] \
        || {
            echo "Missing or empty deployment file: $file"
            exit 1
        }
done

bash -n deploy/production-deploy.sh
bash -n deploy/verify-deployment-pack.sh

grep -q '^APP_ENV=production$' \
    deploy/.env.production.example

grep -q '^APP_DEBUG=false$' \
    deploy/.env.production.example

grep -q '^DB_CONNECTION=pgsql$' \
    deploy/.env.production.example

grep -q '^QUEUE_CONNECTION=database$' \
    deploy/.env.production.example

grep -q '^SESSION_SECURE_COOKIE=true$' \
    deploy/.env.production.example

grep -q '^SECURITY_FORCE_HTTPS=true$' \
    deploy/.env.production.example

grep -q 'root APP_PATH/public;' \
    deploy/nginx-consent-platform.conf.example

grep -q 'fastcgi_pass unix:PHP_FPM_SOCKET;' \
    deploy/nginx-consent-platform.conf.example

grep -q 'queue:work database' \
    deploy/supervisor-consent-platform.conf.example

grep -q 'artisan schedule:run' \
    deploy/cron-consent-platform.txt

grep -q 'production:backup' \
    deploy/production-deploy.sh

grep -q 'artisan migrate' \
    deploy/production-deploy.sh

grep -q 'artisan optimize' \
    deploy/production-deploy.sh

grep -q 'artisan queue:restart' \
    deploy/production-deploy.sh

grep -q 'INITIAL_DEPLOY' \
    deploy/production-deploy.sh

if grep -Eq \
    'APP_KEY=base64:|DB_PASSWORD=[^[:space:]]{16,}|MAIL_PASSWORD=[^[:space:]]{16,}' \
    deploy/.env.production.example
then
    echo "Possible real secret detected in the production template."
    exit 1
fi

git diff --check

echo "Deployment pack validation passed."
