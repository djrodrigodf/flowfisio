<?php

namespace App\Livewire\Public;

use App\Models\PreRegistration;
use App\Models\PreRegistrationLink;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Illuminate\Support\Facades\Validator;

class PreCadastro extends Component
{
    public string $token;
    public ?PreRegistrationLink $link = null;

    public int $step = 1;

    // Etapa 1: Criança
    public string $child_name = '';
    public string $child_birthdate = '';
    public string $child_gender = '';
    public string $child_cpf = '';
    public ?string $child_sus = null;
    public ?string $child_nationality = null;
    public string $child_address = '';
    public ?string $child_residence_type = null;
    public ?string $child_phone = null;
    public string $child_cellphone = '';
    public ?string $child_school = null;
    public bool $has_other_clinic = false;
    public bool $has_other_residence = false;
    public ?string $other_clinic_info = null;
    public string $care_type = '';

    // Etapa 2: Responsável
    public string $responsible_name = '';
    public string $responsible_kinship = '';
    public ?string $responsible_birthdate = null;
    public ?string $responsible_nationality = null;
    public string $responsible_cpf = '';
    public string $responsible_rg = '';
    public ?string $responsible_profession = null;
    public string $responsible_phones = '';
    public string $responsible_email = '';
    public string $responsible_address = '';
    public ?string $responsible_residence_type = '';
    public bool $authorized_to_pick_up = false;
    public bool $is_financial_responsible = false;

    public function mount(string $token)
    {
        $this->token = $token;
        $this->link = PreRegistrationLink::where('token', $token)->firstOrFail();

        if ($this->link->preRegistrations()->exists()) {
            $this->step = 99;
        }
    }

    public function goToStep2()
    {
        $this->validate($this->rulesStep1());
        $this->step = 2;
    }

    public function submit()
    {
        $this->validate($this->rulesStep2());

        PreRegistration::create([
            'pre_registration_link_id' => $this->link->id,
            // criança
            'child_name' => $this->child_name,
            'child_birthdate' => $this->child_birthdate,
            'child_gender' => $this->child_gender,
            'child_cpf' => $this->child_cpf,
            'child_sus' => $this->child_sus,
            'child_nationality' => $this->child_nationality,
            'child_address' => $this->child_address,
            'child_residence_type' => $this->child_residence_type,
            'child_phone' => $this->child_phone,
            'child_cellphone' => $this->child_cellphone,
            'child_school' => $this->child_school,
            'has_other_clinic' => $this->has_other_clinic,
            'other_clinic_info' => $this->other_clinic_info,
            'care_type' => $this->care_type,
            // responsável
            'responsible_name' => $this->responsible_name,
            'responsible_kinship' => $this->responsible_kinship,
            'responsible_birthdate' => $this->responsible_birthdate,
            'responsible_nationality' => $this->responsible_nationality,
            'responsible_cpf' => $this->responsible_cpf,
            'responsible_rg' => $this->responsible_rg,
            'responsible_profession' => $this->responsible_profession,
            'responsible_phones' => $this->responsible_phones,
            'responsible_email' => $this->responsible_email,
            'responsible_address' => $this->responsible_address,
            'responsible_residence_type' => $this->responsible_residence_type,
            'authorized_to_pick_up' => $this->authorized_to_pick_up,
            'is_financial_responsible' => $this->is_financial_responsible,
        ]);

        $this->step = 99;


    }

    public function rulesStep1(): array
    {
        return [
            'child_name' => 'required|string',
            'child_birthdate' => 'required|date',
            'child_gender' => 'required|string',
            'child_cpf' => 'required|string|size:14',
            'child_address' => 'required|string',
            'child_cellphone' => 'required|string',
            'care_type' => 'required|in:particular,liminar,garantia,convenio',
        ];
    }

    public function getKinshipOptions(): array
    {
        return collect([
            'pai' => 'Pai',
            'mae' => 'Mãe',
            'avo' => 'Avô/Avó',
            'irmao' => 'Irmão/Irmã',
            'tio' => 'Tio/Tia',
            'primo' => 'Primo/Prima',
            'padrasto' => 'Padrasto',
            'madrasta' => 'Madrasta',
            'enteado' => 'Enteado(a)',
            'outro' => 'Outro',
            'responsavel_legal' => 'Responsável Legal',
        ])->map(fn($name, $id) => ['id' => $id, 'name' => $name])->values()->toArray();
    }

    public function rulesStep2(): array
    {
        return [
            'responsible_name'     => 'required|string',
            'responsible_kinship'  => 'required|string',
            'responsible_cpf'      => 'required|string|size:14',
            'responsible_rg'       => 'required|string',
            'responsible_phones'   => 'required|string',
            'responsible_email'    => 'required|email',
            'responsible_residence_type' => [
                Rule::requiredIf($this->has_other_residence),
                'string',
            ],
            'responsible_address'  => [
                Rule::requiredIf($this->has_other_residence),
                'string',
            ],

        ];
    }

    public function render()
    {
        return view('livewire.public.pre-cadastro',
        [
            'kinshipOptions' => $this->getKinshipOptions(),
        ])->layout('components.layouts.appNoSideBar');
    }
}
