<?php

namespace App\Livewire\Admin;

use App\Models\Insurance;
use App\Models\Patient;
use App\Models\PreRegistration;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Mary\Traits\Toast;

class PatientForm extends Component
{
    use Toast;

    public ?Patient $model = null;

    public $patient;

    // Dados principais
    #[Validate('required|string|max:255')]
    public string $name = '';

    #[Validate('nullable|string|max:255')]
    public ?string $document = null;

    #[Validate('nullable|string|max:255')]
    public ?string $sus = null;

    #[Validate('nullable|date')]
    public ?string $birthdate = null;

    #[Validate('nullable|in:M,F')]
    public ?string $gender = null;

    #[Validate('nullable|string|max:255')]
    public ?string $nationality = null;

    #[Validate('boolean')]
    public bool $active = true;

    // Contato
    #[Validate('nullable|email|max:255')]
    public ?string $email = null;

    #[Validate('nullable|string|max:255')]
    public ?string $phone = null;

    #[Validate('nullable|string|max:255')]
    public ?string $phone_alt = null;

    // Endereço
    #[Validate('nullable|string|max:12')]
    public ?string $zip_code = null;

    #[Validate('nullable|string|max:255')]
    public ?string $address = null;

    #[Validate('nullable|string|max:255')]
    public ?string $residence_type = null;

    #[Validate('nullable|string|max:255')]
    public ?string $city = null;

    #[Validate('nullable|string|size:2')]
    public ?string $state = null;

    // Escola
    #[Validate('nullable|string|max:255')]
    public ?string $school = null;

    // Convênio
    #[Validate('nullable|exists:insurances,id')]
    public ?int $insurance_id = null;

    #[Validate('nullable|string|max:255')]
    public ?string $insurance_number = null;

    #[Validate('nullable|date')]
    public ?string $insurance_valid_until = null;

    // Outros
    #[Validate('boolean')]
    public bool $has_other_clinic = false;

    #[Validate('nullable|string')]
    public ?string $other_clinic_info = null;

    #[Validate('nullable|string|max:255')]
    public ?string $care_type = null;

    #[Validate('nullable|string')]
    public ?string $notes = null;

    // Relacionamentos auxiliares
    #[Validate('nullable|exists:pre_registrations,id')]
    public ?int $pre_registration_id = null;

    public array $insOptions = [];

    /** Route Model Binding: “new” não entra aqui (ver rotas) */
    public function mount($patient = null): void
    {

        if ($this->patient) {

            $this->model = Patient::find($this->patient);
            $patient = $this->model;
            $this->fill($patient->only([
                'pre_registration_id',
                'name', 'document', 'sus', 'birthdate', 'gender', 'nationality', 'active',
                'email', 'phone', 'phone_alt',
                'zip_code', 'address', 'residence_type', 'city', 'state',
                'school',
                'insurance_id', 'insurance_number', 'insurance_valid_until',
                'has_other_clinic', 'other_clinic_info',
                'care_type', 'notes',
            ]));
            // cast date->toDateString
            $this->birthdate = optional($patient->birthdate)?->toDateString();
            $this->insurance_valid_until = optional($patient->insurance_valid_until)?->toDateString();
        } else {
            // criando novo: se vier “pre_registration_id” na query, preenche
            if ($prId = request('pre_registration_id')) {
                $pr = PreRegistration::find($prId);
                if ($pr) {
                    $this->pre_registration_id = $pr->id;
                    $this->name = $pr->child_name ?? '';
                    $this->birthdate = optional($pr->child_birthdate)?->toDateString();
                    $this->gender = $pr->child_gender === 'feminino' ? 'F' : ($pr->child_gender === 'masculino' ? 'M' : null);
                    $this->sus = $pr->child_sus;
                    $this->nationality = $pr->child_nationality;
                    $this->address = $pr->child_address;
                    $this->residence_type = $pr->child_residence_type;
                    $this->phone = $pr->child_cellphone;
                    $this->school = $pr->child_school;
                    $this->care_type = $pr->care_type;
                    $this->notes = $pr->notes;
                }
            }
        }

        // opções de convênio
        if (class_exists(Insurance::class)) {
            $this->insOptions = Insurance::select('id', 'name')->orderBy('name')->get()->toArray();
        }
    }

    public function save(): void
    {
        $this->validate();

        $data = [
            'pre_registration_id' => $this->pre_registration_id,
            'name' => $this->name,
            'document' => $this->document,
            'sus' => $this->sus,
            'birthdate' => $this->birthdate,
            'gender' => $this->gender,
            'nationality' => $this->nationality,
            'active' => $this->active,
            'email' => $this->email,
            'phone' => $this->phone,
            'phone_alt' => $this->phone_alt,
            'zip_code' => $this->zip_code,
            'address' => $this->address,
            'residence_type' => $this->residence_type,
            'city' => $this->city,
            'state' => $this->state,
            'school' => $this->school,
            'insurance_id' => $this->insurance_id,
            'insurance_number' => $this->insurance_number,
            'insurance_valid_until' => $this->insurance_valid_until,
            'has_other_clinic' => $this->has_other_clinic,
            'other_clinic_info' => $this->other_clinic_info,
            'care_type' => $this->care_type,
            'notes' => $this->notes,
        ];

        $this->model
            ? $this->model->update($data)
            : $this->model = Patient::create($data);

        $this->success('Paciente salvo!', redirectTo: route('admin.patients.index'));
    }

    public function render()
    {
        return view('livewire.admin.patient-form')
            ->title($this->model ? 'Editar Paciente' : 'Novo Paciente');
    }
}
