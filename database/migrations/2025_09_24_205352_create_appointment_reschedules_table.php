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
        Schema::create('appointment_reschedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('appointment_id')->constrained()->cascadeOnDelete();

            // snapshot do "antes"
            $table->timestamp('old_start_at');
            $table->timestamp('old_end_at');
            $table->foreignId('old_room_id')->nullable()->constrained('rooms')->nullOnDelete();

            // novo agendamento
            $table->timestamp('new_start_at');
            $table->timestamp('new_end_at');
            $table->foreignId('new_room_id')->nullable()->constrained('rooms')->nullOnDelete();

            $table->string('reason')->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete(); // quem reagendou

            $table->timestamps();

            $table->index(['appointment_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('appointment_reschedules');
    }
};
