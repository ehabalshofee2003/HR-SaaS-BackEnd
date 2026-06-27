<?php
namespace App\Models\SaaS;

use App\Models\BaseModel;
use App\Models\Organization\Company;

class CompanySubscription extends BaseModel
{
    protected $table = 'company_subscriptions';
    public $timestamps = true;
    protected $fillable = ['company_id', 'plan_id', 'start_date', 'end_date', 'auto_renew', 'status'];
    protected $casts = ['auto_renew' => 'boolean', 'start_date' => 'date', 'end_date' => 'date'];

    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    public function plan()
    {
        return $this->belongsTo(SubscriptionPlan::class, 'plan_id');
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class, 'subscription_id');
    }
}