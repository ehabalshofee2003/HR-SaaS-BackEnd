<?php
namespace App\Models\SaaS;

use App\Models\BaseModel;

class PlatformSetting extends BaseModel
{
    protected $table = 'platform_settings';
    public $timestamps = true; // لا يوجد soft deletes
    protected $fillable = ['key', 'value', 'type'];
}