#!/usr/bin/env bash
set -euo pipefail

# ------------------------------------------------------------
# setup.sh for Empreus Admin (using Laravel Sail + MySQL/Redis)
# ------------------------------------------------------------

# 1. Ensure Docker/Sail is available
if ! command -v docker &> /dev/null; then
  echo "Error: Docker is not installed. Please install Docker first." >&2
  exit 1
fi

# 2. Bring up Sail (build images if needed) in detached mode
echo "🔧 Starting Docker containers via Sail..."
./docker/sail up -d

# 3. Configure Flux credentials for Composer (already in env, fallback to placeholders)
FLUX_USER="${FLUX_USER:-alejandrosan3@gmail.com}"
FLUX_PASS="${FLUX_PASS:-fcc36e72-5f5d-4402-98f7-8c5d8f7264e2}"
echo "🔐 Configuring FluxUI credentials for Composer..."
docker run --rm \
  -v "$(pwd)":/app \
  -w /app \
  laravelsail/php80-composer:latest \
  composer config --global http-basic.composer.fluxui.dev "$FLUX_USER" "$FLUX_PASS"

# 4. Install PHP dependencies inside Sail
echo "📦 Installing PHP dependencies (Composer)..."
./docker/sail composer install --no-interaction --prefer-dist

# 5. Install JS dependencies inside Sail
echo "📦 Installing JS dependencies (npm)..."
./docker/sail npm install

# 6. Fix permissions (so Laravel can write to storage & cache)
echo "🛠️  Setting folder permissions..."
./docker/sail exec app sh -c "chmod -R 775 storage bootstrap/cache || true"

# 7. Generate APP_KEY if missing (your .env already has one, but this enforces it)
echo "🔑 Ensuring APP_KEY is set..."
./docker/sail artisan key:generate --force

# 8. Run migrations (MySQL container will be used, per your .env)
echo "🗄️  Running database migrations..."
./docker/sail artisan migrate --force

# 9. Seed mandatory tables, then dummy data
echo "🌱 Seeding mandatory reference data..."
./docker/sail artisan db:seed --class=TaskTypeSeeder --force
./docker/sail artisan db:seed --class=StatusTypeSeeder --force

echo "🌱 Seeding all dummy/test data..."
./docker/sail artisan db:seed --force

# 10. (Optional) Start Vite dev server in background, if you need hot reload
echo "🚀 Starting Vite dev server in background..."
nohup ./docker/sail npm run dev > /dev/null 2>&1 &

echo "✅ Setup complete! Your app should be running at http://localhost"
