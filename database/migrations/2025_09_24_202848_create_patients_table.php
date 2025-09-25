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
        Schema::create('patients', function (Blueprint $table) {
            $table->id();

            // Identificação do paciente
            $table->foreignId('pre_registration_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('document')->nullable();          // CPF
            $table->string('sus')->nullable();
            $table->date('birthdate')->nullable();
            $table->string('gender', 1)->nullable();         // M/F/O
            $table->string('nationality')->nullable();
            $table->boolean('active')->default(true);

            // Contato
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('phone_alt')->nullable();

            // Endereço
            $table->string('zip_code', 12)->nullable();
            $table->string('address')->nullable();
            $table->string('residence_type')->nullable();
            $table->string('city')->nullable();
            $table->string('state', 2)->nullable();

            // Escola (se for aplicável ao público)
            $table->string('school')->nullable();

            // Convênio principal
            $table->foreignId('insurance_id')->nullable()->constrained()->nullOnDelete();
            $table->string('insurance_number')->nullable();
            $table->date('insurance_valid_until')->nullable();

            // Fluxo inicial
            $table->boolean('has_other_clinic')->default(false);
            $table->text('other_clinic_info')->nullable();
            $table->string('care_type')->nullable(); // ex.: particular/convênio

            // Observações gerais
            $table->text('notes')->nullable();

            $table->timestamps();

            // Índices úteis
            $table->index('document');
            $table->index('email');
            $table->index(['name', 'birthdate']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('patients');
    }
};
