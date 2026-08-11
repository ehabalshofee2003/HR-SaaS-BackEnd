<?php

namespace App\Http\Controllers\Api\V1\BranchManager;

use App\Http\Controllers\Controller;
use App\Http\Requests\BranchManager\Workshop\StoreWorkshopRequest;
use App\Http\Requests\BranchManager\Workshop\UpdateWorkshopRequest;
use App\Http\Requests\BranchManager\Workshop\CancelWorkshopRequest;
use App\Services\Hr\WorkshopService;
use App\Models\Identity\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Exception;

class WorkshopController extends Controller
{
    public function __construct(
        protected WorkshopService $workshopService
    ) {}

    public function index(Request $request)
    {
        $user = User::find(Auth::id());
        if (!$user) throw new Exception('غير مصرح.', 401);

        $workshops = $this->workshopService->list($user, $request->only(['status']));

        return response()->json(['data' => $workshops]);
    }

    public function store(StoreWorkshopRequest $request)
    {
        $user = User::find(Auth::id());
        if (!$user) throw new Exception('غير مصرح.', 401);

        $workshop = $this->workshopService->create($user, $request->validated());

        return response()->json(['data' => $workshop], 201);
    }

    public function show(Request $request, $id)
    {
        $user = User::find(Auth::id());
        if (!$user) throw new Exception('غير مصرح.', 401);

        $workshop = $this->workshopService->getDetails((int) $id, $user);

        return response()->json(['data' => $workshop]);
    }

    public function update(UpdateWorkshopRequest $request, $id)
    {
        $user = User::find(Auth::id());
        if (!$user) throw new Exception('غير مصرح.', 401);

        $workshop = $this->workshopService->update((int) $id, $request->validated(), $user);

        return response()->json(['data' => $workshop]);
    }

    public function cancel(CancelWorkshopRequest $request, $id)
    {
        $user = User::find(Auth::id());
        if (!$user) throw new Exception('غير مصرح.', 401);

        $workshop = $this->workshopService->cancel((int) $id, $user, $request->validated()['reason']);

        return response()->json(['data' => $workshop]);
    }

    public function attendees(Request $request, $id)
    {
        $user = User::find(Auth::id());
        if (!$user) throw new Exception('غير مصرح.', 401);

        $attendees = $this->workshopService->getAttendees((int) $id, $user);

        return response()->json(['data' => $attendees]);
    }

    public function markAttendance(Request $request, $id, $user_id)
    {
        $user = User::find(Auth::id());
        if (!$user) throw new Exception('غير مصرح.', 401);

        $this->workshopService->markAttendance((int) $id, (int) $user_id, $user);

        return response()->json(['message' => 'تم تسجيل الحضور بنجاح.']);
    }
}