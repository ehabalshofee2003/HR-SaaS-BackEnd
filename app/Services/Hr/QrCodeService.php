<?php

namespace App\Services\Hr;

use App\Repositories\Hr\QrCodeRepository;
use Illuminate\Support\Str;
use App\Models\Hr\QrCode;

class QrCodeService
{
    public function __construct(
        protected QrCodeRepository $qrCodeRepository
    ) {}

    public function generate(int $supervisorUserId, string $type): ?QrCode
    {
        $branchId = \App\Models\Identity\User::find($supervisorUserId)
            ?->employeeDetail?->department?->branch_id;

        if (!$branchId) {
            return null;
        }

        return $this->qrCodeRepository->create([
            'branch_id'    => $branchId,
            'code'         => Str::uuid()->toString(),
            'type'         => $type,
            'usage_limit'  => 0,
            'expires_at'   => now()->addMinutes(15),
        ]);
    }
}