<?php

namespace App\Http\Controllers\Api\V1\Employee;

use App\Http\Controllers\Controller;
use App\Http\Resources\Employee\WorkshopListResource;
use App\Http\Resources\Employee\WorkshopDetailsResource;
use App\Http\Resources\Employee\MyWorkshopResource;
use App\Services\Hr\WorkshopService;
use App\Models\Identity\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class WorkshopController extends Controller
{
    public function __construct(private WorkshopService $service) {}

    public function index(): JsonResponse
    {
        $user = User::find(Auth::id());
        if (!$user) return response()->json(['message' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);

        $workshops = $this->service->getAll($user);
        
        // نحضر الـ IDs ونعطيها للـ Resource
        $registeredIds = $this->service->getRegisteredWorkshopIds($user);
        $collection = WorkshopListResource::collection($workshops);
        $collection->additional(['registered_ids' => $registeredIds]);

        return response()->json([
            'data' => $collection,
            'meta' => [
                'current_page' => $workshops->currentPage(),
                'last_page'    => $workshops->lastPage(),
                'per_page'     => $workshops->perPage(),
            ]
        ]);
    }

    public function show($id): JsonResponse
    {
        $user = User::find(Auth::id());
        if (!$user) return response()->json(['message' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);

        $workshop = $this->service->getById($user, $id);
        if (!$workshop) return response()->json(['message' => 'Workshop not found.'], Response::HTTP_NOT_FOUND);

        return response()->json([
            'data' => new WorkshopDetailsResource($workshop->load('creator', 'attendees'))
        ]);
    }

    public function register($id): JsonResponse
    {
        $user = User::find(Auth::id());
        if (!$user) return response()->json(['message' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);

        $result = $this->service->register($user, $id);
        
        if (isset($result['error'])) {
            return response()->json(['message' => $result['error']], $result['code']);
        }

        return response()->json(['message' => 'Registered successfully.'], Response::HTTP_CREATED);
    }

    public function unregister($id): JsonResponse
    {
        $user = User::find(Auth::id());
        if (!$user) return response()->json(['message' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);

        $result = $this->service->unregister($user, $id);
        
        if ($result !== true) {
            return response()->json(['message' => $result['error']], $result['code']);
        }

        return response()->json(['message' => 'Unregistered successfully.']);
    }

    public function myWorkshops(): JsonResponse
    {
        $user = User::find(Auth::id());
        if (!$user) return response()->json(['message' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);

        $workshops = $this->service->getMyWorkshops($user);
        return response()->json([
            'data' => MyWorkshopResource::collection($workshops),
            'meta' => [
                'current_page' => $workshops->currentPage(),
                'last_page'    => $workshops->lastPage(),
                'per_page'     => $workshops->perPage(),
            ]
        ]);
    }

    public function myWorkshopShow($id): JsonResponse
    {
        $user = User::find(Auth::id());
        if (!$user) return response()->json(['message' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);

        $attendee = $this->service->getMyWorkshopById($user, $id);
        if (!$attendee) return response()->json(['message' => 'Workshop not found in your registrations.'], Response::HTTP_NOT_FOUND);

        return response()->json([
            'data' => new MyWorkshopResource($attendee->load('workshop'))
        ]);
    }
}