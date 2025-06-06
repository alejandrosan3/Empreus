#!/usr/bin/env bash
set -euo pipefail

# 1. Ensure script is run as root
if [[ "$EUID" -ne 0 ]]; then
  echo "Please run as root (sudo)." >&2
  exit 1
fi

# 2. Install PHP 8.4 and extensions
echo "📦 Installing PHP 8.4 packages..."
apt-get update -y
DEBIAN_FRONTEND=noninteractive apt-get install -y \
  php8.4-cli php8.4-fpm php8.4-dev \
  php8.4-mysql php8.4-redis php8.4-gd \
  php8.4-curl php8.4-mbstring php8.4-xml \
  php8.4-zip php8.4-bcmath php8.4-intl \
  php8.4-readline php8.4-imagick php8.4-xdebug \
  unzip git curl

# 3. (Optional) Install alternate JS package managers
npm install -g pnpm bun || true

# 4. Start Redis in daemon mode
echo "🚀 Starting Redis..."
redis-server --daemonize yes

# 5. Install & start Meilisearch and Mailpit
echo "🔎 Installing Meilisearch..."
curl -sL https://github.com/meilisearch/meilisearch/releases/latest/download/meilisearch-linux-amd64 \
  -o /usr/local/bin/meilisearch && chmod +x /usr/local/bin/meilisearch
nohup meilisearch > /tmp/meilisearch.log 2>&1 &

echo "📧 Installing Mailpit..."
curl -sL https://raw.githubusercontent.com/axllent/mailpit/develop/install.sh | bash
nohup mailpit > /tmp/mailpit.log 2>&1 &

# 6. Prepare .env
if [[ ! -f .env ]]; then
  echo "⚙️ Creating .env from example..."
  cp .env.example .env
  sed -i 's/DB_HOST=mysql/DB_HOST=127.0.0.1/' .env
  sed -i 's/MAIL_PORT=2525/MAIL_PORT=1025/' .env
fi

# 7. Configure Composer auth for FluxUI
if [[ -n "${FLUX_USER:-}" && -n "${FLUX_PASS:-}" ]]; then
  echo "🔐 Setting Composer HTTP-Basic for FluxUI..."
  composer config --global http-basic.composer.fluxui.dev "$FLUX_USER" "$FLUX_PASS"
else
  echo "⚠️ FLUX_USER/FLUX_PASS missing; private packages may fail"
fi

# 8. Install PHP & JS dependencies
echo "📦 Installing PHP dependencies..."
composer install --no-interaction --prefer-dist

echo "📦 Installing JS dependencies..."
npm install

# 9. Permissions & APP_KEY
echo "🛠️ Setting permissions & generating APP_KEY..."
chmod -R 775 storage bootstrap/cache
php artisan key:generate --force

# 10. Migrate & seed
echo "📊 Running migrations & seeders..."
php artisan migrate --force
php artisan db:seed --class=TaskTypeSeeder --force
php artisan db:seed --class=StatusTypeSeeder --force
php artisan db:seed --force

# 11. Launch dev servers
echo "💻 Starting Laravel server (port 80) and Vite (5173)..."
nohup php artisan serve --host=0.0.0.0 --port=80 > /tmp/laravel.log 2>&1 &
nohup npm run dev > /tmp/vite.log 2>&1 &

echo "✅ setup.sh complete! Backend: http://localhost:80 | Frontend: http://localhost:5173"
