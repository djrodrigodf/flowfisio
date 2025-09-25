<?php

namespace App\Observers;

use App\Models\TreatmentTable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TreatmentTableObserver
{
    public function creating(TreatmentTable $t): void
    {
        $t->name = trim($t->name);
        if (blank($t->slug)) {
            $t->slug = $this->uniqueSlug($t->name);
        }
    }

    public function updating(TreatmentTable $t): void
    {
        if ($t->isDirty('name') && blank($t->slug)) {
            $t->slug = $this->uniqueSlug($t->name, $t->id);
        }
    }

    private function uniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $base = Str::slug($name) ?: 'tabela';
        $slug = $base;
        $i = 2;
        $exists = function ($candidate) use ($ignoreId) {
            $q = DB::table('treatment_tables')->where('slug', $candidate);
            if ($ignoreId) {
                $q->where('id', '!=', $ignoreId);
            }

            return $q->exists();
        };
        while ($exists($slug)) {
            $slug = $base.'-'.$i;
            $i++;
        }

        return $slug;
    }
}
