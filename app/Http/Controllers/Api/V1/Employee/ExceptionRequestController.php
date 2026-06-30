<?php

namespace App\Http\Controllers\Api\V1\Employee;

use App\Http\Controllers\Controller;
use App\Http\Requests\Employee\StoreExceptionRequest;
use App\Http\Requests\Employee\CancelExceptionRequest;
use App\Http\Resources\Employee\ExceptionRequestResource;
use App\Services\Hr\ExceptionRequestService;
use App\Models\Identity\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class ExceptionRequestController extends Controller
{
    public function __construct(
        private ExceptionRequestService $service
    ) {}

    public function index(): JsonResponse
    {
        $user = User::find(Auth::id());
        if (!$user) {
            return response()->json(['message' => 'User not found'], Response::HTTP_UNAUTHORIZED);
        }

        $exceptions = $this->service->getAll($user);
        return response()->json([
            'data' => ExceptionRequestResource::collection($exceptions),
            'meta' => [
                'current_page' => $exceptions->currentPage(),
                'last_page'    => $exceptions->lastPage(),
                'per_page'     => $exceptions->perPage(),
            ]
        ]);
    }

    public function store(StoreExceptionRequest $request): JsonResponse
    {
        $user = User::find(Auth::id());
        if (!$user) {
            return response()->json(['message' => 'User not found'], Response::HTTP_UNAUTHORIZED);
        }

        $exception = $this->service->store($user, $request);
        return response()->json([
            'message' => 'Exception request created successfully.',
            'data'    => new ExceptionRequestResource($exception)
        ], Response::HTTP_CREATED);
    }

    public function show($id): JsonResponse
    {
        $user = User::find(Auth::id());
        if (!$user) {
            return response()->json(['message' => 'User not found'], Response::HTTP_UNAUTHORIZED);
        }

        $exception = $this->service->getById($user, $id);
        if (!$exception) {
            return response()->json(['message' => 'Exception request not found.'], Response::HTTP_NOT_FOUND);
        }

        return response()->json([
            'data' => new ExceptionRequestResource($exception->load('exceptionType'))
        ]);
    }

    public function cancel(CancelExceptionRequest $request, $id): JsonResponse
    {
        $user = User::find(Auth::id());
        if (!$user) {
            return response()->json(['message' => 'User not found'], Response::HTTP_UNAUTHORIZED);
        }

        $exception = $this->service->cancel($user, $id);
        if (!$exception) {
            return response()->json(['message' => 'Exception request not found or cannot be cancelled.'], Response::HTTP_NOT_FOUND);
        }

        return response()->json([
            'message' => 'Exception request cancelled successfully.',
            'data'    => new ExceptionRequestResource($exception->load('exceptionType'))
        ]);
    }
}