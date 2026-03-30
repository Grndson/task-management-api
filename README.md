# Task Management API & Web Interface 

This is a task management system built with Laravel and a MySQL-compatible database (MariaDB), featuring a RESTful API and a lightweight frontend built with vanilla JavaScript.

The application allows users to create, update, filter, and manage tasks based on priority, status, and deadlines.

This project was developed as a technical assessment to demonstrate backend architecture, API design, and frontend integration.

---

## Tech Stack

- **Framework:** Laravel 10 (PHP 8.1+)
- **Database:** MariaDB (MySQL-compatible)
- **Frontend:** Vanilla JavaScript, HTML, CSS
- **Language:** PHP (OOP — Models, Form Requests, Controllers)

---

## Features

- RESTful API for task management
- Create tasks with duplicate-prevention (title + due_date must be unique)
- List tasks sorted by priority (high → medium → low), then due date
- Filter tasks by status
- Advance task status strictly: `pending → in_progress → done`
- Delete only completed (`done`) tasks
- **Frontend UI** for interacting with the API (vanilla JavaScript)
- **Bonus:** Daily report showing counts per priority × status

---

## Project Structure

```
app/
├── Http/
│   ├── Controllers/
│   │   └── TaskController.php       ← All 5 endpoints
│   └── Requests/
│       ├── StoreTaskRequest.php     ← Validation for create
│       └── UpdateTaskStatusRequest.php
├── Models/
│   └── Task.php                     ← Eloquent model + business logic
database/
├── migrations/
│   └── 2024_01_01_000000_create_tasks_table.php
├── seeders/
│   ├── DatabaseSeeder.php
│   └── TaskSeeder.php
│   task_management_dump.sql         ← Direct SQL import option
routes/
└── api.php                          ← All API routes
```

---

## Local Setup

### Prerequisites
- PHP >= 8.1
- Composer
- MySQL 8.x
- Laravel CLI

### Steps

```bash
# 1. Clone the project
git clone <your-repo-url>
cd task-api

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
# API is now at: http://localhost:8000/api
```

---
## Frontend Usage

The project includes a simple frontend interface built with vanilla JavaScript to interact with the API.

Features:
- Create tasks via form input
- View tasks grouped by status
- Update task status dynamically
- Delete completed tasks

Access it at:
http://localhost:8000/

When deployed:
https://your-app-url/

---

## API Endpoints

Base URL: `http://localhost:8000/api` (or your deployed URL)

---

### 1. Create Task

```
POST /api/tasks
Content-Type: application/json
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
    "created_at": "2026-03-30T10:00:00.000000Z",
    "updated_at": "2026-03-30T10:00:00.000000Z"
  }
}
```

**Validation Errors (422):**
```json
{
  "message": "The title has already been taken.",
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
      "status": "pending"
    },
    {
      "id": 3,
      "title": "Code review",
      "due_date": "2026-04-01",
      "priority": "high",
      "status": "in_progress"
    },
    {
      "id": 2,
      "title": "Write tests",
      "due_date": "2026-04-05",
      "priority": "medium",
      "status": "pending"
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
    "status": "in_progress",
    ...
  }
}
```

**Invalid Transition (422):**
```json
{
  "message": "Invalid status transition. From 'pending', you can only move to 'in_progress'.",
  "current_status": "pending",
  "allowed_next": "in_progress"
}
```

---

### 4. Delete Task

```
DELETE /api/tasks/{id}
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
  "message": "Forbidden. Only tasks with status \"done\" can be deleted.",
  "current_status": "in_progress"
}
```

---

### 5. Daily Report (Bonus)

```
GET /api/tasks/report?date=2026-03-30
```

**Success Response (200):**
```json
{
  "date": "2026-03-30",
  "total": 7,
  "summary": {
    "high": {
      "pending": 2,
      "in_progress": 1,
      "done": 0
    },
    "medium": {
      "pending": 1,
      "in_progress": 1,
      "done": 1
    },
    "low": {
      "pending": 0,
      "in_progress": 0,
      "done": 1
    }
  }
}
```

---

## Business Rules Summary

| Rule | Implementation |
|------|---------------|
| No duplicate title on same due_date | DB unique constraint + Form Request validation |
| due_date must be today or later | `after_or_equal:today` validation rule |
| Priority: low, medium, high only | Enum column + `Rule::in()` validation |
| Status flows: pending→in_progress→done only | `canTransitionTo()` method on Task model |
| Cannot skip or revert status | Checked before update; 422 with helpful message |
| Only `done` tasks can be deleted | Checked before delete; 403 Forbidden otherwise |
| List sorted: priority high→low, then due_date asc | `FIELD()` MySQL function in Eloquent scope |

---

## Testing with cURL

```bash
# Create a task
curl -X POST http://localhost:8000/api/tasks \
  -H "Content-Type: application/json" \
  -d '{"title":"My task","due_date":"2026-04-15","priority":"high"}'

# List all tasks
curl http://localhost:8000/api/tasks

# List only pending tasks
curl "http://localhost:8000/api/tasks?status=pending"

# Update status to in_progress
curl -X PATCH http://localhost:8000/api/tasks/1/status \
  -H "Content-Type: application/json" \
  -d '{"status":"in_progress"}'

# Delete a done task
curl -X DELETE http://localhost:8000/api/tasks/4

# Get daily report
curl "http://localhost:8000/api/tasks/report?date=2026-04-15"
```

---

## Database

- **Database Used:** MariaDB 10.4 (MySQL-compatible)
- **SQL Dump:** `database/task_management_dump.sql`

Import directly:
```bash
mysql -u root -p task_management < database/task_management_dump.sql
```

## Architecture Overview

- Backend handles all business logic, validation, and data persistence
- Frontend consumes the API using JavaScript (fetch/HTTP requests)
- Clean separation between API and UI layers
