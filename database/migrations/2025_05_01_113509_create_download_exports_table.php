<?php

use App\Models\User;
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
        Schema::create('download_exports', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('type');
            $table->string('path')->nullable();
            $table->string('status')->default('pending');
            $table->string('error')->nullable();
            $table->foreignIdFor(User::class)->nullable()->constrained('users')->cascadeOnDelete();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('download_exports');
    }
};
