<?php

namespace Tests\Feature;

use App\Models\Task;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskApiTest extends TestCase
{
    use RefreshDatabase;

    // Helpers

    private function makeTask(array $overrides = []): Task
    {
        return Task::create(array_merge([
            'title'    => 'Test task',
            'due_date' => Carbon::today()->addDays(3)->format('Y-m-d'),
            'priority' => 'medium',
            'status'   => 'pending',
        ], $overrides));
    }

    // Create

    public function test_can_create_a_task(): void
    {
        $response = $this->postJson('/api/tasks', [
            'title'    => 'My new task',
            'due_date' => Carbon::today()->addDays(2)->format('Y-m-d'),
            'priority' => 'high',
        ]);

        $response->assertStatus(201)
                 ->assertJsonPath('task.title', 'My new task')
                 ->assertJsonPath('task.status', 'pending')
                 ->assertJsonPath('task.priority', 'high');
    }

    public function test_cannot_create_task_with_past_due_date(): void
    {
        $response = $this->postJson('/api/tasks', [
            'title'    => 'Late task',
            'due_date' => '2020-01-01',
            'priority' => 'low',
        ]);

        $response->assertStatus(422);
    }

    public function test_cannot_create_duplicate_title_on_same_due_date(): void
    {
        $date = Carbon::today()->addDays(1)->format('Y-m-d');

        $this->makeTask(['title' => 'Duplicate task', 'due_date' => $date]);

        $response = $this->postJson('/api/tasks', [
            'title'    => 'Duplicate task',
            'due_date' => $date,
            'priority' => 'medium',
        ]);

        $response->assertStatus(422);
    }

    // List

    public function test_can_list_tasks(): void
    {
        $this->makeTask(['priority' => 'high']);
        $this->makeTask(['title' => 'Another task', 'priority' => 'low']);

        $response = $this->getJson('/api/tasks');

        $response->assertStatus(200)
                 ->assertJsonPath('total', 2);
    }

    public function test_can_filter_tasks_by_status(): void
    {
        $this->makeTask(['status' => 'pending']);
        $this->makeTask(['title' => 'Done task', 'status' => 'done']);

        $response = $this->getJson('/api/tasks?status=pending');

        $response->assertStatus(200)
                 ->assertJsonPath('total', 1);
    }

    // Status update

    public function test_can_advance_status_from_pending_to_in_progress(): void
    {
        $task = $this->makeTask(['status' => 'pending']);

        $response = $this->patchJson("/api/tasks/{$task->id}/status", [
            'status' => 'in_progress',
        ]);

        $response->assertStatus(200)
                 ->assertJsonPath('task.status', 'in_progress');
    }

    public function test_cannot_skip_status(): void
    {
        $task = $this->makeTask(['status' => 'pending']);

        $response = $this->patchJson("/api/tasks/{$task->id}/status", [
            'status' => 'done',
        ]);

        $response->assertStatus(422);
    }

    public function test_cannot_revert_status(): void
    {
        $task = $this->makeTask(['status' => 'in_progress']);

        $response = $this->patchJson("/api/tasks/{$task->id}/status", [
            'status' => 'pending',
        ]);

        $response->assertStatus(422);
    }

    // Delete

    public function test_can_delete_a_done_task(): void
    {
        $task = $this->makeTask(['status' => 'done']);

        $response = $this->deleteJson("/api/tasks/{$task->id}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('tasks', ['id' => $task->id]);
    }

    public function test_cannot_delete_a_pending_task(): void
    {
        $task = $this->makeTask(['status' => 'pending']);

        $response = $this->deleteJson("/api/tasks/{$task->id}");

        $response->assertStatus(403);
    }

    // Report

    public function test_daily_report_returns_correct_structure(): void
    {
        $date = Carbon::today()->addDays(1)->format('Y-m-d');
        $this->makeTask(['due_date' => $date, 'priority' => 'high']);

        $response = $this->getJson("/api/tasks/report?date={$date}");

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'date',
                     'total',
                     'summary' => [
                         'high'   => ['pending', 'in_progress', 'done'],
                         'medium' => ['pending', 'in_progress', 'done'],
                         'low'    => ['pending', 'in_progress', 'done'],
                     ],
                 ]);
    }
}