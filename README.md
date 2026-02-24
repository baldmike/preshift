# PreShift

A digital pre-shift meeting replacement for restaurants and bars. Managers post daily operational updates — 86'd items, specials, push items, announcements — and staff check in before their shift to see everything they need, tailored to their role. Includes a full scheduling system with shift drops and time-off requests.

## Tech Stack

| Layer | Technology |
|-------|-----------|
| Frontend | Vue 3, Vite, Pinia, Vue Router, Tailwind CSS |
| Backend | Laravel 11, PHP 8.4+ |
| Auth | Laravel Sanctum (token-based) |
| Realtime | Laravel Reverb (WebSockets) |
| Database | MySQL (SQLite for tests) |

## Project Structure

```
/api       ← Laravel 11 API
/client    ← Vue 3 SPA
```

The API and client are independent applications. The Vue SPA proxies `/api` requests to the Laravel backend during development.

## Prerequisites

- PHP 8.4+
- Composer
- Node.js 20+
- npm
- MySQL 8+

## Local Setup

### 1. Clone the repo

```bash
git clone git@github.com:baldmike/preshift.git
cd preshift
```

### 2. Set up the API

```bash
cd api
composer install
cp .env.example .env
php artisan key:generate
```

Edit `.env` and configure your database:

```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=preshift
DB_USERNAME=root
DB_PASSWORD=
```

Run migrations and seed demo data:

```bash
php artisan migrate
php artisan db:seed
```

### 3. Set up the client

```bash
cd ../client
npm install
```

### 4. Start the dev servers

In one terminal — API:

```bash
cd api
php artisan serve
```

In another terminal — client:

```bash
cd client
npm run dev
```

The app is now running at **http://localhost:5173**. The Vite dev server proxies API requests to Laravel on port 8000.

### 5. (Optional) Start Reverb for real-time updates

```bash
cd api
php artisan reverb:start
```

## Demo Login

After seeding, all accounts use the password **`password`**. Here are some accounts to try:

| Role | Name | Email |
|------|------|-------|
| Admin (superadmin) | Prince Springsteen | `prince@preshift.test` |
| Manager | Lisa Mercury | `mercury@preshift.test` |
| Server | Sam Presley | `presley@preshift.test` |
| Bartender | Kyle Hendrix | `hendrix@preshift.test` |

The seeder creates a full demo environment with a location ("The Anchor"), staff across all roles, a menu, sample 86'd items, specials, push items, announcements, and a multi-week schedule.

### Initial Setup

When you're ready to replace the demo data with your own, log in as the superadmin and go to the **Config** page. The **Initial Setup** form wipes all seeded data and creates your real superadmin account and location. After setup, the Config page shows the Establishment Name setting and a Danger Zone with a full reset option.

## Running Tests

### Backend

```bash
cd api
php artisan test
```

### Frontend

```bash
cd client
npm test
```

## Production

The app is deployed at **https://preshift86.com** on a DigitalOcean droplet running Ubuntu 24.04.

| Component | Detail |
|-----------|--------|
| Web server | Nginx (SSL via Let's Encrypt) |
| PHP | PHP 8.4 FPM |
| Database | MySQL 8 (local) |
| WebSockets | Laravel Reverb (proxied via Nginx at `/app`) |
| Process management | Supervisor (Reverb + queue worker) |

Deploy scripts live in `/deploy`. For subsequent deployments:

```bash
ssh preshift
cd /var/www/preshift
sudo -u preshift bash deploy/deploy.sh
```

## Roles

| Role | Access |
|------|--------|
| admin | Full access across all locations |
| manager | CRUD content, build schedules, approve drops/time-off (scoped to location) |
| server | View pre-shift content, view schedule, request drops/time-off (scoped to location) |
| bartender | Same as server, plus bar-specific content visibility |
