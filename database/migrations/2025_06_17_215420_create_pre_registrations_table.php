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
        Schema::create('pre_registrations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pre_registration_link_id')->constrained()->cascadeOnDelete();

            // Dados da criança
            $table->string('child_name');
            $table->date('child_birthdate');
            $table->string('child_gender');
            $table->string('child_cpf');
            $table->string('child_sus')->nullable();
            $table->string('child_nationality')->nullable();
            $table->string('child_address');
            $table->string('child_residence_type')->nullable();
            $table->string('child_phone')->nullable();
            $table->string('child_cellphone');
            $table->string('child_school')->nullable();
            $table->boolean('has_other_clinic');
            $table->text('other_clinic_info')->nullable();
            $table->enum('care_type', ['particular', 'liminar', 'garantia', 'convenio']);

            // Responsável principal
            $table->string('responsible_name');
            $table->string('responsible_kinship');
            $table->date('responsible_birthdate')->nullable();
            $table->string('responsible_nationality')->nullable();
            $table->string('responsible_cpf');
            $table->string('responsible_rg');
            $table->string('responsible_profession')->nullable();
            $table->string('responsible_phones');
            $table->string('responsible_email');
            $table->string('responsible_address');
            $table->string('responsible_residence_type')->nullable();
            $table->boolean('authorized_to_pick_up')->nullable();
            $table->boolean('is_financial_responsible')->nullable();

            $table->enum('status', ['aguardando', 'agendado', 'cancelado'])->default('aguardando');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pre_registrations');
    }
};
