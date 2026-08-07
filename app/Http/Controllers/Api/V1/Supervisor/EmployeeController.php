<?php

namespace App\Http\Controllers\Api\V1\Supervisor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Supervisor\StoreEmployeeRequest;
use App\Http\Requests\Supervisor\UpdateEmployeeRequest;
use App\Services\Hr\SupervisorEmployeeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use App\Models\Identity\User;

class EmployeeController extends Controller
{
    public function __construct(private SupervisorEmployeeService $service) {}

    public function index(): JsonResponse
    {
        $user = User::find(Auth::id());
        if (!$user) return response()->json(['message' => 'Unauthorized'], 401);

        // الطريقة الصحيحة لإرجاع Pagination مع Resource في لارافيل
        return $this->service->getTeamEmployees($user)
            ->additional(['status' => 'success'])
            ->response();
    }

    public function store(StoreEmployeeRequest $request): JsonResponse
    {
        $user = User::find(Auth::id());
        if (!$user) return response()->json(['message' => 'Unauthorized'], 401);

        $result = $this->service->createEmployee($user, $request->validated(), $request->file('avatar'), $request->file('documents'));
        
        return response()->json([
            'status' => 'success',
            'message' => 'Employee created successfully.',
            'data' => $result // سنعدل هذا في السيرفس الآن
        ], 201);
    }

    public function show(int $id): JsonResponse
    {
        $user = User::find(Auth::id());
        if (!$user) return response()->json(['message' => 'Unauthorized'], 401);

        return response()->json([
            'status' => 'success',
            'data' => $this->service->getEmployeeDetails($user, $id)
        ]);
    }

    public function update(UpdateEmployeeRequest $request, int $id): JsonResponse
    {
        $user = User::find(Auth::id());
        if (!$user) return response()->json(['message' => 'Unauthorized'], 401);

        $result = $this->service->updateEmployee($user, $id, $request->validated(), $request->file('avatar'), $request->file('documents'));

        return response()->json([
            'status' => 'success',
            'message' => 'Employee updated successfully.',
            'data' => $result
        ]);
    }
        public function getDocuments(int $id): JsonResponse
    {
        $user = User::find(Auth::id());
        if (!$user) return response()->json(['message' => 'Unauthorized'], 401);

        return response()->json([
            'status' => 'success',
            'data' => $this->service->getEmployeeDocuments($user, $id)
        ]);
    }

    public function deleteDocument(int $employee_id, int $document_id): JsonResponse
    {
        $user = User::find(Auth::id());
        if (!$user) return response()->json(['message' => 'Unauthorized'], 401);

        $this->service->deleteEmployeeDocument($user, $employee_id, $document_id);

        return response()->json([
            'status' => 'success',
            'message' => 'Document deleted successfully.'
        ]);
    }
}