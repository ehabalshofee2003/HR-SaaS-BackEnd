<?php

namespace App\Models\Identity;

use App\Models\BaseModel;

class SuperAdmin extends BaseModel
{
    protected $table = 'super_admins';

    protected $fillable = ['user_id', 'is_active', 'last_login_at'];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}