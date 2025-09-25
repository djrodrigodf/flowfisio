<?php

namespace App\Livewire\Admin;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Spatie\Permission\Models\Role;

class UserForm extends Component
{
    public ?User $user = null;

    public string $name = '';

    public string $email = '';

    public string $password = '';

    public array $roles = [];

    public function mount(?int $id = null): void
    {

        if (isset($this->user)) {

            $this->name = $this->user->name;
            $this->email = $this->user->email;
            $this->is_admin = (bool) $this->user->is_admin;
            $this->roles = $this->user->roles()->pluck('id')->toArray();

        } else {
            $this->reset(['user', 'name', 'email', 'password', 'roles']);
        }

    }

    public function save()
    {
        $this->validate([
            'name' => 'required|string|min:3',
            'email' => ['required', 'email', Rule::unique('users', 'email')->ignore($this->user?->id)],
            'password' => $this->user ? 'nullable|min:6' : 'required|min:6',
            'roles' => 'array',
        ]);

        $user = User::updateOrCreate(
            ['id' => $this->user?->id],
            [
                'name' => $this->name,
                'email' => $this->email,
                'password' => $this->password ? Hash::make($this->password) : $this->user->password,
            ]
        );

        $user->syncRoles($this->roles);

        session()->flash('success', 'Usuário salvo com sucesso.');

        return redirect()->route('admin.users.index');
    }

    public function getPropertyAllRoles()
    {
        return Role::get();

    }

    public function render()
    {
        return view('livewire.admin.user-form', [
            'allRoles' => $this->getPropertyAllRoles(),
        ]);
    }
}
