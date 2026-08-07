<?php

namespace App\Http\Controllers\Api\V1\BranchManager;

use App\Http\Controllers\Controller;
use App\Http\Requests\BranchManager\Employee\StoreEmployeeRequest;
use App\Http\Requests\BranchManager\Employee\UpdateEmployeeRequest;
use App\Http\Requests\BranchManager\Employee\UploadEmployeeDocumentRequest;
use App\Services\Hr\EmployeeService;
use App\Models\Identity\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Exception;

class EmployeeController extends Controller
{
    public function __construct(
        protected EmployeeService $employeeService
    ) {}

    public function index(Request $request)
    {
        $user = User::find(Auth::id());
        if (!$user) {
            throw new Exception('غير مصرح.', 401);
        }

        $employees = $this->employeeService->list($user, $request->only(['status', 'department_id', 'supervisor_id', 'search']));

        return response()->json(['data' => $employees]);
    }

    public function store(StoreEmployeeRequest $request)
    {
        $user = User::find(Auth::id());
        if (!$user) {
            throw new Exception('غير مصرح.', 401);
        }

        $data = $request->validated();
        $avatar = $request->file('avatar');
        $documents = $request->input('documents', []);

        // ربط الملفات الفعلية بحقل documents
        foreach ($documents as $i => $doc) {
            $documents[$i]['file'] = $request->file("documents.$i.file");
        }

        $employee = $this->employeeService->create($user, $data, $avatar, $documents);

        return response()->json(['data' => $this->formatEmployee($employee)], 201);
    }

    public function show(Request $request, $id)
    {
        $user = User::find(Auth::id());
        if (!$user) {
            throw new Exception('غير مصرح.', 401);
        }

        $employee = $this->employeeService->getDetails((int) $id, $user);

        return response()->json(['data' => $this->formatEmployee($employee)]);
    }

    public function update(UpdateEmployeeRequest $request, $id)
    {
        $user = User::find(Auth::id());
        if (!$user) {
            throw new Exception('غير مصرح.', 401);
        }

        $employee = $this->employeeService->update(
            (int) $id,
            $request->validated(),
            $user,
            $request->file('avatar')
        );

        return response()->json(['data' => $this->formatEmployee($employee)]);
    }

    public function destroy(Request $request, $id)
    {
        $user = User::find(Auth::id());
        if (!$user) {
            throw new Exception('غير مصرح.', 401);
        }

        $this->employeeService->delete((int) $id, $user);

        return response()->json(['message' => 'تم حذف الموظف بنجاح.']);
    }

    public function toggleStatus(Request $request, $id)
    {
        $user = User::find(Auth::id());
        if (!$user) {
            throw new Exception('غير مصرح.', 401);
        }

        $employee = $this->employeeService->toggleStatus((int) $id, $user);

        return response()->json(['data' => $this->formatEmployee($employee)]);
    }

    public function resetPassword(Request $request, $id)
    {
        $user = User::find(Auth::id());
        if (!$user) {
            throw new Exception('غير مصرح.', 401);
        }

        $newPassword = $this->employeeService->resetPassword((int) $id, $user);

        return response()->json(['message' => 'تم إعادة تعيين كلمة المرور.', 'temporary_password' => $newPassword]);
    }

    public function documents(Request $request, $id)
    {
        $user = User::find(Auth::id());
        if (!$user) {
            throw new Exception('غير مصرح.', 401);
        }

        $documents = $this->employeeService->listDocuments((int) $id, $user);

        $documents = array_map(function ($doc) {
            $doc->file_url = Storage::url($doc->file_path);
            return $doc;
        }, $documents);

        return response()->json(['data' => $documents]);
    }

    public function uploadDocument(UploadEmployeeDocumentRequest $request, $id)
    {
        $user = User::find(Auth::id());
        if (!$user) {
            throw new Exception('غير مصرح.', 401);
        }

        $result = $this->employeeService->uploadDocument(
            (int) $id,
            $request->file('file'),
            $request->validated()['type'],
            $user
        );

        return response()->json(['data' => [
            'type' => $result->type,
            'file_url' => Storage::url($result->file_path),
        ]], 201);
    }

    public function deleteDocument(Request $request, $employee_id, $document_id)
    {
        $user = User::find(Auth::id());
        if (!$user) {
            throw new Exception('غير مصرح.', 401);
        }

        $this->employeeService->deleteDocument((int) $employee_id, (int) $document_id, $user);

        return response()->json(['message' => 'تم حذف المستند بنجاح.']);
    }

    private function formatEmployee(object $employee): array
    {
        $data = (array) $employee;
        if (!empty($data['avatar'])) {
            $data['avatar_url'] = Storage::url($data['avatar']);
        }
        return $data;
    }
}