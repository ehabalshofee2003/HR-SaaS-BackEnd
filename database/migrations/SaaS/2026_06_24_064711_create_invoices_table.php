<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        Schema::create('invoices', function (Blueprint $table) {
            $table->id(); // بدلاً من bigIncrements
            
            // 1. عميل الشركة (تم إصلاح التكرار)
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            
            $table->string('invoice_number')->unique();
            $table->decimal('total', 15, 4)->default(0);
            $table->enum('status', ['draft', 'sent', 'paid', 'overdue', 'cancelled'])->default('draft');
            $table->date('due_date');
            $table->timestamp('paid_at')->nullable();
            
            // 2. الاشتراك (تم إصلاح نسيان إنشاء العمود)
            $table->foreignId('subscription_id')->constrained('company_subscriptions')->cascadeOnDelete();

            $table->timestamps();
            $table->softDeletes();
        });
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

    }

    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};