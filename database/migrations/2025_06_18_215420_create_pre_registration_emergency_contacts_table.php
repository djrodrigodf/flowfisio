<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('pre_registration_emergency_contacts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pre_registration_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('kinship');
            $table->string('phone');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pre_registration_emergency_contacts');
    }
};
