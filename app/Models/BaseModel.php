<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

abstract class BaseModel extends Model
{
    use HasFactory;

    /**
     * Pessimistic Locking - لمنع التضارب في الـ Payroll
     * 
     * @param Builder $query
     */
    public function scopeLockForUpdate(Builder $query): Builder
    {
        return $query->lockForUpdate();
    }

    /**
     * إنشاء سجل داخل Transaction
     */
    public static function createInTransaction(array $attributes): static
    {
        return DB::transaction(function () use ($attributes) {
            return static::create($attributes);
        });
    }
}