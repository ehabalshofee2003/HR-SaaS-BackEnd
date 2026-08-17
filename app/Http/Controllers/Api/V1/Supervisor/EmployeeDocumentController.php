<?php

namespace App\Http\Controllers\Api\V1\Supervisor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Supervisor\Employee\UploadDocumentRequest;
use App\Models\Identity\User;
use App\Repositories\Supervisor\EmployeeRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Exception;
use Illuminate\Support\Facades\Log;

class EmployeeDocumentController extends Controller
{
    public function __construct(protected EmployeeRepository $employeeRepository) {}

    private function ensureEmployee(int $employeeId, int $supervisorId): void
    {
        if (!$this->employeeRepository->find($employeeId, $supervisorId)) {
            throw new Exception('Employee not found.', 404);
        }
    }

    public function index(int $employeeId): JsonResponse
    {
        $supervisorId = Auth::id();

        try {
            $this->ensureEmployee($employeeId, $supervisorId);

            $docs = DB::table('user_documents')
                ->where('documentable_type', 'App\\Models\\Identity\\User')
                ->where('documentable_id', $employeeId)
                ->orderByDesc('created_at')
                ->get();

            $docs = $docs->map(function ($d) {
                $d->file_url = Storage::url($d->file_path);
                return $d;
            });

            return response()->json(['success' => true, 'data' => $docs]);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], $e->getCode() ?: 404);
        }
    }

    public function store(UploadDocumentRequest $request, int $employeeId): JsonResponse
    {
        $supervisorId = Auth::id();

        try {
            $this->ensureEmployee($employeeId, $supervisorId);

            $file = $request->file('file');
            $path = $file->store('employees/documents', 'public');
            $companyId = User::find($supervisorId)->getCurrentCompanyId();

            DB::table('user_documents')->insert([
                'company_id' => $companyId,
                'documentable_type' => 'App\\Models\\Identity\\User',
                'documentable_id' => $employeeId,
                'type' => $request->validated()['type'],
                'file_name' => $file->getClientOriginalName(),
                'file_path' => $path,
                'mime_type' => $file->getClientMimeType(),
                'uploaded_by' => $supervisorId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return response()->json(['success' => true, 'data' => ['file_url' => Storage::url($path)]], 201);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], $e->getCode() ?: 404);
        }
    }

    public function download(int $employeeId, int $documentId)
    {
        $supervisorId = Auth::id();
        $this->ensureEmployee($employeeId, $supervisorId);

        $doc = DB::table('user_documents')->where('id', $documentId)->where('documentable_id', $employeeId)->first();

        if (!$doc) {
            return response()->json(['success' => false, 'message' => 'Document not found.'], 404);
        }

        return Storage::disk('public')->download($doc->file_path, $doc->file_name);
    }

    public function destroy(int $employeeId, int $documentId): JsonResponse
    {
        $supervisorId = Auth::id();

        try {
            $this->ensureEmployee($employeeId, $supervisorId);

            $doc = DB::table('user_documents')->where('id', $documentId)->where('documentable_id', $employeeId)->first();

            if (!$doc) {
                return response()->json(['success' => false, 'message' => 'Document not found.'], 404);
            }

            DB::table('user_documents')->where('id', $documentId)->delete();
            Storage::disk('public')->delete($doc->file_path);

            return response()->json(['success' => true, 'message' => 'Document deleted.']);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], $e->getCode() ?: 404);
        }
    }
}