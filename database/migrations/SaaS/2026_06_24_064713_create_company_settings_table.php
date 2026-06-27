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
    Schema::create('company_settings', function (Blueprint $table) {
        $table->bigIncrements('id');
        $table->unsignedBigInteger('company_id');
        $table->string('key');
        $table->text('value')->nullable();
        $table->enum('type', ['string', 'number', 'boolean', 'json'])->default('string');
        $table->timestamps();

        $table->foreign('company_id')->references('id')->on('companies')->cascade();
        $table->unique(['company_id', 'key']); // ممنوع تكرار نفس الإعداد للشركة
    });
    DB::statement('SET FOREIGN_KEY_CHECKS=1;');

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('company_settings');
    }
};
