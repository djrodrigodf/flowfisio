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
        Schema::create('holiday_import_logs', function (Blueprint $t) {
            $t->id();
            $t->string('provider');           // ex.: 'invertexto'
            $t->unsignedSmallInteger('year');
            $t->string('state', 5)->nullable(); // DF, SP, etc. (API aceita UF)
            $t->enum('scope', ['location', 'room', 'professional'])->nullable(); // null = Global
            $t->unsignedBigInteger('scope_id')->nullable();
            $t->unsignedInteger('created_count')->default(0);
            $t->unsignedInteger('updated_count')->default(0);
            $t->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();

            $t->unique(['provider', 'year', 'state', 'scope', 'scope_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('holiday_import_logs');
    }
};
