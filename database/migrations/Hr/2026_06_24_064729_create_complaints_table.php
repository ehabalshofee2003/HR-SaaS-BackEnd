<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void   
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
Schema::create('complaints', function (Blueprint $table) {
    $table->bigIncrements('id');
    $table->unsignedBigInteger('company_id');
    $table->unsignedBigInteger('user_id')->nullable(); // صاحب الشكوى (Nullable للحفاظ على مجهولية الهوية)
    $table->unsignedBigInteger('department_id')->nullable();
    $table->string('subject');
    $table->text('description');
    $table->enum('status', ['open', 'in_review', 'resolved', 'closed'])->default('open');
    $table->text('response')->nullable();
    $table->unsignedBigInteger('resolved_by')->nullable();
    $table->boolean('is_anonymous')->default(false);
    $table->timestamps();
    $table->softDeletes();

    $table->foreign('company_id')->references('id')->on('companies')->restrict();
    $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
    $table->foreign('department_id')->references('id')->on('departments')->nullOnDelete();
    $table->foreign('resolved_by')->references('id')->on('users')->nullOnDelete();
});

    DB::statement('SET FOREIGN_KEY_CHECKS=1;'); 
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('complaints');
    }
};
