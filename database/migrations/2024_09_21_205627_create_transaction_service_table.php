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
        Schema::create('transaction_service', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('order_service_id')->unsigned();
            $table->string('payment_status')->default('pending');
            $table->date('payment_date')->nullable();

            $table->foreign('order_service_id')->references('id')->on('order_service')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transaction_service');
    }
};
