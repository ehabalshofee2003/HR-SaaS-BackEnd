<?php

namespace App\Models\Hr;

use Illuminate\Database\Eloquent\Model;

class ExceptionType extends Model
{
    // تأكد من اسم الجدول إذا كان يختلف (مثلاً hr_exception_types)
    protected $table = 'exception_types'; 
    
    protected $fillable = [
        'name', // افترضت أن حقل اسم النوع اسمه name، عدله إذا كان title أو type
    ];
}