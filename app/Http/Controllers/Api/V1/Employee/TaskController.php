<?php

namespace App\Http\Controllers\Api\V1\Employee;

use App\Http\Controllers\Controller;
use App\Http\Resources\Employee\TaskResource;
use App\Services\Hr\TaskService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\JsonResponse;
use App\Http\Resources\Employee\HomeTaskResource;
use App\Http\Resources\Employee\TaskDetailResource;

class TaskController extends Controller
{
    public function __construct(
        private TaskService $taskService
    ) {}

    /**
     * GET /api/v1/employees/tasks
     */
    public function index(Request $request)
    {
        $user = \App\Models\Identity\User::find(Auth::id());

        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated.'], 401);
        }

        $filters = $request->only(['status', 'per_page']);
        $tasks = $this->taskService->getEmployeeTasks($user, $filters);

        return response()->json([
            'success' => true,
            'data' => TaskResource::collection($tasks),
            'meta' => [
                'current_page' => $tasks->currentPage(),
                'last_page' => $tasks->lastPage(),
                'total' => $tasks->total(),
            ]
        ]);
    }
    public function home(): JsonResponse
    {
        $user = \App\Models\Identity\User::find(Auth::id());

        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        $tasks = $this->taskService->getHomeTasks($user->id);

        return response()->json([
            'data' => HomeTaskResource::collection($tasks),
        ]);
    }
    public function show($id): JsonResponse    {
        $user = \App\Models\Identity\User::find(Auth::id());

        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        $task = $this->taskService->getTaskDetail($id, $user->id);

        if (!$task) {
            return response()->json(['message' => 'Task not found'], 404);
        }

        return response()->json([
            'data' => new TaskDetailResource($task),
        ]);
    }
    public function start($id): JsonResponse
    {
        $user = \App\Models\Identity\User::find(Auth::id());

        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        $result = $this->taskService->startTask((int) $id, $user->id);

        if (!$result['success']) {
            return response()->json(['message' => $result['message']], $result['code']);
        }

        return response()->json([
            'data' => new TaskDetailResource($result['task']),
        ]);
    }

    public function complete($id): JsonResponse
    {
        $user = \App\Models\Identity\User::find(Auth::id());

        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        $result = $this->taskService->completeTask((int) $id, $user->id);

        if (!$result['success']) {
            return response()->json(['message' => $result['message']], $result['code']);
        }

        return response()->json([
            'data' => new TaskDetailResource($result['task']),
        ]);
    }
}