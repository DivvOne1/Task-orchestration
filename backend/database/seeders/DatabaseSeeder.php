<?php

namespace Database\Seeders;

use App\Enums\ProjectStatus;
use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Enums\UserRole;
use App\Models\Comment;
use App\Models\Notification;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $manager = User::firstOrCreate(
            ['email' => 'manager@taskflow.local'],
            [
                'name' => 'Demo Manager',
                'password' => Hash::make('password'),
                'role' => UserRole::Manager,
                'is_blocked' => false,
            ],
        );

        $assignee = User::firstOrCreate(
            ['email' => 'user@taskflow.local'],
            [
                'name' => 'Demo User',
                'password' => Hash::make('password'),
                'role' => UserRole::User,
                'is_blocked' => false,
            ],
        );

        $project = Project::firstOrCreate(
            ['title' => 'TaskFlow MVP'],
            [
                'description' => 'Demo project seeded for frontend-backend integration.',
                'owner_id' => $manager->id,
                'status' => ProjectStatus::Active,
            ],
        );

        $project->members()->syncWithoutDetaching([
            $manager->id => ['role' => 'owner'],
            $assignee->id => ['role' => 'member'],
        ]);

        $task = Task::firstOrCreate(
            ['title' => 'Connect Vue dashboard to Laravel API'],
            [
                'project_id' => $project->id,
                'description' => 'Visible integration proof between frontend and backend.',
                'status' => TaskStatus::InProgress,
                'priority' => TaskPriority::High,
                'assignee_id' => $assignee->id,
                'creator_id' => $manager->id,
                'deadline' => now()->addDays(3),
            ],
        );

        Comment::firstOrCreate(
            ['task_id' => $task->id, 'user_id' => $manager->id],
            ['text' => 'Dashboard now consumes real data from Laravel.'],
        );

        Notification::firstOrCreate(
            ['user_id' => $assignee->id, 'type' => 'task_assigned'],
            [
                'message' => 'You have been assigned a demo task.',
                'payload' => ['task_id' => $task->id, 'project_id' => $project->id],
                'is_read' => false,
            ],
        );
    }
}
