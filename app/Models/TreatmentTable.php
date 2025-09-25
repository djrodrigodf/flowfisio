<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TreatmentTable extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'slug', 'insurance_id', 'location_id', 'partner_id',
        'status', 'effective_from', 'effective_to', 'priority', 'created_by',
    ];

    protected $casts = [
        'effective_from' => 'date',
        'effective_to' => 'date',
        'priority' => 'integer',
    ];

    // Relacionamentos
    public function insurance(): BelongsTo
    {
        return $this->belongsTo(Insurance::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function partner(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(\App\Models\Partner::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(TreatmentTableItem::class);
    }
}
