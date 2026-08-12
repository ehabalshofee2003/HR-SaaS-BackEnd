<?php

namespace App\Http\Controllers\Api\V1\Owner;

use App\Http\Controllers\Controller;
use App\Http\Requests\Owner\BranchRequest;
use App\Services\Owner\BranchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Exception;

class BranchController extends Controller
{
    public function __construct(
        protected BranchService $branchService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $ownerId = Auth::id();
        if (!$ownerId) {
            return response()->json(['success' => false, 'message' => 'غير مصرح.'], 401);
        }

        $filters = $request->only(['status', 'search']);
        $branches = $this->branchService->list($ownerId, $filters);

        return response()->json(['success' => true, 'data' => $branches]);
    }

    public function store(BranchRequest $request): JsonResponse
    {
        $ownerId = Auth::id();
        if (!$ownerId) {
            return response()->json(['success' => false, 'message' => 'غير مصرح.'], 401);
        }

        try {
            $branch = $this->branchService->create($ownerId, $request->validated());
            return response()->json(['success' => true, 'data' => $branch], 201);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], $e->getCode() ?: 422);
        }
    }

    public function show(int $id): JsonResponse
    {
        $ownerId = Auth::id();
        if (!$ownerId) {
            return response()->json(['success' => false, 'message' => 'غير مصرح.'], 401);
        }

        try {
            $branch = $this->branchService->getDetails($id, $ownerId);
            return response()->json(['success' => true, 'data' => $branch]);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], $e->getCode() ?: 404);
        }
    }

    public function update(BranchRequest $request, int $id): JsonResponse
    {
        $ownerId = Auth::id();
        if (!$ownerId) {
            return response()->json(['success' => false, 'message' => 'غير مصرح.'], 401);
        }

        try {
            $branch = $this->branchService->update($id, $ownerId, $request->validated());
            return response()->json(['success' => true, 'data' => $branch]);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], $e->getCode() ?: 422);
        }
    }

    public function destroy(int $id): JsonResponse
    {
        $ownerId = Auth::id();
        if (!$ownerId) {
            return response()->json(['success' => false, 'message' => 'غير مصرح.'], 401);
        }

        try {
            $result = $this->branchService->delete($id, $ownerId);
            return response()->json($result);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], $e->getCode() ?: 404);
        }
    }
}