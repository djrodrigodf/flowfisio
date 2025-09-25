<?php

namespace App\Observers;

use App\Models\Location;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class LocationObserver
{
    public function creating(Location $loc): void
    {
        if (blank($loc->code)) {
            $loc->code = $this->uniqueCode($loc->name);
        } else {
            $loc->code = Str::upper($loc->code);
        }
    }

    public function updating(Location $loc): void
    {
        // se quiser regenerar quando o nome mudar e o code estiver vazio
        if ($loc->isDirty('name') && blank($loc->code)) {
            $loc->code = $this->uniqueCode($loc->name);
        }
    }

    private function uniqueCode(string $name): string
    {
        $base = Str::upper(Str::substr(Str::slug($name, ''), 0, 10)) ?: 'LOC';
        $code = $base;
        $i = 2;

        $exists = fn ($c) => DB::table('locations')->where('code', $c)->exists();
        while ($exists($code)) {
            $suffix = (string) $i++;
            $code = Str::substr($base, 0, 10 - strlen($suffix)).$suffix;
        }

        return $code;
    }
}
