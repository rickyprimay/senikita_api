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
        Schema::create('order', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('user_id')->unsigned();
            $table->string('email');
            $table->bigInteger('address_id')->unsigned();
            $table->string('no_transaction');
            $table->integer('ongkir')->nullable();
            $table->integer('price');
            $table->integer('total_price');
            $table->string('courier')->default('jne');
            $table->string('service');
            $table->string('invoice_url');
            $table->string('estimation');
            $table->string('status')->default('pending');
            $table->string('status_order')->default('pending');
            $table->string('note')->nullable();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            // $table->foreign('address_id')->references('id')->on('address')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
