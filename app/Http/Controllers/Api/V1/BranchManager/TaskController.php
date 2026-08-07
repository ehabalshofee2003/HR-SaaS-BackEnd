<?php

namespace App\Http\Controllers\Api\V1\BranchManager;

use App\Http\Controllers\Controller;
use App\Http\Requests\BranchManager\Task\StoreGeneralTaskRequest;
use App\Http\Requests\BranchManager\Task\UpdateGeneralTaskRequest;
use App\Services\Hr\TaskService;
use App\Models\Identity\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Exception;

class TaskController extends Controller
{
    public function __construct(
        protected TaskService $taskService
    ) {}

    public function index(Request $request)
    {
        $user = User::find(Auth::id());
        if (!$user) {
            throw new Exception('غير مصرح.', 401);
        }

        $tasks = $this->taskService->list($user, $request->only(['status', 'department_id']));

        return response()->json(['data' => $tasks]);
    }

    public function store(StoreGeneralTaskRequest $request)
    {
        $user = User::find(Auth::id());
        if (!$user) {
            throw new Exception('غير مصرح.', 401);
        }

        $task = $this->taskService->createGeneral($user, $request->validated());

        return response()->json(['data' => $task], 201);
    }

    public function show(Request $request, $id)
    {
        $user = User::find(Auth::id());
        if (!$user) {
            throw new Exception('غير مصرح.', 401);
        }

        $task = $this->taskService->getDetails((int) $id, $user);

        return response()->json(['data' => $task]);
    }

    public function update(UpdateGeneralTaskRequest $request, $id)
    {
        $user = User::find(Auth::id());
        if (!$user) {
            throw new Exception('غير مصرح.', 401);
        }

        $task = $this->taskService->updateGeneral((int) $id, $request->validated(), $user);

        return response()->json(['data' => $task]);
    }

    public function destroy(Request $request, $id)
    {
        $user = User::find(Auth::id());
        if (!$user) {
            throw new Exception('غير مصرح.', 401);
        }

        $this->taskService->deleteGeneral((int) $id, $user);

        return response()->json(['message' => 'تم حذف المهمة بنجاح.']);
    }
}