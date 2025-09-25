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
        Schema::create('rooms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('location_id')->constrained()->cascadeOnDelete();
            $table->string('name');               // ex.: Sala 01
            $table->string('code')->nullable();   // ex.: SALA-01
            $table->unsignedSmallInteger('capacity')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->unique(['location_id', 'name']); // não repetir nome da sala na mesma unidade
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rooms');
    }
};
