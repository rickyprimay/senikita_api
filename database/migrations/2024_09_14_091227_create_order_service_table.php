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
        Schema::create('order_service', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('user_id')->unsigned();
            $table->bigInteger('service_id')->unsigned();
            $table->string('name');
            $table->string('activity_name');
            $table->date('activity_date');
            $table->string('phone');
            $table->string('email');
            $table->time('activity_time');
            $table->bigInteger('province_id')->unsigned();
            $table->bigInteger('city_id')->unsigned();
            $table->integer('attendee');
            $table->string('no_transaction');
            $table->integer('price');
            $table->string('address');
            $table->text('description')->nullable();
            $table->string('invoice_url');
            $table->json('optional_document')->nullable();
            $table->string('status')->default('pending');

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('service_id')->references('id')->on('service');
            $table->foreign('province_id')->references('id')->on('provinces');
            $table->foreign('city_id')->references('id')->on('cities');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_service');
    }
};
