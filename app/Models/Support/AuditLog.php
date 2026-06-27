<?php
namespace App\Models\Support;

use App\Models\BaseModel;
use App\Models\Identity\User;
use App\Models\Organization\Company;

class AuditLog extends BaseModel
{
    protected $table = 'audit_logs';
    public $timestamps = false; // فقط created_at حسب المايجريشن
    const CREATED_AT = 'created_at';
    
    protected $fillable = [
        'user_id', 'company_id', 'action', 'entity_type', 'entity_id',
        'old_values', 'new_values', 'ip_address', 'user_agent'
    ];
    protected $casts = ['old_values' => 'json', 'new_values' => 'json'];

    public function user() { return $this->belongsTo(User::class, 'user_id'); }
    public function company() { return $this->belongsTo(Company::class, 'company_id'); }
}