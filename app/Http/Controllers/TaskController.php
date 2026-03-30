<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTaskRequest;
use App\Http\Requests\UpdateTaskStatusRequest;
use App\Http\Resources\TaskResource;
use App\Models\Task;
use App\Services\TaskService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TaskController extends Controller
{
    public function __construct(protected TaskService $taskService)
    {
    }

    /**
     * POST /api/tasks
     */
    public function store(StoreTaskRequest $request): JsonResponse
    {
        $task = $this->taskService->createTask($request->validated());

        return response()->json([
            'message' => 'Task created successfully.',
            'task'    => new TaskResource($task),
        ], 201);
    }

    /**
     * GET /api/tasks?status=optional
     */
    public function index(Request $request): JsonResponse
    {
        $status = $request->query('status');

        if ($status && !in_array($status, ['pending', 'in_progress', 'done'])) {
            return response()->json([
                'message' => 'Invalid status filter. Must be: pending, in_progress, or done.',
            ], 422);
        }

        $tasks = $this->taskService->getAllTasks($status);

        if ($tasks->isEmpty()) {
            return response()->json([
                'message' => $status
                    ? "No tasks found with status '{$status}'."
                    : 'No tasks found. Create your first task!',
                'tasks' => [],
            ]);
        }

        return response()->json([
            'total' => $tasks->count(),
            'tasks' => TaskResource::collection($tasks),
        ]);
    }

    /**
     * PATCH /api/tasks/{id}/status
     */
    public function updateStatus(UpdateTaskStatusRequest $request, int $id): JsonResponse
    {
        $task = Task::find($id);

        if (!$task) {
            return response()->json(['message' => 'Task not found.'], 404);
        }

        try {
            $updated = $this->taskService->updateStatus($task, $request->status);
            return response()->json([
                'message' => 'Task status updated successfully.',
                'task'    => new TaskResource($updated),
            ]);
        } catch (\InvalidArgumentException $e) {
            $code = $e->getCode() ?: 422;
            return response()->json(['message' => $e->getMessage()], $code);
        }
    }

    /**
     * DELETE /api/tasks/{id}
     */
    public function destroy(int $id): JsonResponse
    {
        $task = Task::find($id);

        if (!$task) {
            return response()->json(['message' => 'Task not found.'], 404);
        }

        try {
            $this->taskService->deleteTask($task);
            return response()->json(['message' => 'Task deleted successfully.']);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 403);
        }
    }

    /**
     * GET /api/tasks/report?date=YYYY-MM-DD
     */
    public function report(Request $request): JsonResponse
    {
        $request->validate([
            'date' => 'required|date|date_format:Y-m-d',
        ]);

        $report = $this->taskService->getDailyReport($request->query('date'));

        return response()->json($report);
    }
}