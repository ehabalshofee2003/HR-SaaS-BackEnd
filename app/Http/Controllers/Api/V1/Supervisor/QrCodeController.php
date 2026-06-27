<?php

namespace App\Http\Controllers\Api\V1\Supervisor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Supervisor\GenerateQrCodeRequest;
use App\Http\Resources\Supervisor\QrCodeResource;
use App\Services\Hr\QrCodeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class QrCodeController extends Controller
{
    public function __construct(
        protected QrCodeService $qrCodeService
    ) {}

    public function generate(GenerateQrCodeRequest $request): JsonResponse
    {
        $user = \App\Models\Identity\User::find(Auth::id());

        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        $qr = $this->qrCodeService->generate($user->id, $request->type);

        if (!$qr) {
            return response()->json(['message' => 'Could not determine supervisor branch'], 422);
        }

        return response()->json([
            'data' => new QrCodeResource($qr),
        ]);
    }
}