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
        Schema::create('rating_product_image', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('rating_product_id')->unsigned();
            $table->string('picture_rating_product');

            $table->foreign('rating_product_id')->references('id')->on('rating_product');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rating_product_image');
    }
};
