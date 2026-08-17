<?php

namespace App\Http\Controllers\Api\V1\Supervisor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Supervisor\Exception\RejectExceptionRequest;
use App\Models\Identity\User;
use App\Services\Hr\SupervisorExceptionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Exception;

class ExceptionController extends Controller
{
    public function __construct(protected SupervisorExceptionService $service) {}

    public function index(Request $request): JsonResponse
    {
        $supervisorId = Auth::id();
        if (!$supervisorId) return response()->json(['success' => false, 'message' => 'Unauthorized.'], 401);

        return response()->json(['success' => true, 'data' => $this->service->list($supervisorId, $request->only(['status']))]);
    }

    public function show(int $id): JsonResponse
    {
        $supervisorId = Auth::id();
        try {
            return response()->json(['success' => true, 'data' => $this->service->get($id, $supervisorId)]);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], $e->getCode() ?: 404);
        }
    }

    public function forwardToOwner(int $id): JsonResponse
    {
        $supervisorId = Auth::id();
        try {
            return response()->json(['success' => true, 'data' => $this->service->forwardToOwner($id, $supervisorId)]);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], $e->getCode() ?: 422);
        }
    }

    public function reject(RejectExceptionRequest $request, int $id): JsonResponse
    {
        $supervisorId = Auth::id();
        try {
            $data = $this->service->reject($id, $supervisorId, $request->validated()['reason']);
            return response()->json(['success' => true, 'data' => $data]);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], $e->getCode() ?: 422);
        }
    }
}