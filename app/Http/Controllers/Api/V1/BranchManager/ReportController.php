<?php

namespace App\Http\Controllers\Api\V1\BranchManager;

use App\Http\Controllers\Controller;
use App\Http\Requests\BranchManager\Report\ReportFilterRequest;
use App\Services\Reports\ReportService;
use App\Models\Identity\User;
use Illuminate\Support\Facades\Auth;
use Exception;

class ReportController extends Controller
{
    public function __construct(
        protected ReportService $reportService
    ) {}

    public function attendance(ReportFilterRequest $request)
    {
        $user = User::find(Auth::id());
        if (!$user) throw new Exception('غير مصرح.', 401);

        return response()->json(['data' => $this->reportService->attendance($user, $request->validated())]);
    }

    public function tasks(ReportFilterRequest $request)
    {
        $user = User::find(Auth::id());
        if (!$user) throw new Exception('غير مصرح.', 401);

        return response()->json(['data' => $this->reportService->tasks($user, $request->validated())]);
    }

    public function financial(ReportFilterRequest $request)
    {
        $user = User::find(Auth::id());
        if (!$user) throw new Exception('غير مصرح.', 401);

        return response()->json(['data' => $this->reportService->financial($user, $request->validated())]);
    }

    public function performance(ReportFilterRequest $request)
    {
        $user = User::find(Auth::id());
        if (!$user) throw new Exception('غير مصرح.', 401);

        return response()->json(['data' => $this->reportService->performance($user, $request->validated())]);
    }
}