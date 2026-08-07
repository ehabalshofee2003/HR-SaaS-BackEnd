<?php

namespace App\Models\Identity;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class UserProfile extends Model
{
    use HasFactory;

    // تحديد اسم الجدول صراحةً (Best Practice في أنظمة SaaS المعقدة)
    protected $table = 'user_profiles';

    // السماح بتعبيء هذه الحقول عبر Create أو Update
    protected $fillable = [
        'user_id',
        'full_name',
        'avatar',
        'national_id',
        'date_of_birth',
        'gender',
        'bio',
    ];

    // تحويل أنواع البيانات تلقائياً
    // سنستخدم date بدل datetime لتجنب مشاكل التوقيت الزمني إن لم يكن مطلوباً
    protected $casts = [
        'date_of_birth' => 'date',
    ];

    /*
    |--------------------------------------------------------------------------
    | العلاقات (Relationships)
    |--------------------------------------------------------------------------
    */

    /**
     * الملف الشخصي ينتمي لمستخدم واحد فقط (1 to 1)
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
    
    /*
    |--------------------------------------------------------------------------
    | دوال مساعدة (Helpers) - مطابقة للقيود الصارمة
    |--------------------------------------------------------------------------
    */

    /**
     * جلب رابط الصورة الشخصية بشكل آمن
     * تطبيق القاعدة: دائماً \Storage::url($path)
     */
    public function getAvatarUrlAttribute(): ?string
    {
        return $this->avatar ? Storage::url($this->avatar) : null;
    }
}