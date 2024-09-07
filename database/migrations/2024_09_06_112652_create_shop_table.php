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
        Schema::create('shop', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('desc');
            $table->double('lat')->nullable();
            $table->double('lng')->nullable();
            $table->string('address')->nullable();
            $table->string('city')->nullable();
            $table->string('province')->nullable();
            $table->string('profile_picture')->nullable();
            $table->tinyInteger('status')->default(0);
            $table->bigInteger('balance')->default(0)->nullable();
            $table->bigInteger('user_id')->unsigned()->nullable();
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shop');
    }
};
