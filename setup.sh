#!/usr/bin/env bash
set -euo pipefail

# 1. Ensure script is run as root
if [[ "$EUID" -ne 0 ]]; then
  echo "Please run as root (sudo)." >&2
  exit 1
fi

# 2. Override env and .env for DB & cache
echo "🔧 Configuring environment variables..."
# Export for this session
export DB_HOST=127.0.0.1
export DB_PORT=3306
export DB_DATABASE=laravel
export DB_USERNAME=root
export DB_PASSWORD=""
export DB_CONNECTION=mysql
export REDIS_HOST=127.0.0.1
export QUEUE_CONNECTION=redis
# Also patch .env file
if [[ -f .env ]]; then
  sed -i 's/^DB_HOST=.*/DB_HOST=127.0.0.1/' .env
  sed -i 's/^DB_PORT=.*/DB_PORT=3306/' .env
  sed -i 's/^DB_DATABASE=.*/DB_DATABASE=laravel/' .env
  sed -i 's/^DB_USERNAME=.*/DB_USERNAME=root/' .env
  sed -i 's|^DB_PASSWORD=.*|DB_PASSWORD=|' .env
  sed -i 's/^DB_CONNECTION=.*/DB_CONNECTION=mysql/' .env
  sed -i 's/^REDIS_HOST=.*/REDIS_HOST=127.0.0.1/' .env
  sed -i 's/^QUEUE_CONNECTION=.*/QUEUE_CONNECTION=redis/' .env
else
  cp .env.example .env
  sed -i 's/^DB_HOST=.*/DB_HOST=127.0.0.1/' .env
  sed -i 's/^DB_PORT=.*/DB_PORT=3306/' .env
  sed -i 's/^DB_DATABASE=.*/DB_DATABASE=laravel/' .env
  sed -i 's/^DB_USERNAME=.*/DB_USERNAME=root/' .env
  sed -i 's|^DB_PASSWORD=.*|DB_PASSWORD=|' .env
  sed -i 's/^DB_CONNECTION=.*/DB_CONNECTION=mysql/' .env
  sed -i 's/^REDIS_HOST=.*/REDIS_HOST=127.0.0.1/' .env
  sed -i 's/^QUEUE_CONNECTION=.*/QUEUE_CONNECTION=redis/' .env
  sed -i 's/^MAIL_PORT=.*/MAIL_PORT=1025/' .env
fi

# Ensure APP_KEY placeholder exists
if ! grep -q '^APP_KEY=' .env; then
  echo "APP_KEY=" >> .env
fi

# 3. Start MySQL if not already running
echo "🚀 Checking & starting MySQL..."
if ! mysqladmin ping --silent; then
  # Initialize if first run (ignore errors if already initialized)
  mysqld --initialize-insecure --user=mysql 2>/dev/null || true
  mysqld --daemonize --user=mysql \
        --socket=/var/run/mysqld/mysqld.sock \
        --pid-file=/var/run/mysqld/mysqld.pid
  until mysqladmin --socket=/var/run/mysqld/mysqld.sock ping --silent; do sleep 1; done
  echo "✅ MySQL is up"
else
  echo "✅ MySQL already running"
fi

# 4. Install PHP and extensions
echo "📦 Installing PHP and required extensions..."
apt-get update -y
DEBIAN_FRONTEND=noninteractive apt-get install -y \
  php-cli php-fpm php-dev \

echo "📦 Installing PHP and required extensions..."
apt-get update -y
DEBIAN_FRONTEND=noninteractive apt-get install -y \
  php-cli php-fpm php-dev \
  php-mysql php-redis php-gd php-curl \
  php-mbstring php-xml php-zip php-bcmath \
  php-intl php-readline php-imagick php-xdebug \
  unzip git curl

# 4. Install & start Redis
echo "🚀 Starting Redis-server..."
apt-get install -yqq redis-server
redis-server --daemonize yes

# 5. Install & launch Meilisearch and Mailpit
echo "🔎 Installing Meilisearch..."
curl -sL https://github.com/meilisearch/meilisearch/releases/latest/download/meilisearch-linux-amd64 \
  -o /usr/local/bin/meilisearch && chmod +x /usr/local/bin/meilisearch
nohup meilisearch > /tmp/meilisearch.log 2>&1 &

echo "📧 Installing Mailpit..."
curl -sL https://raw.githubusercontent.com/axllent/mailpit/develop/install.sh | bash
nohup mailpit > /tmp/mailpit.log 2>&1 &

# 6. Configure Composer auth for FluxUI
echo "🔐 Configuring Composer HTTP Basic auth..."
if [[ -n "${FLUX_USER:-}" && -n "${FLUX_PASS:-}" ]]; then
  composer config --global http-basic.composer.fluxui.dev "$FLUX_USER" "$FLUX_PASS"
else
  echo "⚠️  FLUX_USER/FLUX_PASS missing; private packages may fail"
fi

# 7. Install dependencies
echo "📦 Installing PHP dependencies with Composer..."
composer install --no-interaction --prefer-dist

echo "📦 Installing JS dependencies with npm..."
npm install

# 8. Set permissions & generate app key
echo "🛠️ Setting storage/cache permissions and generating APP_KEY..."
chmod -R 775 storage bootstrap/cache
php artisan key:generate --force

# 9. Run migrations & seeders
echo "📊 Running migrations & seeders..."
php artisan migrate --force
php artisan db:seed --class=TaskTypeSeeder --force
php artisan db:seed --class=StatusTypeSeeder --force
php artisan db:seed --force

# 10. Launch development servers
echo "💻 Starting Laravel and Vite development servers..."
nohup php artisan serve --host=0.0.0.0 --port=80 > /tmp/laravel.log 2>&1 &
nohup npm run dev > /tmp/vite.log 2>&1 &

echo "✅ setup.sh complete! Backend → http://localhost:80 | Frontend → http://localhost:5173"
