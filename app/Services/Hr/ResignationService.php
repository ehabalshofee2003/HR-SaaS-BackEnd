<?php

namespace App\Services\Hr;

use App\Models\Identity\User;
use App\Repositories\Hr\ResignationRepository;
use Illuminate\Support\Facades\Auth;

class ResignationService
{
    public function __construct(private ResignationRepository $resignationRepository) {}

    public function submit(array $data)
    {
        $user = $this->getAuthenticatedUser();

        $resignationData = [
            'employee_user_id' => $user->id,
            'supervisor_user_id' => $data['supervisor_user_id'],
            'reason' => $data['reason'],
            'notice_date' => $data['notice_date'],
            'last_working_date' => $data['last_working_date'],
            'status' => 'pending',
        ];

        $resignation = $this->resignationRepository->create($resignationData);
        $resignation->load('supervisor.profile');

        return [
            'success' => true,
            'message' => 'Resignation submitted successfully.',
            'code' => 201,
            'data' => $resignation
        ];
    }

    public function list()
    {
        $user = $this->getAuthenticatedUser();
        return $this->resignationRepository->getEmployeeResignations($user->id);
    }

    public function details($id)
    {
        $user = $this->getAuthenticatedUser();
        $resignation = $this->resignationRepository->findEmployeeResignationById((int) $id, $user->id);

        if (!$resignation) {
            return [
                'success' => false,
                'message' => 'Resignation not found.',
                'code' => 404,
                'data' => null
            ];
        }

        return [
            'success' => true,
            'message' => 'Resignation details retrieved successfully.',
            'code' => 200,
            'data' => $resignation
        ];
    }

    public function withdraw($id)
    {
        $user = $this->getAuthenticatedUser();
        $resignation = $this->resignationRepository->findEmployeeResignationById((int) $id, $user->id);

        if (!$resignation) {
            return [
                'success' => false,
                'message' => 'Resignation not found.',
                'code' => 404,
                'data' => null
            ];
        }

        if ($resignation->status !== 'pending') {
            return [
                'success' => false,
                'message' => 'Cannot withdraw a resignation that is already processed.',
                'code' => 400,
                'data' => null
            ];
        }

        $this->resignationRepository->updateStatus($resignation, 'withdrawn');
        $resignation->refresh();

        return [
            'success' => true,
            'message' => 'Resignation withdrawn successfully.',
            'code' => 200,
            'data' => $resignation
        ];
    }

    private function getAuthenticatedUser(): User
    {
        $user = \App\Models\Identity\User::find(Auth::id());
        if (!$user) {
            abort(401, 'Unauthorized');
        }
        return $user;
    }
}