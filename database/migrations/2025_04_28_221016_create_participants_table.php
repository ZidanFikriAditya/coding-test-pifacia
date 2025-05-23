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
        Schema::create('participants', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('seminar_id');
            $table->foreign('seminar_id')->on('seminars')->references('id')->onDelete('cascade');
            $table->string('name');
            $table->string('email');
            $table->dateTime('registered_at')->nullable();
            $table->json('extra_data')->nullable();
            $table->boolean('is_confirmed')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('participants');
    }
};
