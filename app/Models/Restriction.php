<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Restriction extends Model
{
    protected $fillable = ['scope', 'scope_id', 'start_at', 'end_at', 'reason', 'active'];

    protected $casts = [
        'start_at' => 'datetime',
        'end_at' => 'datetime',
        'active' => 'boolean',
    ];

    public function subject(): MorphTo
    {
        // type = scope, id = scope_id
        return $this->morphTo(__FUNCTION__, 'scope', 'scope_id');
    }

    // ------- helpers para a lista -------
    public function getPeriodAttribute(): string
    {
        $s = $this->start_at?->format('d/m/Y') ?? '—';
        $e = $this->end_at?->format('d/m/Y') ?? '—';

        return "{$s} — {$e}";
    }

    public function getScopeLabelAttribute(): string
    {
        if (is_null($this->scope)) {
            return 'Global';
        }
        $name = $this->subject?->name ?? "#{$this->scope_id}";

        return ucfirst($this->scope)." ({$name})";
    }

    public function scopeActive(Builder $q): Builder
    {
        return $q->where('active', true);
    }

    /** Período que intersecta [from, to] */
    public function scopeOverlapping(Builder $q, $from, $to): Builder
    {
        return $q->where(function ($w) use ($from, $to) {
            $w->where('start_at', '<=', $to)
                ->where('end_at', '>=', $from);
        });
    }
}
