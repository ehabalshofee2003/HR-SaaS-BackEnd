<?php

namespace App\Http\Controllers\Api\V1\Employee;

use App\Http\Controllers\Controller;
use App\Http\Resources\Employee\EvaluationListResource;
use App\Http\Resources\Employee\EvaluationDetailsResource;
use App\Services\Hr\EvaluationService;
use App\Models\Identity\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class EvaluationController extends Controller
{
    public function __construct(private EvaluationService $service) {}

    public function index(): JsonResponse
    {
        $user = User::find(Auth::id());
        if (!$user) return response()->json(['message' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);

        $evaluations = $this->service->getAll($user);
        return response()->json([
            'data' => EvaluationListResource::collection($evaluations),
            'meta' => [
                'current_page' => $evaluations->currentPage(),
                'last_page'    => $evaluations->lastPage(),
                'per_page'     => $evaluations->perPage(),
            ]
        ]);
    }

    public function show($id): JsonResponse
    {
        $user = User::find(Auth::id());
        if (!$user) return response()->json(['message' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);

        $evaluation = $this->service->getById($user, $id);
        if (!$evaluation) {
            return response()->json(['message' => 'Evaluation not found.'], Response::HTTP_NOT_FOUND);
        }

        return response()->json([
            'data' => new EvaluationDetailsResource($evaluation->load('supervisor', 'scores.criteria'))        ]);
    }

    public function markRead($id): JsonResponse
    {
        $user = User::find(Auth::id());
        if (!$user) return response()->json(['message' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);

        $evaluation = $this->service->markAsRead($user, $id);
        if (!$evaluation) {
            return response()->json(['message' => 'Evaluation not found.'], Response::HTTP_NOT_FOUND);
        }

        return response()->json([
            'message' => 'Evaluation marked as read successfully.',
            'data'    => new EvaluationListResource($evaluation->load('supervisor'))
        ]);
    }
}