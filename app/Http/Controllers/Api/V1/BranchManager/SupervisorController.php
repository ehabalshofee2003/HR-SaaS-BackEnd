<?php

namespace App\Http\Controllers\Api\V1\BranchManager;

use App\Http\Controllers\Controller;
use App\Http\Requests\BranchManager\Supervisor\StoreSupervisorRequest;
use App\Http\Requests\BranchManager\Supervisor\UpdateSupervisorRequest;
use App\Services\Organization\SupervisorService;
use App\Models\Identity\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Exception;

class SupervisorController extends Controller
{
    public function __construct(
        protected SupervisorService $supervisorService
    ) {}

    public function index(Request $request)
    {
        $user = User::find(Auth::id());
        if (!$user) {
            throw new Exception('غير مصرح.', 401);
        }

        $supervisors = $this->supervisorService->list($user, $request->only(['status', 'department_id', 'search']));

        return response()->json(['data' => $supervisors]);
    }

    public function store(StoreSupervisorRequest $request)
    {
        $user = User::find(Auth::id());
        if (!$user) {
            throw new Exception('غير مصرح.', 401);
        }

        $supervisor = $this->supervisorService->create($user, $request->validated());

        return response()->json(['data' => $supervisor], 201);
    }

    public function show(Request $request, $id)
    {
        $user = User::find(Auth::id());
        if (!$user) {
            throw new Exception('غير مصرح.', 401);
        }

        $result = $this->supervisorService->getDetails((int) $id, $user);

        return response()->json(['data' => $result]);
    }

    public function update(UpdateSupervisorRequest $request, $id)
    {
        $user = User::find(Auth::id());
        if (!$user) {
            throw new Exception('غير مصرح.', 401);
        }

        $supervisor = $this->supervisorService->update((int) $id, $request->validated(), $user);

        return response()->json(['data' => $supervisor]);
    }

    public function destroy(Request $request, $id)
    {
        $user = User::find(Auth::id());
        if (!$user) {
            throw new Exception('غير مصرح.', 401);
        }

        $this->supervisorService->delete((int) $id, $user);

        return response()->json(['message' => 'تم حذف المشرف بنجاح.']);
    }

    public function toggleStatus(Request $request, $id)
    {
        $user = User::find(Auth::id());
        if (!$user) {
            throw new Exception('غير مصرح.', 401);
        }

        $supervisor = $this->supervisorService->toggleStatus((int) $id, $user);

        return response()->json(['data' => $supervisor]);
    }

    public function resetPassword(Request $request, $id)
    {
        $user = User::find(Auth::id());
        if (!$user) {
            throw new Exception('غير مصرح.', 401);
        }

        $newPassword = $this->supervisorService->resetPassword((int) $id, $user);

        return response()->json(['message' => 'تم إعادة تعيين كلمة المرور.', 'temporary_password' => $newPassword]);
    }
}