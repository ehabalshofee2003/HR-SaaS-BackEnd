<?php

namespace App\Http\Controllers\Api\V1\Owner;

use App\Http\Controllers\Controller;
use App\Services\Permission\PermissionDelegationService;
use App\Repositories\Organization\ManagerRepository;
use App\Models\Identity\User;
use Illuminate\Http\Request;
use Exception;

class PermissionController extends Controller
{
    public function __construct(
        protected PermissionDelegationService $delegationService,
        protected ManagerRepository $managerRepository
    ) {}

    /**
     * الصلاحيات يلي يقدر الـ Owner الحالي يفوّضها لمدراء فروعه
     * (= صلاحياته هو نفسه حاليًا).
     */
    public function assignableCatalog(Request $request)
    {
        $catalog = $this->delegationService->getAssignableCatalog($request->user());

        return response()->json(['success' => true, 'data' => $catalog]);
    }

    /**
     * الصلاحيات الحالية الممنوحة فعليًا لمدير فرع معيّن.
     */
    public function managerPermissions(Request $request, int $managerId)
    {
        $manager = $this->managerRepository->findForOwner($managerId, $request->user()->id);

        if (!$manager) {
            return response()->json(['success' => false, 'message' => 'مدير الفرع غير موجود.'], 404);
        }

        $target = User::find($managerId);
        $granted = $this->delegationService->getGrantedPermissions($target);

        return response()->json(['success' => true, 'data' => $granted]);
    }

    /**
     * تحديث صلاحيات مدير فرع معيّن (استبدال كامل).
     */
    public function updateManagerPermissions(Request $request, int $managerId)
    {
        $validated = $request->validate([
            'permissions' => ['required', 'array'],
            'permissions.*' => ['string'],
        ]);

        $manager = $this->managerRepository->findForOwner($managerId, $request->user()->id);

        if (!$manager) {
            return response()->json(['success' => false, 'message' => 'مدير الفرع غير موجود.'], 404);
        }

        try {
            $target = User::find($managerId);
            $updated = $this->delegationService->assignPermissionsTo(
                $request->user(),
                $target,
                $validated['permissions']
            );

            return response()->json([
                'success' => true,
                'message' => 'تم تحديث صلاحيات مدير الفرع بنجاح.',
                'data' => $this->delegationService->getGrantedPermissions($updated),
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], $e->getCode() ?: 403);
        }
    }
}