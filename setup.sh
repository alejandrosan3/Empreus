#!/usr/bin/env bash
set -euo pipefail

# 1. Must be root
if [[ "$EUID" -ne 0 ]]; then
  echo "Please run as root (sudo)." >&2
  exit 1
fi

# 2. Install PHP  & extensions
echo "📦 Installing PHP packages..."
apt-get update -y
DEBIAN_FRONTEND=noninteractive apt-get install -y \
  php-cli php-fpm php-dev \
  php-mysql php-redis php-gd \
  php-curl php-mbstring php-xml \
  php-zip php-bcmath php-intl \
  php-readline php-imagick php-xdebug

# 3. Install & daemonize Redis
echo "🚀 Installing & starting Redis..."
DEBIAN_FRONTEND=noninteractive apt-get install -y redis-server
redis-server --daemonize yes

# 4. Install & start Meilisearch + Mailpit
echo "🔎 Installing Meilisearch..."
curl -sL \
  https://github.com/meilisearch/meilisearch/releases/latest/download/meilisearch-linux-amd64 \
  -o /usr/local/bin/meilisearch && chmod +x /usr/local/bin/meilisearch
nohup meilisearch > /tmp/meilisearch.log 2>&1 &

echo "📧 Installing Mailpit..."
curl -sL https://raw.githubusercontent.com/axllent/mailpit/develop/install.sh | bash
nohup mailpit > /tmp/mailpit.log 2>&1 &

# 5. Prepare .env
if [[ ! -f .env ]]; then
  echo "⚙️ Creating .env..."
  cp .env.example .env
  sed -i 's/DB_HOST=mysql/DB_HOST=127.0.0.1/' .env
  sed -i 's/MAIL_PORT=2525/MAIL_PORT=1025/' .env
fi

# 6. Composer auth for FluxUI
if [[ -n "${FLUX_USER:-}" && -n "${FLUX_PASS:-}" ]]; then
  echo "🔐 Configuring Composer HTTP-Basic..."
  composer config --global http-basic.composer.fluxui.dev \
    "$FLUX_USER" "$FLUX_PASS"
else
  echo "⚠️  FLUX_USER/FLUX_PASS missing; private packages may fail"
fi

# 7. Install PHP & JS deps
echo "📦 Installing PHP dependencies..."
composer install --no-interaction --prefer-dist

echo "📦 Installing JS dependencies..."
npm install

# 8. Permissions & APP_KEY
echo "🛠️ Setting permissions & generating APP_KEY..."
chmod -R 775 storage bootstrap/cache
php artisan key:generate --force

# 9. Migrate & seed
echo "📊 Running migrations & seeders..."
php artisan migrate --force
php artisan db:seed --class=TaskTypeSeeder --force
php artisan db:seed --class=StatusTypeSeeder --force
php artisan db:seed --force

# 10. Launch dev servers
echo "💻 Starting Laravel & Vite servers..."
nohup php artisan serve --host=0.0.0.0 --port=80 > /tmp/laravel.log 2>&1 &
nohup npm run dev > /tmp/vite.log 2>&1 &

echo "✅ setup.sh done! Backend → http://localhost:80 • Frontend → http://localhost:5173"
