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
        Schema::create('treatment_table_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('treatment_table_id')->constrained('treatment_tables')->cascadeOnDelete();
            $table->foreignId('treatment_id')->constrained('treatments')->cascadeOnDelete();

            $table->decimal('price', 12, 2);
            $table->enum('repasse_type', ['percent', 'fixed'])->default('percent');
            $table->decimal('repasse_value', 12, 2)->default(0);
            $table->unsignedSmallInteger('duration_min')->nullable();
            $table->string('notes')->nullable();

            $table->timestamps();

            $table->unique(['treatment_table_id', 'treatment_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('treatment_table_items');
    }
};
