<?php

namespace App\Services;

use App\Models\Task;
use Illuminate\Support\Collection;

class TaskService
{
    /**
     * Return all tasks, optionally filtered by status.
     * Sorted by priority (high → medium → low) then due_date asc.
     */
    public function getAllTasks(?string $status): Collection
    {
        return Task::byStatus($status)
                   ->prioritySorted()
                   ->get();
    }

    /**
     * Create a new task. Status always starts as pending.
     */
    public function createTask(array $data): Task
    {
        return Task::create([
            'title'    => $data['title'],
            'due_date' => $data['due_date'],
            'priority' => $data['priority'],
            'status'   => 'pending',
        ]);
    }

    /**
     * Advance a task to the next status.
     * Returns the updated task or throws an exception if transition is invalid.
     */
    public function updateStatus(Task $task, string $newStatus): Task
    {
        if ($task->status === $newStatus) {
            throw new \InvalidArgumentException(
                "Task is already in '{$newStatus}' status."
            );
        }

        if (!$task->canTransitionTo($newStatus)) {
            $nextAllowed = Task::$statusFlow[$task->status] ?? null;
            $message = $nextAllowed
                ? "Invalid transition. From '{$task->status}' you can only move to '{$nextAllowed}'."
                : "Task is already '{$task->status}' — the final status.";

            throw new \InvalidArgumentException($message, 422);
        }

        $task->update(['status' => $newStatus]);
        return $task->fresh();
    }

    /**
     * Delete a task. Only done tasks can be deleted.
     */
    public function deleteTask(Task $task): void
    {
        if ($task->status !== 'done') {
            throw new \InvalidArgumentException(
                'Only tasks with status "done" can be deleted.',
                403
            );
        }

        $task->delete();
    }

    /**
     * Build a daily report for a given date.
     */
    public function getDailyReport(string $date): array
    {
        $tasks      = Task::whereDate('due_date', $date)->get();
        $priorities = ['high', 'medium', 'low'];
        $statuses   = ['pending', 'in_progress', 'done'];

        $summary = [];
        foreach ($priorities as $priority) {
            foreach ($statuses as $status) {
                $summary[$priority][$status] = 0;
            }
        }

        foreach ($tasks as $task) {
            $summary[$task->priority][$task->status]++;
        }

        return [
            'date'    => $date,
            'total'   => $tasks->count(),
            'summary' => $summary,
        ];
    }
}