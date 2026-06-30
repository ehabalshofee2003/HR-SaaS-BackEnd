<?php

namespace App\Models\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Notification extends Model
{
    use SoftDeletes;

    // تحديد اسم الجدول صراحةً (رغم أنه سيكون الافتراضي)
    protected $table = 'notifications'; 

    protected $fillable = [
        'company_id', 'user_id', 'title', 'body', 'type', 'is_read', 'data'
    ];

    protected $casts = [
        'is_read' => 'boolean',
        'data'    => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(\App\Models\Identity\User::class);
    }

    public function company()
    {
        return $this->belongsTo(\App\Models\Organization\Company::class);
    }
}