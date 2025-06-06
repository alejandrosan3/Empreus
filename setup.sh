#!/usr/bin/env bash
set -euo pipefail

# 1. Ensure script is run as root (or with sudo privileges)
if [[ "$EUID" -ne 0 ]]; then
  echo "Please run this script as root (e.g., using sudo)." >&2
  exit 1
fi

# 2. Update package lists and install base dependencies
echo "🔄 Updating package lists and installing base packages..."
apt-get update -y 
DEBIAN_FRONTEND=noninteractive apt-get install -y \
  software-properties-common curl ca-certificates lsb-release gnupg \
  unzip zip git build-essential

# 3. Add PHP 8.4 PPA (Ondrej) and NodeSource repository
echo "📥 Adding PHP 8.4 and Node.js (v22) repositories..."
add-apt-repository -y ppa:ondrej/php
curl -fsSL https://deb.nodesource.com/setup_22.x | bash -

# Add Yarn repo (optional—you can skip if Corepack is disabled)
curl -sS https://dl.yarnpkg.com/debian/pubkey.gpg \
  | gpg --dearmor -o /usr/share/keyrings/yarnkey.gpg
echo "deb [signed-by=/usr/share/keyrings/yarnkey.gpg] \
  https://dl.yarnpkg.com/debian stable main" \
  > /etc/apt/sources.list.d/yarn.list

# 4. Install PHP, Node.js, MySQL, Redis, etc.
echo "📦 Installing PHP, Node.js, MySQL, Redis, and other tools..."
apt-get update -y
DEBIAN_FRONTEND=noninteractive apt-get install -y \
  php8.4-cli php8.4-common php8.4-fpm php8.4-dev \
  php8.4-mysql php8.4-redis php8.4-gd php8.4-curl \
  php8.4-mbstring php8.4-xml php8.4-zip php8.4-bcmath \
  php8.4-intl php8.4-readline php8.4-imagick php8.4-xdebug \
  nodejs yarn mysql-server redis-server ffmpeg sqlite3

# (Optional) install alternate JS package managers
npm install -g pnpm bun || true

# 5. Configure MySQL: set up root no-password and create DBs
echo "🗄️ Configuring MySQL..."
# Try to start via service; if that fails, fallback to mysqld_safe
if ! service mysql start; then
  echo "   → service mysql failed, using mysqld_safe (background)…"
  mysqld_safe --user=mysql --datadir=/var/lib/mysql \
    > /tmp/mysql.log 2>&1 &
  sleep 5
else
  sleep 5
fi

mysql --user=root <<EOF
ALTER USER 'root'@'localhost' IDENTIFIED WITH mysql_native_password BY '';
FLUSH PRIVILEGES;
CREATE DATABASE IF NOT EXISTS laravel;
CREATE DATABASE IF NOT EXISTS testing;
EOF

# 6. Start Redis
echo "🚀 Starting Redis…"
service redis-server start || /etc/init.d/redis-server start || true
sleep 2

# 7. Install & start Meilisearch and Mailpit
echo "🔎 Installing Meilisearch…"
curl -L https://github.com/meilisearch/meilisearch/releases/latest/download/meilisearch-linux-amd64 \
  -o /usr/local/bin/meilisearch
chmod +x /usr/local/bin/meilisearch
nohup meilisearch > /tmp/meilisearch.log 2>&1 &

echo "📧 Installing Mailpit…"
curl -sL https://raw.githubusercontent.com/axllent/mailpit/develop/install.sh | bash
nohup mailpit > /tmp/mailpit.log 2>&1 &

# 8. Copy & patch .env if needed
if [[ ! -f .env ]]; then
  echo "⚙️  Creating .env from example…"
  cp .env.example .env
  sed -i 's/DB_HOST=mysql/DB_HOST=127.0.0.1/' .env
  sed -i 's/MAIL_PORT=2525/MAIL_PORT=1025/' .env
fi

# 9. Configure Composer HTTP-Basic for FluxUI if vars set
if [[ -n "${FLUX_USER:-}" && -n "${FLUX_PASS:-}" ]]; then
  echo "🔐 Setting Composer auth for FluxUI…"
  composer config --global http-basic.composer.fluxui.dev \
    "$FLUX_USER" "$FLUX_PASS"
else
  echo "⚠️  FLUX_USER/FLUX_PASS missing; private packages may fail"
fi

# 10. Install PHP & JS dependencies
echo "📦 Installing PHP dependencies…"
composer install --no-interaction --prefer-dist

echo "📦 Installing JS dependencies…"
npm install

# 11. Permissions & key
echo "🛠️ Setting permissions on storage & cache…"
chmod -R 775 storage bootstrap/cache || true

echo "🔑 Generating APP_KEY…"
php artisan key:generate --force

# 12. Migrate & seed
echo "📊 Migrating database…"
php artisan migrate --force

echo "🌱 Seeding reference data…"
php artisan db:seed --class=TaskTypeSeeder --force
php artisan db:seed --class=StatusTypeSeeder --force

echo "🌱 Seeding dummy data…"
php artisan db:seed --force

# 13. Launch dev servers
echo "💻 Starting Laravel server on port 80…"
nohup php artisan serve --host=0.0.0.0 --port=80 > /tmp/laravel.log 2>&1 &

echo "💻 Starting Vite dev server on port 5173…"
nohup npm run dev > /tmp/vite.log 2>&1 &

echo "✅ Setup complete! Visit http://localhost (backend) and port 5173 (frontend)."
