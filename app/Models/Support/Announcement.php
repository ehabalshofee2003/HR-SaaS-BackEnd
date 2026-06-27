<?php

namespace App\Models\Support;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Identity\User;
use App\Models\Organization\Company;

class Announcement extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'company_id', 'created_by', 'title', 'content', 
        'target_type', 'target_id', 'start_date', 'end_date', 'is_active'
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'is_active' => 'boolean',
    ];

    public function reads()
    {
        return $this->hasMany(AnnouncementRead::class, 'announcement_id');
    }
    public function company() { return $this->belongsTo(Company::class, 'company_id'); }
    public function creator() { return $this->belongsTo(User::class, 'created_by'); }
}