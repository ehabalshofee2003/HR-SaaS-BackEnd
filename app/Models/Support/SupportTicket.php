<?php
namespace App\Models\Support;

use App\Models\BaseModel;
use App\Models\Identity\User;
use App\Models\Organization\Company;

class SupportTicket extends BaseModel
{
    protected $table = 'support_tickets';
    protected $fillable = [
        'company_id', 'created_by', 'title', 'description', 'category',
        'priority', 'status', 'assigned_to', 'resolution_notes', 'resolved_at', 'closed_at'
    ];
    protected $casts = ['resolved_at' => 'datetime', 'closed_at' => 'datetime'];

    public function company() { return $this->belongsTo(Company::class, 'company_id'); }
    public function creator() { return $this->belongsTo(User::class, 'created_by'); }
    public function assignee() { return $this->belongsTo(User::class, 'assigned_to'); }
}