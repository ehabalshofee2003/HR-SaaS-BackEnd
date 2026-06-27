<?php

namespace App\Repositories\Hr;

use App\Models\Hr\Complaint;

class ComplaintRepository
{
    protected $model;

    public function __construct(Complaint $model)
    {
        $this->model = $model;
    }

    public function create(array $data): Complaint
    {
        return $this->model->create($data);
    }

    public function getByUserId(int $userId)
    {
        // جلب شكاوى الموظف (سواء المجهولة أو المعلنة التي قام هو بكتابتها)
        return $this->model->where('user_id', $userId)->latest()->get();
    }
}