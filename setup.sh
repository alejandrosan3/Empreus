#!/usr/bin/env bash
set -euo pipefail

# check network connectivity
if ! curl -I https://composer.fluxui.dev >/dev/null 2>&1; then
  echo "Network is unreachable. Please ensure internet access." >&2
  exit 1
fi

# install base packages
apt-get update
apt-get install -y php php-xml composer nodejs npm

# copy environment file if not present
if [ ! -f .env ]; then
  cp .env.example .env
fi

# use sqlite for local database
sed -i 's/^DB_CONNECTION=.*/DB_CONNECTION=sqlite/' .env
sed -i 's/^DB_HOST=.*/DB_HOST=/' .env
sed -i 's/^DB_PORT=.*/DB_PORT=/' .env
sed -i 's#^DB_DATABASE=.*#DB_DATABASE=database/database.sqlite#' .env
sed -i 's/^DB_USERNAME=.*/DB_USERNAME=/' .env
sed -i 's/^DB_PASSWORD=.*/DB_PASSWORD=/' .env

# create sqlite database file
mkdir -p database
[ -f database/database.sqlite ] || touch database/database.sqlite

# configure composer credentials for Flux packages
FLUX_USER="${FLUX_USER:-alejandrosan3@gmail.com}"
FLUX_PASS="${FLUX_PASS:-fcc36e72-5f5d-4402-98f7-8c5d8f7264e2}"
composer config --global http-basic.composer.fluxui.dev "$FLUX_USER" "$FLUX_PASS"

# install PHP dependencies
composer install --no-interaction || {
  echo "composer install failed. Some packages may require credentials." >&2
  exit 1
}

# generate application key and run migrations
php artisan key:generate --force
php artisan migrate --force

# install JS dependencies
npm install

