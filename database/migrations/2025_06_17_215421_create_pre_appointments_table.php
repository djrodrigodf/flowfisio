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
        Schema::create('pre_appointments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pre_registration_id')->constrained()->cascadeOnDelete();

            $table->date('date');
            $table->time('time');
            $table->string('convenio')->nullable();
            $table->string('guide_number')->nullable();
            $table->string('procedure'); // especialidade
            $table->string('professional');
            $table->string('room')->nullable();
            $table->text('notes')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pre_appointments');
    }
};
