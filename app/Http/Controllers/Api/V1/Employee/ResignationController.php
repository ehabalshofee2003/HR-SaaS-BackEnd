<?php

namespace App\Http\Controllers\Api\V1\Employee;

use App\Http\Controllers\Controller;
use App\Http\Requests\Employee\StoreResignationRequest;
use App\Http\Resources\Employee\ResignationResource;
use App\Services\Hr\ResignationService;
use Illuminate\Http\JsonResponse;

class ResignationController extends Controller
{
    public function __construct(private ResignationService $resignationService) {}

    public function store(StoreResignationRequest $request): JsonResponse
    {
        $result = $this->resignationService->submit($request->validated());
        return response()->json([
            'message' => $result['message'],
            'data' => new ResignationResource($result['data'])
        ], $result['code']);
    }

    public function index(): JsonResponse
    {
        $resignations = $this->resignationService->list();
        return ResignationResource::collection($resignations)->response();
    }

    public function show($id): JsonResponse
    {
        $result = $this->resignationService->details($id);

        if (!$result['success']) {
            return response()->json(['message' => $result['message']], $result['code']);
        }

        return response()->json([
            'data' => new ResignationResource($result['data'])
        ]);
    }

    public function withdraw($id): JsonResponse
    {
        $result = $this->resignationService->withdraw($id);

        if (!$result['success']) {
            return response()->json(['message' => $result['message']], $result['code']);
        }

        return response()->json([
            'message' => $result['message'],
            'data' => new ResignationResource($result['data'])
        ]);
    }
}