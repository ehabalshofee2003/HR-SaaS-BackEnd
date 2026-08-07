<?php

namespace App\Services\Hr;

use App\Models\Identity\User;
use App\Repositories\Hr\SupervisorEmployeeRepository;
use App\Http\Resources\Supervisor\EmployeeDetailsResource;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Arr;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;

class SupervisorEmployeeService
{
    public function __construct(private SupervisorEmployeeRepository $repo) {}

    public function getTeamEmployees(User $supervisor)
    {
        $employees = $this->repo->getEmployeesList($supervisor->id, request()->query());
        return \App\Http\Resources\Supervisor\EmployeeListResource::collection($employees);
    }

    public function createEmployee(User $supervisor, array $data, ?UploadedFile $avatar = null, ?array $documents = null)
    {
        // 1. جلب بيانات المشرف مباشرة من الداتا بيز لتجنب أخطاء الموديلات
        $supervisorData = $this->repo->getSupervisorRawData($supervisor->id);
        if (!$supervisorData || !$supervisorData->department_id) {
            abort(403, 'Supervisor account is not linked to a department.');
        }

        if (!$this->repo->canAddEmployee($supervisorData->company_id)) {
            abort(403, 'Cannot add more employees. Company limit reached.');
        }

        $uploadedFiles = ['avatar' => null, 'documents' => []];

        try {
            if ($avatar) {
                $uploadedFiles['avatar'] = $avatar->store('avatars', 'public');
            }
            if ($documents) {
                foreach ($documents as $doc) {
                    $uploadedFiles['documents'][] = [
                        'path' => $doc->store('documents/' . $supervisorData->company_id, 'public'),
                        'name' => $doc->getClientOriginalName(),
                        'mime' => $doc->getMimeType()
                    ];
                }
            }
        } catch (\Exception $e) {
            $this->cleanupFiles($uploadedFiles);
            throw $e;
        }

        try {
            DB::beginTransaction();

            // 2. إنشاء المستخدم (إضافة email هنا لأنه في جدول users)
            $newUserId = DB::table('users')->insertGetId([
                'phone' => $data['phone'],
                'email' => $data['email'] ?? null, // تم تصحيح المكان
                'password_hash' => Hash::make($data['password']),
                'user_type' => 'employee',
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now()
            ]);

            // 3. إنشاء الملف الشخصي (بدون email)
            DB::table('user_profiles')->insert([
                'user_id' => $newUserId,
                'full_name' => $data['full_name'],
                'avatar' => $uploadedFiles['avatar'],
                'national_id' => $data['national_id'] ?? null,
                'date_of_birth' => $data['date_of_birth'] ?? null,
                'created_at' => now(),
                'updated_at' => now()
            ]);

            // 4. إنشاء تفاصيل الموظف
            DB::table('employee_details')->insert([
                'user_id' => $newUserId,
                'department_id' => $supervisorData->department_id,
                'supervisor_id' => $supervisor->id,
                'job_title' => $data['job_title'],
                'contract_type' => $data['contract_type'],
                'basic_salary' => $data['basic_salary'],
                'hire_date' => $data['hire_date'],
                'employment_status' => 'active',
                'created_at' => now(),
                'updated_at' => now()
            ]);

            // 5. حفظ المستندات
            foreach ($uploadedFiles['documents'] as $docData) {
                DB::table('user_documents')->insert([
                    'company_id' => $supervisorData->company_id,
                    'documentable_type' => 'App\Models\Identity\User',
                    'documentable_id' => $newUserId,
                    'type' => 'other',
                    'file_name' => $docData['name'],
                    'file_path' => $docData['path'],
                    'mime_type' => $docData['mime'],
                    'uploaded_by' => $supervisor->id,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            }

            DB::commit();

            // 6. إرجاع البيانات المنسقة فوراً
            $createdEmployeeData = $this->repo->getEmployeeWithDetails($supervisor->id, $newUserId);
            return new EmployeeDetailsResource($createdEmployeeData);

        } catch (\Exception $e) {
            DB::rollBack();
            $this->cleanupFiles($uploadedFiles);
            throw $e;
        }
    }

    public function getEmployeeDetails(User $supervisor, int $userId)
    {
        $employee = $this->repo->getEmployeeWithDetails($supervisor->id, $userId);
        if (!$employee) abort(404, 'Employee not found or not in your team.');
        
        return new EmployeeDetailsResource($employee);
    }

    public function updateEmployee(User $supervisor, int $userId, array $data, ?UploadedFile $avatar = null, ?array $documents = null)
    {
        $employee = $this->repo->getEmployeeWithDetails($supervisor->id, $userId);
        if (!$employee) abort(404, 'Employee not found or not in your team.');

        $uploadedFiles = ['avatar' => null, 'documents' => []];

        try {
            if ($avatar) {
                $uploadedFiles['avatar'] = $avatar->store('avatars', 'public');
            }
            if ($documents) {
                foreach ($documents as $doc) {
                    $uploadedFiles['documents'][] = [
                        'path' => $doc->store('documents/' . $employee->company_id, 'public'), // افترضنا وجود company_id في الـ select
                        'name' => $doc->getClientOriginalName(),
                        'mime' => $doc->getMimeType()
                    ];
                }
            }
        } catch (\Exception $e) {
            $this->cleanupFiles($uploadedFiles);
            throw $e;
        }

        try {
            DB::beginTransaction();

            // تحديث جدول users
            $userUpdateData = Arr::only($data, ['phone', 'email']);
            if (!empty($userUpdateData)) {
                DB::table('users')->where('id', $userId)->update($userUpdateData);
            }

            // تحديث جدول user_profiles
            $profileUpdateData = Arr::only($data, ['full_name', 'national_id', 'date_of_birth']);
            if ($uploadedFiles['avatar']) {
                if ($employee->avatar) Storage::disk('public')->delete($employee->avatar);
                $profileUpdateData['avatar'] = $uploadedFiles['avatar'];
            }
            if (!empty($profileUpdateData)) {
                DB::table('user_profiles')->where('user_id', $userId)->update($profileUpdateData);
            }

            // تحديث جدول employee_details
            $empUpdateData = Arr::only($data, ['job_title', 'basic_salary']);
            if (!empty($empUpdateData)) {
                DB::table('employee_details')->where('user_id', $userId)->update($empUpdateData);
            }

            // إضافة المستندات الجديدة
            foreach ($uploadedFiles['documents'] as $docData) {
                DB::table('user_documents')->insert([
                    'company_id' => $employee->company_id ?? 1,
                    'documentable_type' => 'App\Models\Identity\User',
                    'documentable_id' => $userId,
                    'type' => 'other',
                    'file_name' => $docData['name'],
                    'file_path' => $docData['path'],
                    'mime_type' => $docData['mime'],
                    'uploaded_by' => $supervisor->id,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            }

            DB::commit();
            
            $updatedEmployeeData = $this->repo->getEmployeeWithDetails($supervisor->id, $userId);
            return new EmployeeDetailsResource($updatedEmployeeData);

        } catch (\Exception $e) {
            DB::rollBack();
            $this->cleanupFiles($uploadedFiles);
            throw $e;
        }
    }

    private function cleanupFiles(array $files): void
    {
        if ($files['avatar']) Storage::disk('public')->delete($files['avatar']);
        foreach ($files['documents'] as $doc) {
            Storage::disk('public')->delete($doc['path']);
        }
    }
    public function getEmployeeDocuments(User $supervisor, int $userId)
    {
        // 1. التأكد أن الموظف تابع لهذا المشرف
        if (!$this->repo->isEmployeeInTeam($supervisor->id, $userId)) {
            abort(404, 'Employee not found or not in your team.');
        }

        // 2. جلب المستندات (باستخدام الـ Polymorphic Relation)
        $documents = DB::table('user_documents')
            ->where('documentable_type', 'App\Models\Identity\User')
            ->where('documentable_id', $userId)
            ->select('id', 'type', 'file_name', 'file_path', 'mime_type', 'created_at')
            ->get()
            ->map(function ($doc) {
                // تطبيق القاعدة: روابط الصورة/الملف تكون المسار الكامل
                $doc->full_url = Storage::url($doc->file_path);
                $doc->created_at = Carbon::parse($doc->created_at)->format('Y-m-d H:i:s');
                return $doc;
            });

        return $documents;
    }

    public function deleteEmployeeDocument(User $supervisor, int $userId, int $documentId)
    {
        // 1. التأكد أن الموظف تابع لهذا المشرف
        if (!$this->repo->isEmployeeInTeam($supervisor->id, $userId)) {
            abort(404, 'Employee not found or not in your team.');
        }

        // 2. البحث عن المستند
        $document = DB::table('user_documents')
            ->where('id', $documentId)
            ->where('documentable_type', 'App\Models\Identity\User')
            ->where('documentable_id', $userId)
            ->first();

        if (!$document) {
            abort(404, 'Document not found.');
        }

        // 3. حذف الملف الفعلي من السيرفر
        if ($document->file_path) {
            Storage::disk('public')->delete($document->file_path);
        }

        // 4. حذف السجل من الداتا بيز
        DB::table('user_documents')->where('id', $documentId)->delete();
    }
}