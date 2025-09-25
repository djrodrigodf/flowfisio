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
        Schema::create('professional_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('professional_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('weekday'); // 0=Dom, 1=Seg, ... 6=Sáb
            $table->time('start_time');            // HH:MM:SS
            $table->time('end_time');              // HH:MM:SS
            $table->unsignedSmallInteger('slot_minutes')->default(40);
            $table->foreignId('room_id')->nullable()->constrained()->nullOnDelete(); // sala padrão
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->index(['professional_id', 'weekday']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('professional_schedules');
    }
};
