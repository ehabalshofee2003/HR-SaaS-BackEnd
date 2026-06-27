<?php
namespace App\Models\SaaS;

use App\Models\BaseModel;
use App\Models\Organization\Company;

class PaymentTransaction extends BaseModel
{
    protected $table = 'payment_transactions';
    protected $fillable = ['invoice_id', 'company_id', 'amount', 'gateway', 'status', 'reference_number', 'paid_at'];
    protected $casts = ['paid_at' => 'datetime'];

    public function invoice()
    {
        return $this->belongsTo(Invoice::class, 'invoice_id');
    }

    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id');
    }
}