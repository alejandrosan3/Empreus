# Codex Agents for Empreus Admin Project

## 1. Dev Environment Setup Agent

**Description:**
Automates starting up the Laravel Sail (Docker) environment and installing dependencies so the app runs locally.

**Purpose & Capabilities:**

* Starts Docker containers (app, MySQL, Redis, Meilisearch, Mailpit, Selenium).
* Installs PHP dependencies via Composer inside the container.
* Installs front-end dependencies via npm inside the container.
* Ensures `APP_KEY` is already set (it is, via your Codex env vars).
* Sets file permissions so Laravel can write to `storage` and `bootstrap/cache`.

> **Note:** Since all necessary environment variables (APP\_KEY, DB\_*, REDIS\_*, FLUX\_USER, FLUX\_PASS, etc.) are configured in Codex, we skip copying `.env.example`.

**Entry Points:**

```bash
# From repo root
./docker/sail up -d
./docker/sail composer install
./docker/sail npm install
chmod -R 777 storage bootstrap/cache  # ensures write permissions inside the container
```

> If for some reason `APP_KEY` is empty (unlikely, because Codex env provides it), run:
>
> ```bash
> ./docker/sail artisan key:generate
> ```

**Environment Variables (pulled from Codex settings):**

* `APP_NAME`, `APP_ENV=local`, `APP_KEY` (pre-set).
* `DB_CONNECTION=mysql`, `DB_HOST=mysql`, `DB_DATABASE=laravel`, `DB_USERNAME=root`, `DB_PASSWORD=`.
* `QUEUE_CONNECTION=redis`, `REDIS_HOST=redis`, `REDIS_PORT=6379`.
* `MAIL_MAILER=log` (so Mailpit/Log is used locally).
* `FLUX_USER`, `FLUX_PASS` (for FluxUI prompt during Composer install).
* Any other Laravel-related variables from your `.env` (all handled by Codex).

**Required Tools / Context:**

* Docker & Docker Compose installed and running (Sail uses them).
* Run commands from the project root.
* Codex already has all necessary env vars set—no manual `.env` file work is needed.

**Example Usage:**

* “Set up my local dev environment” → runs the above commands in order.
* At this point, the app’s containers are up, dependencies are installed, and you can move on to migrations.

---

## 2. Database Migration Agent

**Description:**
Runs Laravel’s migrations inside the Docker container to create/update database tables.

**Purpose & Capabilities:**

* Applies any new schema changes.
* Optionally refreshes the database from scratch.

**Entry Points:**

```bash
# Run pending migrations
./docker/sail artisan migrate

# (Optional) Wipe & re-run from zero
./docker/sail artisan migrate:fresh
```

**Environment Variables (used by Laravel):**

* `DB_CONNECTION=mysql`
* `DB_HOST=mysql`
* `DB_DATABASE=laravel`
* `DB_USERNAME=root`
* `DB_PASSWORD=`

These are already configured in Codex, so migrations connect automatically.

**Example Usage:**

* “Run migrations” → executes `artisan migrate`.
* “Refresh the database” → executes `artisan migrate:fresh`.

---

## 3. Database Seeding Agent

**Description:**
Populates mandatory lookup tables and dummy test data after migrations.

**Purpose & Capabilities:**

* Inserts essential reference data (`TaskType`, `StatusType`).
* Inserts dummy records for testing (via the main seeder).

**Entry Points:**

```bash
# Seed mandatory reference tables
./docker/sail artisan db:seed --class=TaskTypeSeeder
./docker/sail artisan db:seed --class=StatusTypeSeeder

# Seed all dummy/test data
./docker/sail artisan db:seed
```

**Environment Variables:**

* Same DB\_\* variables as migrations.
* `APP_FAKER_LOCALE=en_US` (already set in Codex).

**Example Usage:**

* “Seed mandatory tables” → runs the first two seeders.
* “Populate dummy data” → runs the main `db:seed` for full test data.

---

## 4. Development Server Agent

**Description:**
Brings up (or tears down) the Dockerized Laravel environment for day-to-day dev.

**Purpose & Capabilities:**

* Starts all containers (app, DB, Redis, etc.) in detached mode.
* Optionally starts the front-end (Vite) dev server.
* Stops the entire Sail environment when you’re done.

**Entry Points:**

```bash
# Start everything (after initial setup)
./docker/sail up -d

# Run Vite for front-end hot reload (in a separate terminal)
./docker/sail npm run dev

# Stop all containers
./docker/sail down
```

**Environment Variables:**

* `APP_PORT=80` (default via Docker).
* `VITE_PORT=5173` (default for Vite, forwarded by Sail).
* `APP_URL=http://localhost` (already set in Codex).

**Example Usage:**

* “Start dev server” → `sail up -d`.
* “Run front-end dev server” → `npm run dev`.
* “Stop project” → `sail down`.

---

## 5. Queue Worker Agent (Laravel Horizon)

**Description:**
Runs background jobs using Laravel Horizon (requires Redis).

**Purpose & Capabilities:**

* Starts the Horizon dashboard and queue workers.
* Processes any queued jobs in real-time.

**Entry Points:**

```bash
# Ensure .env (in Codex) has QUEUE_CONNECTION=redis & REDIS_HOST=redis
./docker/sail artisan horizon
```

> Horizon runs indefinitely until stopped. Press Ctrl+C or `sail down` to halt.

**Environment Variables:**

* `QUEUE_CONNECTION=redis` (set in Codex).
* `REDIS_HOST=redis`, `REDIS_PORT=6379` (set in Codex).

No other credentials needed—Sail’s Redis container is used.

**Example Usage:**

* “Start queue worker” → runs `artisan horizon` inside Docker.

---

## 6. Testing Agent

**Description:**
Runs the Laravel test suite (unit, feature, and any Dusk/browser tests) inside Docker.

**Purpose & Capabilities:**

* Executes PHPUnit tests via `artisan test`.
* Optionally runs Dusk tests if you need browser-level coverage.

**Entry Points:**

```bash
# Run all PHPUnit tests
./docker/sail artisan test

# (Optional) Run Laravel Dusk browser tests
# Make sure Selenium container is up (Sail brings it up by default)
./docker/sail artisan dusk
```

**Environment Variables:**

* `APP_ENV=testing` (automatically set by PHPUnit).
* `DB_DATABASE=testing` (Docker script creates it).
* `DB_HOST=mysql`, `DB_USERNAME=root`, `DB_PASSWORD=` (from Codex).
* Mail and cache drivers for tests are configured in `phpunit.xml` (e.g., `MAIL_MAILER=array`, `CACHE_STORE=array`).

**Example Usage:**

* “Run tests” → executes `artisan test`.
* “Run Dusk tests” → executes `artisan dusk`.

---

## 7. Production Build & Deployment Agent

**Description:**
Prepares optimized assets and caches configuration/routes for a production environment.

**Purpose & Capabilities:**

* Builds front-end assets for production with Vite.
* Runs Laravel’s optimization commands: config caching, route caching, view caching.
* Leaves a ready-to-deploy artifact (assets + cached configs).

**Entry Points:**

```bash
# Build front-end for production
./docker/sail npm run build

# Optimize Laravel for production
./docker/sail artisan optimize
```

**Environment Variables (should be set in your production server’s .env):**

* `APP_ENV=production`, `APP_DEBUG=false`.
* `APP_URL=https://your-production-domain.com`.
* `DB_HOST`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` (your real production DB).
* `REDIS_HOST`, `REDIS_PASSWORD` (if using Redis in prod).
* `AWS_ACCESS_KEY_ID`, `AWS_SECRET_ACCESS_KEY`, `AWS_BUCKET` (if storing files on S3).
* `POSTMARK_TOKEN` (or other mail service credentials) for sending real emails.
* Any other third-party API keys (Stripe, SQS, etc.).

**Required Tools / Context:**

* Node and PHP available (either on host or in a CI container).
* Docker is optional in production—if you deploy via container, run these commands inside a build container.
* Ensure all production env vars are set before running build tasks.

**Example Usage:**

* “Build for production” → runs `npm run build` and `artisan optimize`.
* Developer/CI then packages or deploys the result.

---

### How to Use These Agents in Codex

When you ask Codex to perform one of these tasks, reference the agent by name. For example:

* **“Use the Dev Environment Setup Agent to bring up the project.”**
  Codex will execute (inside Docker):

  ```bash
  ./docker/sail up -d
  ./docker/sail composer install
  ./docker/sail npm install
  chmod -R 777 storage bootstrap/cache
  ```

* **“Invoke the Database Migration Agent.”**
  Codex runs:

  ```bash
  ./docker/sail artisan migrate
  ```

* **“Tell the Queue Worker Agent to start Horizon.”**
  Codex runs:

  ```bash
  ./docker/sail artisan horizon
  ```

All environment variables (APP\_KEY, DB\_*, REDIS\_*, FLUX\_USER, FLUX\_PASS, etc.) are assumed to be configured in the Codex environment, so the agents never copy or modify a local `.env` file. If you ever need to adjust an env var, update it in Codex’s environment settings directly.

That’s it—no other info required. Once Codex knows these agent names and entry points, you can simply ask it to run any of the above tasks, and it will handle the rest.
