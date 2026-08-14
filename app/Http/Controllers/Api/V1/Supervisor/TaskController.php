<?php

namespace App\Http\Controllers\Api\V1\Supervisor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Supervisor\CreateTaskRequest;
use App\Http\Requests\Supervisor\TaskFilterRequest;
use App\Http\Requests\Supervisor\UpdateTaskRequest;
use App\Services\Supervisor\TaskManagementService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class TaskController extends Controller
{
    public function __construct(
        private TaskManagementService $taskService,
    ) {}

    private function supervisorId(): ?int
    {
        return Auth::id();
    }

    public function index(TaskFilterRequest $request): JsonResponse
    {
        $supervisorId = $this->supervisorId();

        if (!$supervisorId) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 401);
        }

        $tasks = $this->taskService->list($supervisorId, $request->validated());

        return response()->json(['success' => true, 'data' => $tasks]);
    }

    public function store(CreateTaskRequest $request): JsonResponse
    {
        $supervisorId = $this->supervisorId();

        if (!$supervisorId) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 401);
        }

        $task = $this->taskService->create($supervisorId, $request->validated());

        return response()->json(['success' => true, 'data' => $task], 201);
    }

    public function update(UpdateTaskRequest $request, int $id): JsonResponse
    {
        $supervisorId = $this->supervisorId();

        if (!$supervisorId) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 401);
        }

        $task = $this->taskService->update($id, $supervisorId, $request->validated());

        return response()->json(['success' => true, 'data' => $task]);
    }

    public function destroy(int $id): JsonResponse
    {
        $supervisorId = $this->supervisorId();

        if (!$supervisorId) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 401);
        }

        $result = $this->taskService->delete($id, $supervisorId);

        return response()->json($result);
    }
}