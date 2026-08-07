<?php

namespace App\Http\Controllers\Api\V1\BranchManager;

use App\Http\Controllers\Controller;
use App\Http\Requests\BranchManager\Evaluation\StoreEvaluationRequest;
use App\Services\Hr\EvaluationService;
use App\Models\Identity\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Exception;

class EvaluationController extends Controller
{
    public function __construct(
        protected EvaluationService $evaluationService
    ) {}

    public function index(Request $request)
    {
        $user = User::find(Auth::id());
        if (!$user) {
            throw new Exception('غير مصرح.', 401);
        }

        $evaluations = $this->evaluationService->list($user, $request->only(['status', 'department_id', 'employee_id', 'supervisor_id']));

        return response()->json(['data' => $evaluations]);
    }

    public function store(StoreEvaluationRequest $request)
    {
        $user = User::find(Auth::id());
        if (!$user) {
            throw new Exception('غير مصرح.', 401);
        }

        $evaluation = $this->evaluationService->evaluate($user, $request->validated());

        return response()->json(['data' => $evaluation], 201);
    }

    public function show(Request $request, $id)
    {
        $user = User::find(Auth::id());
        if (!$user) {
            throw new Exception('غير مصرح.', 401);
        }

        $evaluation = $this->evaluationService->getDetails((int) $id, $user);

        return response()->json(['data' => $evaluation]);
    }
}