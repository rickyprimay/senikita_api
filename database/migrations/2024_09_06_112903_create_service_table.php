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
        Schema::create('service', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->bigInteger('price');
            $table->text('desc');
            $table->tinyInteger('type');
            $table->tinyInteger('status')->default(0);
            $table->string('thumbnail');
            $table->integer('person_amount')->nullable();
            $table->bigInteger('category_id')->nullable()->unsigned();
            $table->bigInteger('shop_id')->unsigned();


            $table->foreign('category_id')->references('id')->on('category')->onDelete('cascade');
            $table->foreign('shop_id')->references('id')->on('shop')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('service');
    }
};
