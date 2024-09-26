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
            $table->foreignId('city_id');
            $table->foreignId('province_id');
            $table->string('name');
            $table->string('email');
            $table->string('no_transaction');
            $table->integer('ongkir')->nullable();
            $table->integer('price');
            $table->integer('total_price');
            $table->string('address');
            $table->string('courier')->default('jne');
            $table->string('service');
            $table->string('invoice_url');
            $table->string('estimation');
            $table->string('status')->default('pending');
            $table->string('status_order')->default('pending');

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
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
