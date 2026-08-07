<?php

namespace App\Http\Controllers\Api\V1\BranchManager;

use App\Http\Controllers\Controller;
use App\Http\Requests\BranchManager\Department\StoreDepartmentRequest;
use App\Http\Requests\BranchManager\Department\UpdateDepartmentRequest;
use App\Http\Requests\BranchManager\Department\AssignSupervisorRequest;
use App\Services\Organization\DepartmentService;
use App\Models\Identity\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Exception;

class DepartmentController extends Controller
{
    public function __construct(
        protected DepartmentService $departmentService
    ) {}

    public function index(Request $request)
    {
        $user = User::find(Auth::id());
        if (!$user) {
            throw new Exception('غير مصرح.', 401);
        }

        $departments = $this->departmentService->list($user, $request->only(['status', 'search']));

        return response()->json(['data' => $departments]);
    }

    public function store(StoreDepartmentRequest $request)
    {
        $user = User::find(Auth::id());
        if (!$user) {
            throw new Exception('غير مصرح.', 401);
        }

        $department = $this->departmentService->create($user, $request->validated());

        return response()->json(['data' => $department], 201);
    }

    public function show(Request $request, $id)
    {
        $user = User::find(Auth::id());
        if (!$user) {
            throw new Exception('غير مصرح.', 401);
        }

        $result = $this->departmentService->getDetails((int) $id, $user);

        return response()->json(['data' => $result]);
    }

    public function update(UpdateDepartmentRequest $request, $id)
    {
        $user = User::find(Auth::id());
        if (!$user) {
            throw new Exception('غير مصرح.', 401);
        }

        $department = $this->departmentService->update((int) $id, $request->validated(), $user);

        return response()->json(['data' => $department]);
    }

    public function destroy(Request $request, $id)
    {
        $user = User::find(Auth::id());
        if (!$user) {
            throw new Exception('غير مصرح.', 401);
        }

        $this->departmentService->delete((int) $id, $user);

        return response()->json(['message' => 'تم حذف القسم بنجاح.']);
    }

    public function toggleStatus(Request $request, $id)
    {
        $user = User::find(Auth::id());
        if (!$user) {
            throw new Exception('غير مصرح.', 401);
        }

        $department = $this->departmentService->toggleStatus((int) $id, $user);

        return response()->json(['data' => $department]);
    }

    public function assignSupervisor(AssignSupervisorRequest $request, $id)
    {
        $user = User::find(Auth::id());
        if (!$user) {
            throw new Exception('غير مصرح.', 401);
        }

        $department = $this->departmentService->assignSupervisor(
            (int) $id,
            $request->validated()['supervisor_user_id'],
            $user
        );

        return response()->json(['data' => $department]);
    }
}