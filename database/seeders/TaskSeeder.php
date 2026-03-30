<?php

namespace Database\Seeders;

use App\Models\Task;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class TaskSeeder extends Seeder
{
    public function run(): void
    {
        \App\Models\Task::truncate();
        $today = Carbon::today();

        $tasks = [
            [
                'title'    => 'Fix critical login bug',
                'due_date' => $today->copy()->addDays(1),
                'priority' => 'high',
                'status'   => 'in_progress',
            ],
            [
                'title'    => 'Deploy hotfix to production',
                'due_date' => $today->copy()->addDays(1),
                'priority' => 'high',
                'status'   => 'pending',
            ],
            [
                'title'    => 'Write unit tests for auth module',
                'due_date' => $today->copy()->addDays(3),
                'priority' => 'medium',
                'status'   => 'pending',
            ],
            [
                'title'    => 'Update API documentation',
                'due_date' => $today->copy()->addDays(5),
                'priority' => 'medium',
                'status'   => 'done',
            ],
            [
                'title'    => 'Clean up unused CSS files',
                'due_date' => $today->copy()->addDays(7),
                'priority' => 'low',
                'status'   => 'done',
            ],
            [
                'title'    => 'Code review for pull request #42',
                'due_date' => $today->copy()->addDays(2),
                'priority' => 'high',
                'status'   => 'pending',
            ],
            [
                'title'    => 'Set up CI/CD pipeline',
                'due_date' => $today->copy()->addDays(10),
                'priority' => 'medium',
                'status'   => 'in_progress',
            ],
        ];

        foreach ($tasks as $task) {
            Task::create($task);
        }
    }
}
