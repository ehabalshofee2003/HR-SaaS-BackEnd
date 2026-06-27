<?php

namespace App\Http\Controllers\Api\V1\Employee;

use App\Http\Controllers\Controller;
use App\Http\Resources\Employee\AnnouncementDetailResource;
use App\Http\Resources\Employee\AnnouncementListResource;
use App\Services\Support\AnnouncementService;
use Illuminate\Http\JsonResponse;

class AnnouncementController extends Controller
{
    public function __construct(private AnnouncementService $announcementService) {}

    public function index(): JsonResponse
    {
        $announcements = $this->announcementService->list();
        return AnnouncementListResource::collection($announcements)->response();
    }

    public function show($id): JsonResponse
    {
        $result = $this->announcementService->details($id);

        if (!$result['success']) {
            return response()->json(['message' => $result['message']], $result['code']);
        }

        return response()->json([
            'data' => new AnnouncementDetailResource($result['data'])
        ]);
    }

    public function markRead($id): JsonResponse
    {
        $result = $this->announcementService->markAsRead($id);

        if (!$result['success']) {
            return response()->json(['message' => $result['message']], $result['code']);
        }

        return response()->json([
            'message' => $result['message'],
            'data' => new AnnouncementDetailResource($result['data'])
        ]);
    }
}