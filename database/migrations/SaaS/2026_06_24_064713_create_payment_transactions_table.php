<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // للحماية من ترتيب الملفات الأبجدي
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        Schema::create('payment_transactions', function (Blueprint $table) {
            $table->id();
            
            // 1. ربط الشركة (بشكل صحيح دون تكرار)
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            
            // 2. ربط الفاتورة (إن وجدت) - معدل كمثال
            $table->foreignId('invoice_id')->nullable()->constrained()->nullOnDelete();
            
            // 3. ربط الاشتراك (إن وجد) - معدل كمثال
            $table->foreignId('subscription_id')->nullable()->constrained('company_subscriptions')->nullOnDelete();

            $table->decimal('amount', 15, 4)->default(0);
            $table->string('payment_method')->nullable(); // paypal, bank transfer, etc.
            $table->string('status')->default('pending'); // pending, success, failed
            $table->string('transaction_ref')->unique()->nullable();
            
            $table->timestamps();
        });

        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_transactions');
    }
};