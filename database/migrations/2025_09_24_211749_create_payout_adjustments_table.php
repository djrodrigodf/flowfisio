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
        Schema::create('payout_adjustments', function (Blueprint $table) {
            $table->id();

            $table->foreignId('payout_id')->constrained()->cascadeOnDelete();

            // valor pode ser POSITIVO (bônus) ou NEGATIVO (desconto/taxa)
            $table->decimal('amount', 12, 2);
            $table->enum('type', ['bonus', 'deduction', 'fee', 'correction'])->default('correction');
            $table->string('reason')->nullable();

            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete(); // quem lançou
            $table->json('metadata')->nullable();

            $table->timestamps();

            $table->index(['payout_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payout_adjustments');
    }
};
