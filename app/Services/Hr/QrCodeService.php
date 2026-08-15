<?php

namespace App\Services\Hr;

use App\Repositories\Hr\QrCodeRepository;
use App\Models\Identity\User;
use Illuminate\Support\Str;
use App\Models\Hr\QrCode;

class QrCodeService
{
    public function __construct(
        protected QrCodeRepository $qrCodeRepository
    ) {}

    public function generate(int $supervisorUserId, string $type): ?QrCode
    {
        $branchId = User::find($supervisorUserId)?->branch_id;

        if (!$branchId) {
            return null;
        }

        $expirySeconds = (int) env('QR_CODE_EXPIRY_SECONDS', 2);

        return $this->qrCodeRepository->create([
            'branch_id'    => $branchId,
            'code'         => Str::uuid()->toString(),
            'type'         => $type,
            'usage_limit'  => 0,
            'expires_at'   => now()->addSeconds($expirySeconds),
        ]);
    }
}