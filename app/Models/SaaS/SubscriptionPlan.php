<?php
namespace App\Models\SaaS;

use App\Models\BaseModel;

class SubscriptionPlan extends BaseModel
{
    protected $table = 'subscription_plans';
    protected $fillable = ['name', 'price', 'billing_cycle', 'max_branches', 'max_employees', 'features', 'is_active'];
    protected $casts = ['features' => 'json', 'is_active' => 'boolean'];

    public function subscriptions()
    {
        return $this->hasMany(CompanySubscription::class, 'plan_id');
    }
}