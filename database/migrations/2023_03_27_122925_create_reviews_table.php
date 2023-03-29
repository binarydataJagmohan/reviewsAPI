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
        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            $table->integer('review_by')->nullable();
            $table->integer('review_to')->nullable();
            $table->string('description')->nullable();
            $table->integer('total_rating')->nullable();
            $table->integer('avg_rating')->nullable();
            $table->integer('thumbs_up')->nullable();
            $table->integer('thumbs_down')->nullable();
            $table->enum('status',['active','deleted'])->default('active');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reviews');
    }
};
