<?php

namespace App\Http\Controllers\Api;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\Task;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Enum;

class TaskController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $tasks = Task::query()
            ->with([
                'project:id,title,owner_id',
                'assignee:id,name,email',
                'creator:id,name,email',
            ])
            ->whereHas('project', function ($query) use ($request) {
                $query->where('owner_id', $request->user()->id)
                    ->orWhereHas('members', function ($memberQuery) use ($request) {
                        $memberQuery->where('users.id', $request->user()->id);
                    });
            })
            ->when($request->filled('project_id'), function ($query) use ($request) {
                $query->where('project_id', $request->integer('project_id'));
            })
            ->latest()
            ->get();

        return response()->json($tasks);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $this->validateTask($request);
        $this->findAccessibleProject($request, $validated['project_id']);

        $task = Task::create([
            ...$validated,
            'creator_id' => $request->user()->id,
        ]);

        return response()->json(
            $task->load(['project:id,title,owner_id', 'assignee:id,name,email', 'creator:id,name,email']),
            201
        );
    }

    public function update(Request $request, Task $task): JsonResponse
    {
        $this->authorizeTaskAccess($request, $task);

        $validated = $this->validateTask($request);
        $this->findAccessibleProject($request, $validated['project_id']);
        $task->update($validated);

        return response()->json(
            $task->fresh()->load(['project:id,title,owner_id', 'assignee:id,name,email', 'creator:id,name,email'])
        );
    }

    public function destroy(Request $request, Task $task): JsonResponse
    {
        $this->authorizeTaskAccess($request, $task);

        $task->delete();

        return response()->json([
            'message' => 'Task deleted.',
        ]);
    }

    private function validateTask(Request $request): array
    {
        return $request->validate([
            'project_id' => ['required', 'integer', 'exists:projects,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'status' => ['required', new Enum(TaskStatus::class)],
            'priority' => ['required', new Enum(TaskPriority::class)],
            'assignee_id' => ['nullable', 'integer', 'exists:users,id'],
            'deadline' => ['nullable', 'date'],
        ]);
    }

    private function authorizeTaskAccess(Request $request, Task $task): void
    {
        $isAllowed = $task->creator_id === $request->user()->id
            || $task->project->owner_id === $request->user()->id;

        abort_unless($isAllowed, 403);
    }

    private function findAccessibleProject(Request $request, int $projectId): Project
    {
        return Project::query()
            ->where('id', $projectId)
            ->where(function ($query) use ($request) {
                $query->where('owner_id', $request->user()->id)
                    ->orWhereHas('members', function ($memberQuery) use ($request) {
                        $memberQuery->where('users.id', $request->user()->id);
                    });
            })
            ->firstOrFail();
    }
}
