<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
public function up(): void
{
    Schema::table('notifications', function (Blueprint $table) {
        $table->string('type', 50)->change(); // enum → varchar، مرونة كاملة لأي Epic مستقبلي
        $table->string('link_type', 100)->nullable()->after('data');
        $table->unsignedBigInteger('link_id')->nullable()->after('link_type');
    });
}

public function down(): void
{
    Schema::table('notifications', function (Blueprint $table) {
        $table->dropColumn(['link_type', 'link_id']);
        // ملاحظة: التراجع عن enum يتطلب تحديد القيم القديمة يدوياً إن لزم، تم تركه كـ varchar
    });
}
};
