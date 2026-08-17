<?php

namespace App\Http\Controllers\Api\V1\Supervisor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Supervisor\EmployeeFilterRequest;
use App\Http\Requests\Supervisor\UpdateEmployeeRequest;
use App\Services\Supervisor\EmployeeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use App\Models\Identity\User;
use App\Http\Requests\Supervisor\StoreEmployeeRequest;
use Exception;


class EmployeeController extends Controller
{
    public function __construct(
        private EmployeeService $employeeService,
    ) {}

    public function index(EmployeeFilterRequest $request): JsonResponse
    {
        $supervisorId = Auth::id();

        if (!$supervisorId) {
            return response()->json(['success' => false, 'message' => 'غير مصرح.'], 401);
        }

        $employees = $this->employeeService->list($supervisorId, $request->validated()['status'] ?? null);

        return response()->json(['success' => true, 'data' => $employees]);
    }

    public function show(int $id): JsonResponse
    {
        $supervisorId = Auth::id();

        if (!$supervisorId) {
            return response()->json(['success' => false, 'message' => 'غير مصرح.'], 401);
        }

        $employee = $this->employeeService->get($id, $supervisorId);

        return response()->json(['success' => true, 'data' => $employee]);
    }

    public function update(UpdateEmployeeRequest $request, int $id): JsonResponse
    {
        $supervisorId = Auth::id();

        if (!$supervisorId) {
            return response()->json(['success' => false, 'message' => 'غير مصرح.'], 401);
        }

        $employee = $this->employeeService->update($id, $supervisorId, $request->validated());

        return response()->json(['success' => true, 'data' => $employee]);
    }

    public function attendance(int $id): JsonResponse
    {
        $supervisorId = Auth::id();

        if (!$supervisorId) {
            return response()->json(['success' => false, 'message' => 'غير مصرح.'], 401);
        }

        $attendance = $this->employeeService->attendanceToday($id, $supervisorId);

        return response()->json(['success' => true, 'data' => $attendance]);
    }
    public function store(StoreEmployeeRequest $request): JsonResponse
    {
        $supervisorId = Auth::id();

        if (!$supervisorId) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 401);
        }

        $supervisor = \App\Models\Identity\User::find($supervisorId);
        $employee = $this->employeeService->create($supervisor, $request->validated());

        return response()->json(['success' => true, 'data' => $employee], 201);
    }
}