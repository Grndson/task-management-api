# Task Management API

A RESTful Task Management API built with **Laravel 11** and **MySQL**, featuring a service layer, API resources, feature tests, and a vanilla JavaScript frontend interface.

Built as a technical assessment to demonstrate backend architecture, API design, OOP principles, and frontend integration.

**Live URL:** https://task-management-api-production-641e.up.railway.app

---

## Tech Stack

- **Framework:** Laravel 11 (PHP 8.2)
- **Database:** MySQL 8.x (hosted on Railway)
- **Frontend:** Vanilla JavaScript, HTML, CSS
- **Architecture:** OOP — Service layer, API Resources, Form Requests, Eloquent Models
- **Hosting:** Railway (app + database)

---

## Features

- RESTful API with full CRUD for tasks
- OOP service layer (`TaskService`) keeping controllers thin
- API Resources for clean, consistent JSON responses
- Create tasks with duplicate-prevention (title + due_date must be unique)
- List tasks sorted by priority (high → medium → low), then due date ascending
- Filter tasks by status
- Strict status progression: `pending → in_progress → done` (no skipping, no reverting)
- Only completed (`done`) tasks can be deleted
- Vanilla JS frontend for interacting with all API endpoints
- Daily report showing task counts per priority × status (bonus)
- 11 feature tests covering all business rules

---

## Project Structure

```
app/
├── Http/
│   ├── Controllers/
│   │   └── TaskController.php          ← Thin controller, delegates to service
│   ├── Requests/
│   │   ├── StoreTaskRequest.php        ← Validation for task creation
│   │   └── UpdateTaskStatusRequest.php ← Validation for status update
│   └── Resources/
│       └── TaskResource.php            ← API response transformer
├── Models/
│   └── Task.php                        ← Eloquent model + business logic
├── Services/
│   └── TaskService.php                 ← OOP service layer (create, update, delete, report)
database/
├── migrations/
│   └── ..._create_tasks_table.php      ← Migration with unique constraint
├── seeders/
│   ├── DatabaseSeeder.php
│   └── TaskSeeder.php                  ← 7 sample tasks
└── task_management_dump.sql            ← Direct SQL import option
public/
├── css/
│   └── tasks.css                       ← Frontend styles
└── js/
    └── tasks.js                        ← Frontend logic (Vanilla JS)
resources/
└── views/
    └── tasks.blade.php                 ← Frontend HTML view
routes/
├── api.php                             ← All API routes
└── web.php                             ← Frontend route
tests/
└── Feature/
    └── TaskApiTest.php                 ← 11 feature tests
```

---

## Local Setup

### Prerequisites

- PHP >= 8.2
- Composer
- MySQL 8.x
- Git

### Steps

```bash
# 1. Clone the project
git clone https://github.com/Grndson/task-management-api.git
cd task-management-api

# 2. Install dependencies
composer install

# 3. Set up environment
cp .env.example .env
php artisan key:generate

# 4. Configure MySQL in .env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=task_management
DB_USERNAME=root
DB_PASSWORD=your_password

# 5. Create the database
mysql -u root -p -e "CREATE DATABASE task_management CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# 6a. Run migrations + seeders (recommended)
php artisan migrate --seed

# 6b. OR import the SQL dump directly
mysql -u root -p task_management < database/task_management_dump.sql

# 7. Start the server
php artisan serve
```

The API is now available at: `http://localhost:8000/api`
The frontend interface is at: `http://localhost:8000`

### Running Tests

```bash
php artisan test
```

Expected output:
```
PASS  Tests\Feature\TaskApiTest
✓ can create a task
✓ cannot create task with past due date
✓ cannot create duplicate title on same due date
✓ can list tasks
✓ can filter tasks by status
✓ can advance status from pending to in progress
✓ cannot skip status
✓ cannot revert status
✓ can delete a done task
✓ cannot delete a pending task
✓ daily report returns correct structure

Tests: 11 passed
```

---

## Deployment (Railway)

This project is deployed on [Railway](https://railway.app) with a MySQL database plugin.

### Steps to deploy your own instance

1. Push the project to GitHub
2. Go to [railway.app](https://railway.app) → New Project → Deploy from GitHub repo
3. Add a MySQL database plugin inside Railway
4. Set the following environment variables in Railway:

```env
APP_NAME=Task Management API
APP_ENV=production
APP_KEY=base64:your-generated-key
APP_DEBUG=false
APP_URL=https://your-railway-domain.up.railway.app
ASSET_URL=https://your-railway-domain.up.railway.app

DB_CONNECTION=mysql
DB_HOST=        (from Railway MySQL service → MYSQL_HOST)
DB_PORT=        (from Railway MySQL service → MYSQL_PORT)
DB_DATABASE=    (from Railway MySQL service → MYSQL_DATABASE)
DB_USERNAME=    (from Railway MySQL service → MYSQL_USER)
DB_PASSWORD=    (from Railway MySQL service → MYSQL_PASSWORD)
```

5. Set the start command in Railway → Settings:

```bash
php artisan migrate --force && php artisan db:seed --force && php artisan serve --host=0.0.0.0 --port=$PORT
```

6. Generate a domain under Settings → Networking → Generate Domain

---

## API Endpoints

**Base URL (local):** `http://localhost:8000/api`
**Base URL (live):** `https://task-management-api-production-641e.up.railway.app/api`

---

### 1. Create Task

```
POST /api/tasks
Content-Type: application/json
Accept: application/json
```

**Request Body:**
```json
{
  "title": "Fix login bug",
  "due_date": "2026-04-10",
  "priority": "high"
}
```

**Success Response (201):**
```json
{
  "message": "Task created successfully.",
  "task": {
    "id": 1,
    "title": "Fix login bug",
    "due_date": "2026-04-10",
    "priority": "high",
    "status": "pending",
    "created_at": "2026-03-30 10:00:00",
    "updated_at": "2026-03-30 10:00:00"
  }
}
```

**Validation Error (422):**
```json
{
  "errors": {
    "title": ["A task with this title already exists for the same due date."],
    "due_date": ["The due date must be today or a future date."]
  }
}
```

---

### 2. List Tasks

```
GET /api/tasks
GET /api/tasks?status=pending
GET /api/tasks?status=in_progress
GET /api/tasks?status=done
```

**Success Response (200):**
```json
{
  "total": 3,
  "tasks": [
    {
      "id": 1,
      "title": "Fix login bug",
      "due_date": "2026-04-01",
      "priority": "high",
      "status": "pending",
      "created_at": "2026-03-30 10:00:00",
      "updated_at": "2026-03-30 10:00:00"
    }
  ]
}
```

**Empty Response (200):**
```json
{
  "message": "No tasks found. Create your first task!",
  "tasks": []
}
```

---

### 3. Update Task Status

```
PATCH /api/tasks/{id}/status
Content-Type: application/json
Accept: application/json
```

**Request Body:**
```json
{
  "status": "in_progress"
}
```

**Success Response (200):**
```json
{
  "message": "Task status updated successfully.",
  "task": {
    "id": 1,
    "status": "in_progress"
  }
}
```

**Invalid Transition (422):**
```json
{
  "message": "Invalid transition. From 'pending' you can only move to 'in_progress'."
}
```

---

### 4. Delete Task

```
DELETE /api/tasks/{id}
Accept: application/json
```

**Success Response (200):**
```json
{
  "message": "Task deleted successfully."
}
```

**Forbidden — task not done (403):**
```json
{
  "message": "Only tasks with status \"done\" can be deleted."
}
```

---

### 5. Daily Report (Bonus)

```
GET /api/tasks/report?date=YYYY-MM-DD
Accept: application/json
```

**Success Response (200):**
```json
{
  "date": "2026-03-30",
  "total": 7,
  "summary": {
    "high":   { "pending": 2, "in_progress": 1, "done": 0 },
    "medium": { "pending": 1, "in_progress": 1, "done": 1 },
    "low":    { "pending": 0, "in_progress": 0, "done": 1 }
  }
}
```

---

## Business Rules

| Rule | Implementation |
|------|----------------|
| No duplicate title on same due_date | DB unique constraint + Form Request validation |
| due_date must be today or later | `after_or_equal:today` validation rule |
| Priority must be low, medium, or high | Enum column + `Rule::in()` validation |
| Status flows: pending → in_progress → done only | `canTransitionTo()` on Task model, enforced in TaskService |
| Cannot skip or revert status | Validated in TaskService; returns 422 with message |
| Only `done` tasks can be deleted | Enforced in TaskService; returns 403 Forbidden otherwise |
| List sorted by priority then due_date | `FIELD()` MySQL function via Eloquent scope |

---

## Testing with cURL

```bash
# Create a task
curl -X POST https://task-management-api-production-641e.up.railway.app/api/tasks \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{"title":"My task","due_date":"2026-04-15","priority":"high"}'

# List all tasks
curl -H "Accept: application/json" \
  https://task-management-api-production-641e.up.railway.app/api/tasks

# Filter by status
curl -H "Accept: application/json" \
  "https://task-management-api-production-641e.up.railway.app/api/tasks?status=pending"

# Update status
curl -X PATCH https://task-management-api-production-641e.up.railway.app/api/tasks/1/status \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{"status":"in_progress"}'

# Delete a done task
curl -X DELETE -H "Accept: application/json" \
  https://task-management-api-production-641e.up.railway.app/api/tasks/4

# Daily report
curl -H "Accept: application/json" \
  "https://task-management-api-production-641e.up.railway.app/api/tasks/report?date=2026-03-31"
```

---

## Database

- **Database:** MySQL 8.x
- **SQL Dump:** `database/task_management_dump.sql`

Import directly:
```bash
mysql -u root -p task_management < database/task_management_dump.sql
```

---

## Architecture Notes

- **TaskService** handles all business logic (create, update, delete, report) — controllers only handle HTTP concerns
- **TaskResource** transforms Eloquent models into clean JSON responses with formatted dates
- **Form Requests** handle all input validation before it reaches the controller
- **Eloquent Scopes** on the Task model handle sorting and filtering at the query level
- Frontend consumes the API entirely through the `fetch` API — no page reloads