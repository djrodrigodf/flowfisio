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
        Schema::create('pre_registration_links', function (Blueprint $table) {
            $table->id();
            $table->enum('type', ['normal', 'anamnese']);
            $table->string('specialty');
            $table->string('token')->unique();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete(); // usuário que gerou o link
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pre_registration_links');
    }
};
