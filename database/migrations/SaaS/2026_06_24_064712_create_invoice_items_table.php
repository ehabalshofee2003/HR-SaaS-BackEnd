<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
Schema::create('invoice_items', function (Blueprint $table) {
        $table->bigIncrements('id');
        $table->unsignedBigInteger('invoice_id');
        $table->string('description');
        $table->integer('quantity')->default(1);
        $table->decimal('unit_price', 15, 4)->default(0);
        $table->timestamps();
        $table->softDeletes();

        $table->foreign('invoice_id')->references('id')->on('invoices')->cascade();
    });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoice_items');
    }
};
