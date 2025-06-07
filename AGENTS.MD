# Empreus AI Agents Guide

This document describes the AI agents used in the Empreus project. It’s designed for a **Ubuntu 24.04** (Codex) environment without Docker.

## Agents Overview

Each agent automates common development tasks by running predefined commands. Before using any agent, ensure you have run `setup.sh` (which installs system packages, PHP, Node.js, MySQL, Redis, Meilisearch, Mailpit) and that services (MySQL, Redis, Meilisearch, Mailpit) are running on `127.0.0.1`.

Agents can be invoked via natural language prompts in Codex

Agents should create a new branch by default on each task with the format <task-type>/<task name> i.e: feature/add-help-button or improvement/increase-performance, unless stated otherwise by the client.

---

### 1. Dev Environment Setup Agent

**Purpose:** Install PHP (v8.4) & JS dependencies, generate app key, set permissions.

**Commands:**

```bash
composer install
npm install
chmod -R 775 storage bootstrap/cache
```

**When to use:** On a fresh clone or after pulling new dependencies.

---

### 2. Database Migration Agent

**Purpose:** Apply new migrations or rebuild the database schema.

**Commands:**

```bash
php artisan migrate
# or for a full reset:
php artisan migrate:fresh
```

**Env vars:** `DB_HOST=127.0.0.1`, `DB_DATABASE=laravel`, `DB_USERNAME=root`, `DB_PASSWORD=` (empty)

---

### 3. Database Seeding Agent

**Purpose:** Populate required lookup tables and optional dummy data.

**Commands:**

```bash
php artisan db:seed --class=TaskTypeSeeder --force
php artisan db:seed --class=StatusTypeSeeder --force
php artisan db:seed --force
```

**When to use:** After migrations, to ensure reference data exist.

---

### 4. Development Server Agent

**Purpose:** Start/stop backend & frontend dev servers.

**Commands:**

```bash
# Backend
php artisan serve --host=0.0.0.0 --port=8000
# Frontend (in another terminal)
npm run dev
```

**Stop:** Ctrl+C in running terminal (or kill background processes).

---

### 5. Queue Worker Agent (Horizon)

**Purpose:** Run Laravel Horizon to process queued jobs.

**Command:**

```bash
php artisan horizon
```

**Env vars:** `QUEUE_CONNECTION=redis`, `REDIS_HOST=127.0.0.1`

---

### 7. Production Build & Deployment Agent

**Purpose:** Build front-end assets for production and optimize Laravel config/routes.

**Commands:**

```bash
npm run build
php artisan optimize
```

**Env vars:** `APP_ENV=production`, `APP_DEBUG=false`, correct `APP_URL`, production DB/Redis settings, third‑party API keys.

---

## Usage Examples

* **"Initialize my environment"** → Dev Environment Setup Agent
* **"Run migrations"** → Database Migration Agent
* **"Seed the database"** → Database Seeding Agent
* **"Start servers"** → Development Server Agent

---

*All agents assume services are on `127.0.0.1` and that you’re on Ubuntu 24.04 (Codex environment) with root or sudo privileges for initial setup. That you use Laravel 11 and Livewire 3*