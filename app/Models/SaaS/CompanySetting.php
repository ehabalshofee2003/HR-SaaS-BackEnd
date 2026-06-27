<?php
namespace App\Models\SaaS;

use App\Models\BaseModel;
use App\Models\Organization\Company;

class CompanySetting extends BaseModel
{
    protected $table = 'company_settings';
    public $timestamps = true;
    protected $fillable = ['company_id', 'key', 'value', 'type'];

    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id');
    }
}