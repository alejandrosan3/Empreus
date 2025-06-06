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
DEBIAN_FRONTEND=noninteractive apt-get install -y software-properties-common curl ca-certificates lsb-release gnupg unzip zip git

# 3. Add PHP 8.4 PPA (Ondrej) and NodeSource repository
echo "📥 Adding PHP 8.4 and Node.js (v22) repositories..."
add-apt-repository -y ppa:ondrej/php
curl -fsSL https://deb.nodesource.com/setup_22.x | bash -   # Adds NodeSource repo for Node.js 22

# Add Yarn repository key and list
curl -sS https://dl.yarnpkg.com/debian/pubkey.gpg | gpg --dearmor -o /usr/share/keyrings/yarnkey.gpg
echo "deb [signed-by=/usr/share/keyrings/yarnkey.gpg] https://dl.yarnpkg.com/debian stable main" > /etc/apt/sources.list.d/yarn.list

# 4. Install PHP 8.4, Node.js, and other system packages
echo "📦 Installing PHP 8.4, Node.js, MySQL, Redis, and other tools..."
apt-get update -y
DEBIAN_FRONTEND=noninteractive apt-get install -y \
  php8.4-cli php8.4-common php8.4-fpm php8.4-dev \
  php8.4-mysql php8.4-sqlite3 php8.4-redis php8.4-gd \
  php8.4-curl php8.4-mbstring php8.4-xml php8.4-zip php8.4-bcmath php8.4-intl php8.4-readline php8.4-imagick php8.4-xdebug \
  nodejs yarn mysql-server redis-server ffmpeg sqlite3

# (Optional) Install global Node package managers for convenience
npm install -g pnpm bun

# 5. Configure MySQL: allow root login with no password and create databases
echo "🗄️ Setting up MySQL database and user..."
# Ensure MySQL service is running
systemctl restart mysql || systemctl restart mariadb || true
# Set root password to '' (empty) and open access, then create databases
mysql --user=root <<EOF
ALTER USER 'root'@'localhost' IDENTIFIED WITH mysql_native_password BY '';
FLUSH PRIVILEGES;
CREATE DATABASE IF NOT EXISTS laravel;
CREATE DATABASE IF NOT EXISTS testing;
EOF

# 6. Start/enable services (MySQL and Redis start on install by default, ensure others)
echo "🚀 Starting required services (MySQL, Redis)..."
systemctl enable --now mysql || true
systemctl enable --now redis-server || true

# 7. Install Meilisearch (latest) and Mailpit, then start them
echo "🔎 Installing Meilisearch search engine..."
curl -L https://github.com/meilisearch/meilisearch/releases/latest/download/meilisearch-linux-amd64 -o /usr/local/bin/meilisearch
chmod +x /usr/local/bin/meilisearch

echo "📧 Installing Mailpit (SMTP test server)..."
curl -sL https://raw.githubusercontent.com/axllent/mailpit/develop/install.sh | bash

# Start Meilisearch and Mailpit in background
echo "🔎 Starting Meilisearch on port 7700..."
nohup meilisearch > /tmp/meilisearch.log 2>&1 &
echo "📧 Starting Mailpit on ports 1025 (SMTP) and 8025 (UI)..."
nohup mailpit > /tmp/mailpit.log 2>&1 &

# 8. Copy .env example if .env is not already present, and adjust for local setup
if [[ ! -f .env ]]; then
  echo "⚙️  Setting up environment configuration (.env)..."
  cp .env.example .env
  # Use localhost for services instead of Docker service names
  sed -i 's/DB_HOST=mysql/DB_HOST=127.0.0.1/' .env
  sed -i 's/REDIS_HOST=127.0.0.1/REDIS_HOST=127.0.0.1/' .env  # (should already be 127.0.0.1 in example)
  sed -i 's/MAIL_HOST=127.0.0.1/MAIL_HOST=127.0.0.1/' .env   # ensure mail host is localhost (Mailpit)
  sed -i 's/MAIL_PORT=2525/MAIL_PORT=1025/' .env             # use Mailpit SMTP port
fi

# 9. Configure Composer credentials for private repository (FluxUI), if provided
if [[ -n "${FLUX_USER:-}" && -n "${FLUX_PASS:-}" ]]; then
  echo "🔐 Configuring Composer credentials for FluxUI..."
  composer config --global http-basic.composer.fluxui.dev "$FLUX_USER" "$FLUX_PASS"
else
  echo "🔸 FLUX_USER/FLUX_PASS not set. If the project needs private packages, set these env variables and run composer config."
fi

# 10. Install PHP dependencies (Composer) and JavaScript dependencies (npm)
echo "📦 Installing PHP dependencies (composer install)..."
composer install --no-interaction --prefer-dist

echo "📦 Installing JS dependencies (npm install)..."
npm install

# 11. Set proper permissions for storage and cache directories
echo "🛠️ Setting file permissions for Laravel directories..."
chmod -R 775 storage bootstrap/cache || true

# 12. Generate application key if not already set
echo "🔑 Generating APP_KEY if missing..."
php artisan key:generate --force

# 13. Run database migrations and seeders
echo "📊 Running database migrations..."
php artisan migrate --force

echo "🌱 Seeding reference data (TaskType & StatusType)..."
php artisan db:seed --class=TaskTypeSeeder --force
php artisan db:seed --class=StatusTypeSeeder --force

echo "🌱 Seeding dummy test data..."
php artisan db:seed --force

# 14. Launch development servers (Laravel backend and Vite frontend)
echo "💻 Starting Laravel development server (port 80)..."
nohup php artisan serve --host=0.0.0.0 --port=80 > /tmp/laravel-server.log 2>&1 &
echo "💻 Starting Vite development server (port 5173)..."
nohup npm run dev > /tmp/vite.log 2>&1 &

echo "✅ Setup complete! The application is running. Backend is serving on http://localhost (port 80) and Vite on port 5173. Use Ctrl+C to stop the servers if running in foreground."
