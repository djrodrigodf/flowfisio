<?php

namespace App\Observers;

use App\Models\Room;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class RoomObserver
{
    public function creating(Room $m): void
    {
        $m->name = trim($m->name);

        // default capacity = 1 se vier vazio
        if (blank($m->capacity)) {
            $m->capacity = 1;
        }

        // code opcional (tabela permite null), mas vamos preencher se vier vazio
        if (blank($m->code)) {
            $m->code = $this->uniqueCode($m->name);
        } else {
            $m->code = Str::upper($m->code);
        }
    }

    public function updating(Room $m): void
    {
        if ($m->isDirty('name') && blank($m->code)) {
            $m->code = $this->uniqueCode($m->name, $m->id);
        }
        if ($m->code) {
            $m->code = Str::upper($m->code);
        }
        if (blank($m->capacity)) {
            $m->capacity = 1;
        }
    }

    private function uniqueCode(string $name, ?int $ignoreId = null): string
    {
        $base = Str::upper(Str::substr(Str::slug($name, ''), 0, 10)) ?: 'ROOM';
        $code = $base;
        $i = 2;

        $exists = function ($c) use ($ignoreId) {
            $q = DB::table('rooms')->where('code', $c);
            if ($ignoreId) {
                $q->where('id', '!=', $ignoreId);
            }

            return $q->exists();
        };

        while ($exists($code)) {
            $suf = (string) $i++;
            $code = Str::substr($base, 0, 10 - strlen($suf)).$suf;
        }

        return $code;
    }
}
