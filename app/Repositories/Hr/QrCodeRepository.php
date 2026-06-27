<?php

namespace App\Repositories\Hr;

use App\Models\Hr\QrCode;

class QrCodeRepository
{
    public function create(array $data): QrCode
    {
        return QrCode::create($data);
    }

    public function findByCode(string $code): ?QrCode
    {
        return QrCode::where('code', $code)->first();
    }
}