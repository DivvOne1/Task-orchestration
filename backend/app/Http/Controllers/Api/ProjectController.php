<?php

namespace App\Http\Controllers\Api;

use App\Enums\ProjectStatus;
use App\Http\Controllers\Controller;
use App\Models\Project;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Enum;

class ProjectController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $projects = Project::query()
            ->with(['owner:id,name,email', 'members:id,name,email'])
            ->where(function ($query) use ($request) {
                $query->where('owner_id', $request->user()->id)
                    ->orWhereHas('members', function ($memberQuery) use ($request) {
                        $memberQuery->where('users.id', $request->user()->id);
                    });
            })
            ->latest()
            ->get();

        return response()->json($projects);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'status' => ['required', new Enum(ProjectStatus::class)],
        ]);

        $project = Project::create([
            ...$validated,
            'owner_id' => $request->user()->id,
        ]);

        $project->members()->syncWithoutDetaching([
            $request->user()->id => ['role' => 'owner'],
        ]);

        return response()->json(
            $project->load(['owner:id,name,email', 'members:id,name,email']),
            201
        );
    }

    public function update(Request $request, Project $project): JsonResponse
    {
        abort_unless($project->owner_id === $request->user()->id, 403);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'status' => ['required', new Enum(ProjectStatus::class)],
        ]);

        $project->update($validated);

        return response()->json(
            $project->fresh()->load(['owner:id,name,email', 'members:id,name,email'])
        );
    }

    public function destroy(Request $request, Project $project): JsonResponse
    {
        abort_unless($project->owner_id === $request->user()->id, 403);

        $project->delete();

        return response()->json([
            'message' => 'Project deleted.',
        ]);
    }
}
