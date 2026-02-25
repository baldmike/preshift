# PreShift

A complete communication and operations platform for restaurants and bars. PreShift replaces the daily pre-shift meeting, staff scheduling, shift management, and team messaging in one place — everything your team needs to stay aligned, accessible from any device.

## Features

### Daily Operations
- **86'd Items** — Track what's out of stock in real time so every server and bartender knows before they hit the floor
- **Specials** — Post daily specials with descriptions, pricing, and limited quantities that decrement as they sell
- **Push Items** — Highlight what the kitchen or bar wants to move tonight
- **Announcements** — Post targeted messages to specific roles (all staff, servers only, bartenders only) with optional expiration dates

### Scheduling
- **Schedule Builder** — Managers build weekly schedules using reusable shift templates, then publish when ready
- **Tonight's Schedule** — Staff see who's working tonight at a glance
- **My Schedule** — Each employee sees their upcoming shifts across all locations they work at
- **Shift Drops** — Staff drop shifts they can't work, eligible same-role employees volunteer, manager picks a replacement
- **Time-Off Requests** — Staff submit requests with configurable advance-notice rules, managers approve or deny, scheduling conflicts are flagged automatically

### Messaging
- **Message Board** — Location-wide posts visible to all staff, with threaded replies
- **Direct Messages** — Private one-on-one conversations between any two team members
- **Real-Time Delivery** — All messages broadcast instantly via WebSockets

### Team Management
- **Multi-Location Organizations** — Group locations under a single organization with org-scoped admin access and cross-location employees
- **Role-Based Access** — Admins, managers, servers, and bartenders each see only what's relevant to their role
- **Employee Profiles** — Staff availability, contact info, and multi-location assignments
- **Acknowledgment Tracking** — Managers see who has and hasn't checked in on today's content
- **Manager Logs** — Daily operational notes visible only to management

### Platform
- **One API Call** — The `/api/preshift` hero endpoint delivers everything a staff member needs in a single request, filtered by role and location
- **Real-Time Updates** — WebSocket broadcasts via Laravel Reverb keep all connected clients in sync without refreshing
- **Mobile-First** — Designed for phones in a server's apron pocket, works on any device
- **Notifications** — In-app notifications for shift drops, time-off decisions, and new messages

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

## Demo Data

The seeder creates a full demo environment with a location, staff across all roles, a menu, sample 86'd items, specials, push items, announcements, and a multi-week schedule. See `DatabaseSeeder.php` for account details.

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
