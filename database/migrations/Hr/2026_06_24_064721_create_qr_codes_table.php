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
Schema::create('qr_codes', function (Blueprint $table) {
    $table->bigIncrements('id');
    $table->unsignedBigInteger('branch_id');
    $table->string('code')->unique();
    $table->enum('type', ['check_in', 'check_out']);
    $table->integer('usage_limit')->default(0);
    $table->dateTime('expires_at');
    $table->timestamps();
    $table->softDeletes();

    $table->foreign('branch_id')->references('id')->on('branches')->cascade();
});
    DB::statement('SET FOREIGN_KEY_CHECKS=1;');

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('qr_codes');
    }
};
