<?php
namespace App\Models\SaaS;

use App\Models\BaseModel;
use App\Models\Organization\Company;

class Invoice extends BaseModel
{
    protected $table = 'invoices';
    protected $fillable = ['company_id', 'subscription_id', 'invoice_number', 'total', 'status', 'due_date', 'paid_at'];
    protected $casts = ['due_date' => 'date', 'paid_at' => 'datetime'];

    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    public function subscription()
    {
        return $this->belongsTo(CompanySubscription::class, 'subscription_id');
    }

    public function items()
    {
        return $this->hasMany(InvoiceItem::class, 'invoice_id');
    }

    public function transactions()
    {
        return $this->hasMany(PaymentTransaction::class, 'invoice_id');
    }
}