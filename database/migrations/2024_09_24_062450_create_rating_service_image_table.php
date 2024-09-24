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
        Schema::create('rating_service_image', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('rating_service_id')->unsigned();
            $table->string('picture_rating_service');

            $table->foreign('rating_service_id')->references('id')->on('rating_service');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rating_service_image');
    }
};
