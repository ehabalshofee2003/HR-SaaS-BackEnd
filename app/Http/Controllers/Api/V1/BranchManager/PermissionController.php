<?php

namespace App\Http\Controllers\Api\V1\BranchManager;

use App\Http\Controllers\Controller;
use App\Services\Permission\PermissionDelegationService;
use App\Repositories\Organization\SupervisorRepository;
use Illuminate\Http\Request;
use Exception;

class PermissionController extends Controller
{
    public function __construct(
        protected PermissionDelegationService $delegationService,
        protected SupervisorRepository $supervisorRepository
    ) {}

    /**
     * الصلاحيات يلي يقدر مدير الفرع الحالي يفوّضها لمشرفيه
     * (= صلاحياته الحالية هو نفسه، ضمن قاعدة عدم تفويض أكثر مما يملك).
     */
    public function assignableCatalog(Request $request)
    {
        $catalog = $this->delegationService->getAssignableCatalog($request->user());

        return response()->json([
            'success' => true,
            'data' => $catalog,
        ]);
    }

    /**
     * الصلاحيات الحالية الممنوحة فعليًا لمشرف معيّن.
     */
    public function supervisorPermissions(Request $request, int $supervisorId)
    {
        $branchId = $request->user()->getCurrentBranchId();
        $supervisor = $this->supervisorRepository->findForBranch($supervisorId, $branchId);

        if (!$supervisor) {
            return response()->json(['success' => false, 'message' => 'المشرف غير موجود.'], 404);
        }

        $target = \App\Models\Identity\User::find($supervisorId);
        $granted = $this->delegationService->getGrantedPermissions($target);

        return response()->json(['success' => true, 'data' => $granted]);
    }

    /**
     * تحديث صلاحيات مشرف معيّن (استبدال كامل).
     */
    public function updateSupervisorPermissions(Request $request, int $supervisorId)
    {
        $validated = $request->validate([
            'permissions' => ['required', 'array'],
            'permissions.*' => ['string'],
        ]);

        $branchId = $request->user()->getCurrentBranchId();
        $supervisor = $this->supervisorRepository->findForBranch($supervisorId, $branchId);

        if (!$supervisor) {
            return response()->json(['success' => false, 'message' => 'المشرف غير موجود.'], 404);
        }

        try {
            $target = \App\Models\Identity\User::find($supervisorId);
            $updated = $this->delegationService->assignPermissionsTo(
                $request->user(),
                $target,
                $validated['permissions']
            );

            return response()->json([
                'success' => true,
                'message' => 'تم تحديث صلاحيات المشرف بنجاح.',
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