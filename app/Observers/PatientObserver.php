<?php

namespace App\Observers;

use App\Models\Patient;
use Illuminate\Support\Str;

class PatientObserver
{
    public function creating(Patient $p): void
    {
        $this->normalize($p);
        if (is_null($p->active)) {
            $p->active = true;
        }
    }

    public function updating(Patient $p): void
    {
        $this->normalize($p);
    }

    private function normalize(Patient $p): void
    {
        // limpa não-dígitos em documento/telefones/CEP
        foreach (['document', 'phone', 'phone_alt', 'zip_code', 'sus'] as $attr) {
            if (! empty($p->{$attr})) {
                $p->{$attr} = preg_replace('/\D+/', '', $p->{$attr});
            }
        }
        // estado em 2 letras maiúsculas
        if (! empty($p->state)) {
            $p->state = Str::upper(substr($p->state, 0, 2));
        }
        // gender para 1-char (M/F) se vier “masculino/feminino”
        if (! empty($p->gender) && strlen($p->gender) > 1) {
            $map = ['masculino' => 'M', 'feminino' => 'F'];
            $p->gender = $map[Str::lower($p->gender)] ?? Str::upper(substr($p->gender, 0, 1));
        }
    }
}
