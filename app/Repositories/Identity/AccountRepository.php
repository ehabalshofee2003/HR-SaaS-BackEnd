<?php

namespace App\Repositories\Identity;

use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AccountRepository
{
    public function getSettings(int $companyId): array
    {
        return DB::table('company_settings')
            ->where('company_id', $companyId)
            ->pluck('value', 'key')
            ->toArray();
    }

    public function upsertSetting(int $companyId, string $key, string $value): void
    {
        DB::table('company_settings')->updateOrInsert(
            ['company_id' => $companyId, 'key' => $key],
            ['value' => $value, 'updated_at' => Carbon::now(), 'created_at' => Carbon::now()]
        );
    }

    public function findBranch(int $branchId): ?object
    {
        return DB::table('branches')->where('id', $branchId)->whereNull('deleted_at')->first();
    }

    public function updateBranch(int $branchId, array $data): void
    {
        $data['updated_at'] = Carbon::now();
        DB::table('branches')->where('id', $branchId)->update($data);
    }
}