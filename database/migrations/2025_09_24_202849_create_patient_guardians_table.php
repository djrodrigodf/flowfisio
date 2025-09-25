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
        Schema::create('patient_guardians', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();

            $table->string('name');
            $table->string('kinship')->nullable();           // grau de parentesco
            $table->date('birthdate')->nullable();
            $table->string('nationality')->nullable();
            $table->string('cpf', 20)->nullable();
            $table->string('rg', 30)->nullable();
            $table->string('profession')->nullable();

            $table->string('phones')->nullable();
            $table->string('email')->nullable();
            $table->string('address')->nullable();
            $table->string('residence_type')->nullable();

            // Flags
            $table->boolean('is_primary')->default(false);
            $table->boolean('is_financial')->default(false);
            $table->boolean('can_pick_up')->default(false);

            $table->text('notes')->nullable();

            $table->timestamps();
            $table->index(['patient_id', 'is_primary']);
            $table->index(['patient_id', 'is_financial']);
            $table->index(['patient_id', 'can_pick_up']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('patient_guardians');
    }
};
