<?php
namespace App\Models\SaaS;

use App\Models\BaseModel;

class InvoiceItem extends BaseModel
{
    protected $table = 'invoice_items';
    protected $fillable = ['invoice_id', 'description', 'quantity', 'unit_price'];

    public function invoice()
    {
        return $this->belongsTo(Invoice::class, 'invoice_id');
    }
}