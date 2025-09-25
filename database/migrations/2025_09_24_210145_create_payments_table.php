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
        Schema::create('payments', function (Blueprint $table) {
            $table->id();

            $table->foreignId('appointment_id')->constrained()->cascadeOnDelete();

            // método e status do pagamento
            $table->enum('method', ['cash', 'pix', 'card', 'boleto', 'insurance'])->index();
            $table->enum('status', ['pending', 'paid', 'failed', 'canceled'])->default('paid')->index();

            // valores
            $table->decimal('amount', 10, 2);              // valor bruto lançado
            $table->enum('discount_type', ['percent', 'fixed'])->nullable();
            $table->decimal('discount_value', 10, 2)->nullable();
            $table->string('discount_reason')->nullable();
            $table->decimal('amount_paid', 10, 2);         // valor efetivamente pago após desconto

            // datas e referências
            $table->timestamp('received_at')->nullable();  // quando recebeu/confirmação
            $table->string('reference')->nullable();       // NSU, txid PIX, nº boleto etc.
            $table->string('receipt_url')->nullable();     // link para recibo/nota
            $table->json('metadata')->nullable();

            $table->timestamps();

            $table->index(['appointment_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
