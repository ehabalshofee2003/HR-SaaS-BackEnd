<?php

namespace App\Http\Controllers\Api\V1\Supervisor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Supervisor\Attendance\AttendanceFilterRequest;
use App\Http\Requests\Supervisor\Attendance\ManualAttendanceRequest;
use App\Http\Requests\Supervisor\Attendance\UpdateAttendanceRequest;
use App\Http\Resources\Supervisor\AttendanceResource;
use App\Models\Identity\User;
use App\Services\Hr\SupervisorAttendanceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Exception;

class AttendanceManagementController extends Controller
{
    public function __construct(
        protected SupervisorAttendanceService $attendanceService
    ) {}

    private function currentSupervisor(): ?User
    {
        $id = Auth::id();
        return $id ? User::find($id) : null;
    }

    public function index(AttendanceFilterRequest $request): JsonResponse
    {
        $supervisor = $this->currentSupervisor();
        if (!$supervisor) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 401);
        }

        $result = $this->attendanceService->list($supervisor, $request->validated());

        return response()->json(['success' => true, 'data' => $result]);
    }

    public function update(UpdateAttendanceRequest $request, int $id): JsonResponse
    {
        $supervisor = $this->currentSupervisor();
        if (!$supervisor) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 401);
        }

        try {
            $record = $this->attendanceService->update($id, $request->validated(), $supervisor);
            return response()->json(['success' => true, 'data' => new AttendanceResource($record)]);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], $e->getCode() ?: 422);
        }
    }

    public function storeManual(ManualAttendanceRequest $request): JsonResponse
    {
        $supervisor = $this->currentSupervisor();
        if (!$supervisor) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 401);
        }

        try {
            $record = $this->attendanceService->createManual($request->validated(), $supervisor);
            return response()->json(['success' => true, 'data' => $record], 201);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], $e->getCode() ?: 422);
        }
    }
}