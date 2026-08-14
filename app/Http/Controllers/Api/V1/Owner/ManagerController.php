<?php

namespace App\Http\Controllers\Api\V1\Owner;

use App\Http\Controllers\Controller;
use App\Http\Requests\Owner\ManagerRequest;
use App\Models\Identity\User;
use App\Services\Owner\ManagerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ManagerController extends Controller
{
    public function __construct(
        private ManagerService $managerService,
    ) {}

    private function companyId(): ?int
    {
        $userId = Auth::id();

        if (!$userId) {
            return null;
        }

        $user = User::find($userId);

        return $user?->company_id;
    }

    public function index(Request $request): JsonResponse
    {
        $companyId = $this->companyId();

        if (!$companyId) {
            return response()->json(['success' => false, 'message' => 'غير مصرح.'], 401);
        }

        $filters = $request->only(['status', 'branch_id', 'search']);
        $managers = $this->managerService->list($companyId, $filters);

        return response()->json(['success' => true, 'data' => $managers]);
    }

    public function store(ManagerRequest $request): JsonResponse
    {
        $companyId = $this->companyId();

        if (!$companyId) {
            return response()->json(['success' => false, 'message' => 'غير مصرح.'], 401);
        }

        $data = $request->validated();

        if ($request->hasFile('avatar')) {
            $data['avatar'] = $request->file('avatar');
        }

        $manager = $this->managerService->create($companyId, $data);

        return response()->json(['success' => true, 'data' => $manager], 201);
    }

    public function show(int $id): JsonResponse
    {
        $companyId = $this->companyId();

        if (!$companyId) {
            return response()->json(['success' => false, 'message' => 'غير مصرح.'], 401);
        }

        $manager = $this->managerService->get($id, $companyId);

        return response()->json(['success' => true, 'data' => $manager]);
    }

    public function update(ManagerRequest $request, int $id): JsonResponse
    {
        $companyId = $this->companyId();

        if (!$companyId) {
            return response()->json(['success' => false, 'message' => 'غير مصرح.'], 401);
        }

        $data = $request->validated();

        if ($request->hasFile('avatar')) {
            $data['avatar'] = $request->file('avatar');
        }

        $manager = $this->managerService->update($id, $companyId, $data);

        return response()->json(['success' => true, 'data' => $manager]);
    }

    public function destroy(int $id): JsonResponse
    {
        $companyId = $this->companyId();

        if (!$companyId) {
            return response()->json(['success' => false, 'message' => 'غير مصرح.'], 401);
        }

        $result = $this->managerService->delete($id, $companyId);

        return response()->json($result);
    }
}