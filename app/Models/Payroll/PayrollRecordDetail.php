<?php
namespace App\Models\Payroll;

use App\Models\BaseModel;

class PayrollRecordDetail extends BaseModel
{
    protected $table = 'payroll_record_details';
    public $timestamps = true;
    protected $fillable = ['record_id', 'name', 'component_type', 'amount'];

    public function record() { return $this->belongsTo(PayrollRecord::class, 'record_id'); }
}