<?php

namespace App\Services;

use App\Models\Patient;
use App\Models\PatientEmergencyContact;
use App\Models\PatientGuardian;
use App\Models\PreRegistration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PreRegistrationConverterService
{
    public function convert(int $preRegistrationId): Patient
    {
        /** @var PreRegistration $pre */
        $pre = PreRegistration::with(['emergencyContacts', 'additionalResponsibles'])->findOrFail($preRegistrationId);

        // Evita conversão duplicada
        if ($existing = Patient::where('pre_registration_id', $pre->id)->first()) {
            return $existing;
        }

        return DB::transaction(function () use ($pre) {
            // 1) Paciente (map dos campos child_*)
            $patient = Patient::create([
                'pre_registration_id' => $pre->id,

                'name' => $pre->child_name,
                'birthdate' => $this->toDateString($pre->child_birthdate),   // <-- normaliza
                'gender' => $this->mapGender($pre->child_gender),         // <-- mapeia
                'document' => $pre->child_cpf,
                'sus' => $pre->child_sus,
                'nationality' => $pre->child_nationality,

                'address' => $pre->child_address,
                'residence_type' => $pre->child_residence_type,
                'phone' => $pre->child_phone,
                'phone_alt' => $pre->child_cellphone,
                'school' => $pre->child_school,

                'has_other_clinic' => (bool) $pre->has_other_clinic,
                'other_clinic_info' => $pre->other_clinic_info,
                'care_type' => $pre->care_type,
            ]);

            // 2) Responsável principal
            if ($pre->responsible_name) {
                PatientGuardian::create([
                    'patient_id' => $patient->id,
                    'name' => $pre->responsible_name,
                    'kinship' => $pre->responsible_kinship,
                    'birthdate' => $pre->responsible_birthdate,
                    'nationality' => $pre->responsible_nationality,
                    'cpf' => $pre->responsible_cpf,
                    'rg' => $pre->responsible_rg,
                    'profession' => $pre->responsible_profession,
                    'phones' => $pre->responsible_phones,
                    'email' => $pre->responsible_email,
                    'address' => $pre->responsible_address,
                    'residence_type' => $pre->responsible_residence_type,
                    'is_primary' => true,
                    'is_financial' => (bool) $pre->is_financial_responsible,
                    'can_pick_up' => $this->isAuthorizedToPickUp($pre->authorized_to_pick_up, $pre->responsible_name),
                ]);
            }

            // 3) Responsáveis adicionais
            foreach ($pre->additionalResponsibles as $add) {
                PatientGuardian::create([
                    'patient_id' => $patient->id,
                    'name' => $add->name ?? ($add->responsible_name ?? 'Responsável'),
                    'kinship' => $add->kinship ?? null,
                    'cpf' => $add->cpf ?? null,
                    'rg' => $add->rg ?? null,
                    'phones' => $add->phones ?? null,
                    'email' => $add->email ?? null,
                    'can_pick_up' => true,
                    'is_primary' => false,
                    'is_financial' => false,
                ]);
            }

            // 4) Contatos de emergência
            foreach ($pre->emergencyContacts as $ec) {
                PatientEmergencyContact::create([
                    'patient_id' => $patient->id,
                    'name' => $ec->name,
                    'relationship' => $ec->relationship ?? null,
                    'phone' => $ec->phone,
                    'phone_alt' => $ec->phone_alt ?? null,
                    'notes' => $ec->notes ?? null,
                ]);
            }

            // 5) (Opcional) copiar mídias do pré para o paciente
            // if ($patient instanceof \Spatie\MediaLibrary\HasMedia) {
            //     foreach ($pre->getMedia() as $media) {
            //         $media->copy($patient, $media->collection_name);
            //     }
            // }

            return $patient;
        });
    }

    private function mapGender(?string $raw): ?string
    {
        if (! $raw) {
            return null;
        }
        $v = Str::of($raw)->lower()->trim()->toString();

        // mapeamentos comuns
        $m = ['m', 'masculino', 'male', 'homem', 'boy'];
        $f = ['f', 'feminino', 'female', 'mulher', 'girl'];
        $o = ['o', 'outro', 'outros', 'nao-binario', 'não-binário', 'non-binary', 'nb', 'x', 'indefinido', 'não informado', 'nao informado'];

        if (in_array($v, $m, true)) {
            return 'M';
        }
        if (in_array($v, $f, true)) {
            return 'F';
        }
        if (in_array($v, $o, true)) {
            return 'O';
        }

        // fallback seguro
        return null; // ou 'O' se preferir
    }

    private function toDateString($value): ?string
    {
        try {
            return $value ? \Carbon\Carbon::parse($value)->toDateString() : null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function isAuthorizedToPickUp(?string $authorizedList, ?string $responsibleName): bool
    {
        if (! $authorizedList || ! $responsibleName) {
            return false;
        }
        $norm = Str::of($authorizedList)->lower();

        return $norm->contains(Str::of($responsibleName)->lower());
    }
}
