<?php

namespace App\Http\Controllers\Api\V1\BranchManager;

use App\Http\Controllers\Controller;
use App\Http\Requests\BranchManager\Announcement\StoreAnnouncementRequest;
use App\Services\Support\AnnouncementService;
use App\Models\Identity\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Exception;

class AnnouncementController extends Controller
{
    public function __construct(
        protected AnnouncementService $announcementService
    ) {}

    public function index(Request $request)
    {
        $user = User::find(Auth::id());
        if (!$user) throw new Exception('غير مصرح.', 401);

        $announcements = $this->announcementService->list($user, $request->only(['target']));

        return response()->json(['data' => $announcements]);
    }

    public function store(StoreAnnouncementRequest $request)
    {
        $user = User::find(Auth::id());
        if (!$user) throw new Exception('غير مصرح.', 401);

        $announcement = $this->announcementService->create(
            $user,
            $request->validated(),
            $request->file('attachments', [])
        );

        return response()->json(['data' => $announcement], 201);
    }

    public function readers(Request $request, $id)
    {
        $user = User::find(Auth::id());
        if (!$user) throw new Exception('غير مصرح.', 401);

        $readers = $this->announcementService->getReaders((int) $id, $user);

        return response()->json(['data' => $readers]);
    }

    public function archive(Request $request, $id)
    {
        $user = User::find(Auth::id());
        if (!$user) throw new Exception('غير مصرح.', 401);

        $announcement = $this->announcementService->archive((int) $id, $user);

        return response()->json(['data' => $announcement]);
    }

    public function destroy(Request $request, $id)
    {
        $user = User::find(Auth::id());
        if (!$user) throw new Exception('غير مصرح.', 401);

        $this->announcementService->delete((int) $id, $user);

        return response()->json(['message' => 'تم حذف الإعلان بنجاح.']);
    }
}