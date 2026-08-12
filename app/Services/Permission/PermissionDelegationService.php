<?php

namespace App\Services\Permission;

use App\Models\Identity\User;
use Illuminate\Support\Facades\DB;
use Exception;

class PermissionDelegationService
{
    /**
     * يرجّع الصلاحيات يلي يقدر "المانح" يفوّضها لغيره —
     * وهي دايمًا subset من صلاحياته الحالية هو نفسه (قاعدة عدم التفويض بأكثر مما يملك).
     */
    public function getAssignableCatalog(User $grantor): array
    {
        return $grantor->getAllPermissions()->pluck('name')->values()->toArray();
    }

    /**
     * يمنح صلاحيات محددة لمستخدم مستهدف (target)،
     * بشرط إنها كلها ضمن نطاق صلاحيات المانح (grantor) الحالية.
     */
    public function assignPermissionsTo(User $grantor, User $target, array $permissionNames): User
    {
        $allowed = $this->getAssignableCatalog($grantor);
        $invalid = array_diff($permissionNames, $allowed);

        if (!empty($invalid)) {
            throw new Exception(
                'لا يمكنك منح صلاحيات لا تملكها: ' . implode(', ', $invalid),
                403
            );
        }

        DB::transaction(function () use ($target, $permissionNames) {
            $target->syncPermissions($permissionNames);
        });

        return $target->fresh();
    }

    /**
     * يرجّع الصلاحيات الحالية الممنوحة فعليًا لمستخدم مستهدف معيّن
     * (مفيد لعرضها بشاشة "تعديل صلاحيات المشرف" مثلاً).
     */
    public function getGrantedPermissions(User $target): array
    {
        return $target->getDirectPermissions()->pluck('name')->values()->toArray();
    }
}