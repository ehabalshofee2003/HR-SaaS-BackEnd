<?php

namespace App\Http\Controllers\Api\V1\Supervisor;

use App\Http\Controllers\Controller;
use App\Services\Supervisor\TaskService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class EmployeeTaskController extends Controller
{
    public function __construct(
        private TaskService $taskService,
    ) {}

    public function index(int $id): JsonResponse
    {
        $supervisorId = Auth::id();

        if (!$supervisorId) {
            return response()->json(['success' => false, 'message' => 'غير مصرح.'], 401);
        }

        $tasks = $this->taskService->listForEmployee($id, $supervisorId);

        return response()->json(['success' => true, 'data' => $tasks]);
    }
}