# Task Management API — Setup Guide

A Laravel REST API for the Task Management System (token auth via Sanctum,
task CRUD with filtering/search/pagination, ownership-based authorization, and
rate limiting). This guide walks through setting it up from scratch and
importing the provided MySQL dump.

---

## 1. Prerequisites

Make sure these are installed and on your `PATH`:

| Tool | Version | Check |
| --- | --- | --- |
| PHP | 8.3+ | `php -v` |
| Composer | 2.x | `composer -V` |
| MySQL | 8.0+ (or MariaDB 10.6+) | `mysql --version` |

Required PHP extensions (usually bundled): `pdo_mysql`, `mbstring`, `openssl`,
`tokenizer`, `xml`, `ctype`, `json`, `bcmath`, `fileinfo`.

---

## 2. Install dependencies

From the project root (the folder containing `composer.json`):

```bash
composer install
```

This pulls in Laravel, Sanctum, and everything else defined in
`composer.json`.

---

## 3. Environment configuration

Create your environment file and generate an app key:

```bash
cp .env.example .env
php artisan key:generate
```

Open `.env` and set the **MySQL** connection. Match `DB_DATABASE` to the
database name you will import the dump into (see the next section):

```dotenv
APP_NAME="Task Management API"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://127.0.0.1:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=task_management
DB_USERNAME=root
DB_PASSWORD=your_mysql_password
```

> For production set `APP_ENV=production` and `APP_DEBUG=false`.

After editing `.env`, clear cached config so the new values are picked up:

```bash
php artisan config:clear
```

---

## 4. Create the database and import `DatabaseDump.Sql`

First create an empty database (skip this if your dump already contains a
`CREATE DATABASE` statement):

```bash
mysql -u root -p -e "CREATE DATABASE task_management CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
```

Then import the dump using **any one** of the methods below.

### Option A — Command line (recommended)

```bash
mysql -u root -p task_management < DatabaseDump.Sql
```

You'll be prompted for the MySQL password. If your dump already creates and
selects its own database, you can omit the database name:

```bash
mysql -u root -p < DatabaseDump.Sql
```

### Option B — From inside the MySQL shell

```bash
mysql -u root -p
```
```sql
CREATE DATABASE IF NOT EXISTS task_management
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE task_management;
SOURCE /full/path/to/DatabaseDump.Sql;
EXIT;
```

> Use the **absolute path** to `DatabaseDump.Sql` with `SOURCE`.

### Option C — GUI (MySQL Workbench or phpMyAdmin)

- **MySQL Workbench:** Server → *Data Import* → *Import from Self-Contained File*
  → select `DatabaseDump.Sql` → choose/create the `task_management` schema as the
  default target → *Start Import*. (Or open it as an SQL script and run it.)
- **phpMyAdmin:** create/select the `task_management` database → *Import* tab →
  *Choose File* `DatabaseDump.Sql` → *Go*.

### Verify the import

```bash
mysql -u root -p task_management -e "SHOW TABLES; SELECT COUNT(*) AS tasks FROM tasks;"
```

You should see the application tables (`users`, `tasks`,
`personal_access_tokens`, `migrations`, …) and a task count.

> **Note:** because the dump already contains the schema and data, you do **not**
> need to run `php artisan migrate`. Running migrations is only for setting up a
> fresh database without the dump (see the Appendix).

---

## 5. Run the API

```bash
php artisan serve
```

The API is now available at:

```
http://127.0.0.1:8000
```

All endpoints are prefixed with `/api` (e.g. `http://127.0.0.1:8000/api/login`).

---

## 6. Test credentials

The seeded default account (present in the dump):

```
Email:    test@example.com
Password: password
```

A second account also exists for testing cross-user authorization:

```
Email:    second@example.com
Password: password
```

---

## 7. Smoke test

Confirm login returns a token:

```bash
curl -X POST http://127.0.0.1:8000/api/login \
  -H "Accept: application/json" \
  -d "email=test@example.com&password=password"
```

Then list tasks using the returned token:

```bash
curl http://127.0.0.1:8000/api/tasks \
  -H "Accept: application/json" \
  -H "Authorization: Bearer PASTE_TOKEN_HERE"
```

A Postman collection (`postman/TaskManagement.postman_collection.json`) is also
included and auto-saves the token after login.

---

## 8. Connecting the Flutter app

The Flutter client points at the API via `lib/core/constants/api_constants.dart`.
Set the base URL for your target:

| Target | Base URL |
| --- | --- |
| Android emulator | `http://10.0.2.2:8000/api` |
| iOS simulator | `http://127.0.0.1:8000/api` |
| Physical device | `http://<your-machine-LAN-ip>:8000/api` |

On Android, plain HTTP is enabled for local development via
`android:usesCleartextTraffic="true"` in
`android/app/src/main/AndroidManifest.xml`.

---

## API endpoint reference

| Method | Path | Auth | Description |
| --- | --- | --- | --- |
| POST | `/api/login` | — | Authenticate, returns token (rate-limited 5/min) |
| POST | `/api/logout` | Sanctum | Revoke current token |
| GET | `/api/me` | Sanctum | Current user |
| GET | `/api/tasks` | Sanctum | List with `?status=`, `?priority=`, `?search=`, `?per_page=`, `?page=` |
| POST | `/api/tasks` | Sanctum | Create a task |
| GET | `/api/tasks/{id}` | Sanctum | Show a task (owner only) |
| PUT/PATCH | `/api/tasks/{id}` | Sanctum | Update a task (owner only) |
| PATCH | `/api/tasks/{id}/status` | Sanctum | Update status only (owner only) |
| DELETE | `/api/tasks/{id}` | Sanctum | Delete a task (owner only) |

All authenticated routes are rate-limited to 60 requests/min per user.

---

## Troubleshooting

**`SQLSTATE[HY000] [1045] Access denied`** — wrong `DB_USERNAME`/`DB_PASSWORD`
in `.env`. Fix and run `php artisan config:clear`.

**`SQLSTATE[HY000] [1049] Unknown database 'task_management'`** — the database
doesn't exist yet. Create it (Section 4) before importing, and make sure
`DB_DATABASE` matches.

**`Could not open input file: artisan`** — you're not in the project root. `cd`
into the folder that contains `artisan` and `composer.json`.

**`No application encryption key has been specified`** — run
`php artisan key:generate`.

**Unauthenticated / `401` on every call** — make sure you send both the
`Authorization: Bearer <token>` and `Accept: application/json` headers.

**Changes to `.env` not taking effect** — run `php artisan optimize:clear`.

**Port 8000 already in use** — serve on another port:
`php artisan serve --port=8001` (update the Flutter base URL to match).

---

## Appendix — Fresh database without the dump

If you ever need to build the database from migrations instead of the dump:

```bash
# .env DB_DATABASE points at an empty database, then:
php artisan migrate:fresh --seed
```

This recreates all tables (IDs starting at 1) and runs the seeders:
- the default + second users, and
- 1000 structured demo tasks for the default user.
