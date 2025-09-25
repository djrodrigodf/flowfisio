<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TreatmentTableItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'treatment_table_id', 'treatment_id', 'price', 'repasse_type', 'repasse_value', 'duration_min', 'notes',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'repasse_value' => 'decimal:2',
        'duration_min' => 'integer',
    ];

    public function table(): BelongsTo
    {
        return $this->belongsTo(TreatmentTable::class, 'treatment_table_id');
    }

    public function treatment(): BelongsTo
    {
        return $this->belongsTo(Treatment::class);
    }
}
