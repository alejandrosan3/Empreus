#!/usr/bin/env bash
set -euo pipefail

# STEP 0: Ensure script is run as root
if [[ "$EUID" -ne 0 ]]; then
  echo "Please run as root (sudo)." >&2
  exit 1
fi

echo "STEP 1: Prevent apt from auto-starting services (policy-rc.d hack)"
cat << 'EOF' > /usr/sbin/policy-rc.d
#!/bin/sh
exit 101
EOF
chmod +x /usr/sbin/policy-rc.d

# STEP 2: Update & install base dependencies
echo "STEP 2: Installing base packages..."
apt-get update -y
DEBIAN_FRONTEND=noninteractive apt-get install -y \
  software-properties-common curl ca-certificates lsb-release gnupg \
  unzip zip git build-essential

# STEP 3: Add PHP & Node.js repositories
echo "STEP 3: Adding PHP PPA and NodeSource..."
add-apt-repository -y ppa:ondrej/php
curl -fsSL https://deb.nodesource.com/setup_22.x | bash -
# Yarn repo (optional)
curl -sS https://dl.yarnpkg.com/debian/pubkey.gpg \
  | gpg --dearmor -o /usr/share/keyrings/yarnkey.gpg
echo "deb [signed-by=/usr/share/keyrings/yarnkey.gpg] https://dl.yarnpkg.com/debian stable main" \
  > /etc/apt/sources.list.d/yarn.list

# STEP 4: Install system packages
echo "STEP 4: Installing PHP, Node.js, MySQL, Redis..."
apt-get update -y
DEBIAN_FRONTEND=noninteractive apt-get install -y \
  php8.4-cli php8.4-common php8.4-redis php8.4-gd php8.4-curl \
  php8.4-mbstring php8.4-xml php8.4-zip php8.4-bcmath \
  php8.4-intl php8.4-readline php8.4-imagick php8.4-xdebug \
  nodejs yarn mysql-server redis-server ffmpeg sqlite3

# (Optional) alternate JS package managers
npm install -g pnpm bun || true

# STEP 5: Remove policy-rc.d hack (allow manual starts)
echo "STEP 5: Cleaning up policy-rc.d hack..."
rm -f /usr/sbin/policy-rc.d

# STEP 6: Start MySQL manually
echo "STEP 6: Starting MySQL in background..."
nohup mysqld_safe --user=mysql --datadir=/var/lib/mysql > /tmp/mysql.log 2>&1 &
sleep 5
echo "MySQL should now be running"

# STEP 7: Configure MySQL
echo "STEP 7: Configuring MySQL root user and creating DBs..."
mysql --user=root <<EOF
ALTER USER 'root'@'localhost' IDENTIFIED WITH mysql_native_password BY '';
FLUSH PRIVILEGES;
CREATE DATABASE IF NOT EXISTS laravel;
CREATE DATABASE IF NOT EXISTS testing;
EOF

# STEP 8: Start Redis manually
echo "STEP 8: Starting Redis in background..."
nohup redis-server > /tmp/redis.log 2>&1 &
sleep 2
echo "Redis should now be running"

# STEP 9: Install & start Meilisearch and Mailpit
echo "STEP 9: Installing Meilisearch and Mailpit..."
curl -L https://github.com/meilisearch/meilisearch/releases/latest/download/meilisearch-linux-amd64 \
  -o /usr/local/bin/meilisearch
chmod +x /usr/local/bin/meilisearch
nohup meilisearch > /tmp/meilisearch.log 2>&1 &

curl -sL https://raw.githubusercontent.com/axllent/mailpit/develop/install.sh | bash
nohup mailpit > /tmp/mailpit.log 2>&1 &
echo "Meilisearch & Mailpit started"

# STEP 10: Setup .env
echo "STEP 10: Creating .env if missing..."
if [[ ! -f .env ]]; then
  cp .env.example .env
  sed -i 's/DB_HOST=mysql/DB_HOST=127.0.0.1/' .env
  sed -i 's/MAIL_PORT=2525/MAIL_PORT=1025/' .env
fi

# STEP 11: Configure Composer HTTP-Basic (Flux)
echo "STEP 11: Setting Composer credentials..."
if [[ -n "${FLUX_USER:-}" && -n "${FLUX_PASS:-}" ]]; then
  composer config --global http-basic.composer.fluxui.dev "$FLUX_USER" "$FLUX_PASS"
else
  echo "⚠️ FLUX_USER/FLUX_PASS not set; private packages may fail"
fi

# STEP 12: Install PHP & JS dependencies
echo "STEP 12: Running composer install..."
composer install --no-interaction --prefer-dist

echo "STEP 13: Running npm install..."
npm install

# STEP 14: Permissions and App Key
echo "STEP 14: Setting permissions and generating APP_KEY..."
chmod -R 775 storage bootstrap/cache
php artisan key:generate --force

# STEP 15: Migrate & Seed
echo "STEP 15: Migrating database..."
php artisan migrate --force

echo "STEP 16: Seeding database..."
php artisan db:seed --class=TaskTypeSeeder --force
php artisan db:seed --class=StatusTypeSeeder --force
php artisan db:seed --force

# STEP 17: Launch Dev Servers
echo "STEP 17: Starting dev servers..."
nohup php artisan serve --host=0.0.0.0 --port=80 > /tmp/laravel.log 2>&1 &
nohup npm run dev > /tmp/vite.log 2>&1 &

echo "✅ Setup complete! Visit http://localhost (backend) and http://localhost:5173 (frontend)."
