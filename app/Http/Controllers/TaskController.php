<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTaskRequest;
use App\Http\Requests\UpdateTaskStatusRequest;
use App\Models\Task;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TaskController extends Controller
{
    /**
     * POST /api/tasks
     * Create a new task.
     */
    public function store(StoreTaskRequest $request): JsonResponse
    {
        $task = Task::create([
            'title'    => $request->title,
            'due_date' => $request->due_date,
            'priority' => $request->priority,
            'status'   => 'pending', // always starts as pending
        ]);

        return response()->json([
            'message' => 'Task created successfully.',
            'task'    => $task,
        ], 201);
    }

    /**
     * GET /api/tasks?status=optional
     * List tasks sorted by priority (high→low), then due_date asc.
     */
    public function index(Request $request): JsonResponse
    {
        $status = $request->query('status');

        // Validate optional status filter
        if ($status && !in_array($status, ['pending', 'in_progress', 'done'])) {
            return response()->json([
                'message' => 'Invalid status filter. Must be: pending, in_progress, or done.',
            ], 422);
        }

        $tasks = Task::byStatus($status)
                     ->prioritySorted()
                     ->get();

        if ($tasks->isEmpty()) {
            return response()->json([
                'message' => $status
                    ? "No tasks found with status '{$status}'."
                    : 'No tasks found. Create your first task!',
                'tasks'   => [],
            ], 200);
        }

        return response()->json([
            'total' => $tasks->count(),
            'tasks' => $tasks,
        ], 200);
    }

    /**
     * PATCH /api/tasks/{id}/status
     * Advance task status: pending → in_progress → done (no skipping, no reverting).
     */
    public function updateStatus(UpdateTaskStatusRequest $request, int $id): JsonResponse
    {
        $task = Task::find($id);

        if (!$task) {
            return response()->json(['message' => 'Task not found.'], 404);
        }

        $newStatus = $request->status;

        if ($task->status === $newStatus) {
            return response()->json([
                'message' => "Task is already in '{$newStatus}' status.",
                'task'    => $task,
            ], 422);
        }

        if (!$task->canTransitionTo($newStatus)) {
            $nextAllowed = Task::$statusFlow[$task->status] ?? null;

            return response()->json([
                'message'       => $nextAllowed
                    ? "Invalid status transition. From '{$task->status}', you can only move to '{$nextAllowed}'."
                    : "Task is already '{$task->status}' — the final status.",
                'current_status' => $task->status,
                'allowed_next'   => $nextAllowed,
            ], 422);
        }

        $task->update(['status' => $newStatus]);

        return response()->json([
            'message' => 'Task status updated successfully.',
            'task'    => $task->fresh(),
        ], 200);
    }

    /**
     * DELETE /api/tasks/{id}
     * Only tasks with status 'done' can be deleted.
     */
    public function destroy(int $id): JsonResponse
    {
        $task = Task::find($id);

        if (!$task) {
            return response()->json(['message' => 'Task not found.'], 404);
        }

        if ($task->status !== 'done') {
            return response()->json([
                'message' => 'Forbidden. Only tasks with status "done" can be deleted.',
                'current_status' => $task->status,
            ], 403);
        }

        $task->delete();

        return response()->json([
            'message' => 'Task deleted successfully.',
        ], 200);
    }

    /**
     * GET /api/tasks/report?date=YYYY-MM-DD
     * Returns counts per priority × status for a given due_date.
     */
    public function report(Request $request): JsonResponse
    {
        $request->validate([
            'date' => 'required|date|date_format:Y-m-d',
        ]);

        $date  = $request->query('date');
        $tasks = Task::whereDate('due_date', $date)->get();

        $priorities = ['high', 'medium', 'low'];
        $statuses   = ['pending', 'in_progress', 'done'];

        // Build summary matrix
        $summary = [];
        foreach ($priorities as $priority) {
            foreach ($statuses as $status) {
                $summary[$priority][$status] = 0;
            }
        }

        // Tally from DB results
        foreach ($tasks as $task) {
            $summary[$task->priority][$task->status]++;
        }

        return response()->json([
            'date'    => $date,
            'total'   => $tasks->count(),
            'summary' => $summary,
        ], 200);
    }
}
