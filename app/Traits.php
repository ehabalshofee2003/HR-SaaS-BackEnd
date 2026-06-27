<?php

namespace App\Traits;

use Illuminate\Http\JsonResponse;

trait ApiResponse
{
    /**
     * نجاح — بيانات
     */
    protected function success($data = null, string $message = 'تمت العملية بنجاح', int $code = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data'    => $data,
            'errors'  => null,
        ], $code);
    }

    /**
     * نجاح — بدون بيانات
     */
    protected function successMessage(string $message = 'تمت العملية بنجاح', int $code = 200): JsonResponse
    {
        return $this->success(null, $message, $code);
    }

    /**
     * خطأ — validation
     */
    protected function validationError($errors, string $message = 'البيانات المدخلة غير صحيحة'): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'data'    => null,
            'errors'  => $errors,
        ], 422);
    }

    /**
     * خطأ — عام
     */
    protected function error(string $message = 'حدث خطأ ما', int $code = 400, $errors = null): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'data'    => null,
            'errors'  => $errors,
        ], $code);
    }

    /**
     * غير مصرح
     */
    protected function unauthorized(string $message = 'غير مصرح لك بالوصول'): JsonResponse
    {
        return $this->error($message, 403);
    }

    /**
     * غير موجود
     */
    protected function notFound(string $message = 'الموارد المطلوبة غير موجودة'): JsonResponse
    {
        return $this->error($message, 404);
    }
}