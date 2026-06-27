<?php

namespace App\Http\Controllers\Api\V1\Employee;

use App\Http\Controllers\Controller;
use App\Http\Requests\Employee\StoreComplaintRequest;
use App\Http\Resources\Employee\ComplaintResource;
use App\Services\Hr\ComplaintService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class ComplaintController extends Controller
{
    protected $service;

    public function __construct(ComplaintService $service)
    {
        $this->service = $service;
    }

public function store(StoreComplaintRequest $request): JsonResponse
{
    // تطبيق القاعدة 2: Auth Type Hinting
    $user = \App\Models\Identity\User::find(Auth::id());
    if (!$user) {
        return response()->json(['message' => 'User not found'], 404);
    }

    // الكنترولر لا يعرف شيئاً عن الفروع والأقسام، يطلب الشركة فقط
    $companyId = $user->getCurrentCompanyId();

    if (!$companyId) {
        return response()->json(['message' => 'User is not assigned to a valid company hierarchy.'], 403);
    }

    $complaint = $this->service->storeComplaint($request, $user->id, $companyId);

    return response()->json([
        'message' => 'Complaint submitted successfully.',
        'data'    => new ComplaintResource($complaint)
    ], 201);
}

    public function index(): JsonResponse
    {
        // تطبيق القاعدة 2: Auth Type Hinting
        $user = \App\Models\Identity\User::find(Auth::id());
        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        $complaints = $this->service->getEmployeeComplaints($user->id);

        return response()->json([
            'data' => ComplaintResource::collection($complaints)
        ], 200);
    }
}