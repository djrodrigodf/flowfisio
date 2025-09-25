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
        Schema::create('appointment_attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('appointment_id')->constrained()->cascadeOnDelete();

            $table->timestamp('checked_in_at');                 // momento do check-in
            $table->enum('mode', ['manual', 'auto'])->default('manual');
            $table->boolean('confirmed')->default(false);       // houve confirmação?
            $table->foreignId('confirmed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('notes')->nullable();

            $table->timestamps();

            $table->index(['appointment_id', 'checked_in_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('appointment_attendances');
    }
};
