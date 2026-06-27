<?php
namespace App\Models\Support;

use App\Models\BaseModel;
use App\Models\Identity\User;
use App\Models\Organization\Company;

class Notification extends BaseModel
{
    protected $table = 'notifications';
    protected $fillable = ['company_id', 'user_id', 'title', 'body', 'type', 'is_read', 'data'];
    protected $casts = ['is_read' => 'boolean', 'data' => 'json'];

    public function company() { return $this->belongsTo(Company::class, 'company_id'); }
    public function user() { return $this->belongsTo(User::class, 'user_id'); }
}