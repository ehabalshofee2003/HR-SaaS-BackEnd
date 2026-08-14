<?php

namespace App\Http\Controllers\Api\V1\Supervisor;

use App\Http\Controllers\Controller;
use App\Services\Supervisor\LeaveService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class EmployeeLeaveController extends Controller
{
    public function __construct(
        private LeaveService $leaveService,
    ) {}

    public function index(int $id): JsonResponse
    {
        $supervisorId = Auth::id();

        if (!$supervisorId) {
            return response()->json(['success' => false, 'message' => 'غير مصرح.'], 401);
        }

        $data = $this->leaveService->getForEmployee($id, $supervisorId);

        return response()->json(['success' => true, 'data' => $data]);
    }
}