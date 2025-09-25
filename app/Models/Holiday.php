<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Holiday extends Model
{
    protected $fillable = ['name', 'date', 'is_recurring', 'scope', 'scope_id', 'active'];

    protected $casts = [
        'date' => 'date',
        'is_recurring' => 'boolean',
        'active' => 'boolean',
    ];

    public function subject(): MorphTo
    {
        // type=scope, id=scope_id (igual Restriction)
        return $this->morphTo(__FUNCTION__, 'scope', 'scope_id');
    }

    /* ----- Acessores para tabela ----- */
    public function getDateLabelAttribute(): string
    {
        return $this->date?->format('d/m/Y') ?? '—';
    }

    public function getScopeLabelAttribute(): string
    {
        if (is_null($this->scope)) {
            return 'Global';
        }
        $name = $this->subject?->name ?? "#{$this->scope_id}";

        return ucfirst($this->scope)." ({$name})";
    }
}
