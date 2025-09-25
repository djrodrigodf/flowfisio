<?php

namespace App\Domain\Pricing\Actions;

use App\Models\TreatmentTable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PublishTreatmentTable
{
    public function handle(TreatmentTable $table): TreatmentTable
    {
        return DB::transaction(function () use ($table) {
            if ($table->items()->count() === 0) {
                throw ValidationException::withMessages([
                    'status' => 'Não é possível publicar uma tabela sem itens.',
                ]);
            }
            $table->status = 'published';
            $table->save();

            return $table;
        });
    }
}
