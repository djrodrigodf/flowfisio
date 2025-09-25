<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use Mary\Traits\Toast;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PermissionManager extends Component
{
    use Toast;

    public ?string $selectedRole = null;

    public array $permissions = [];

    public $roles;

    public $permissao = '';

    public $allPermissions = [];

    public $addModal = false;

    public function mount(): void
    {
        $this->loadInit();

        $this->loadPermissions(); // agora sim, depois de carregar tudo
    }

    private function loadInit()
    {
        $this->roles = Role::get();
        $this->allPermissions = Permission::all()->pluck('name');

        if (! $this->selectedRole && $this->roles->isNotEmpty()) {
            $this->selectedRole = $this->roles->first()->id;
        }
    }

    public function updatedSelectedRole(): void
    {
        $this->loadPermissions();
    }

    public function novaPermissao()
    {

        Role::create(['name' => $this->permissao, 'guard_name' => 'web']);
        $this->toast('success', 'Nova permissão criada com sucesso.', '', 'bottom', 'fas.save', 'alert-success');
        $this->addModal = false;
        $this->loadInit();
    }

    public function selectAll(): void
    {
        foreach ($this->allPermissions as $permission) {
            if (str_contains($permission, '.')) {
                [$module, $action] = explode('.', $permission, 2);
                $this->permissions[$module][$action] = true;
            }
        }
    }

    public function deselectAll(): void
    {
        foreach ($this->allPermissions as $permission) {
            if (str_contains($permission, '.')) {
                [$module, $action] = explode('.', $permission, 2);
                $this->permissions[$module][$action] = false;
            }
        }
    }

    public function loadPermissions(): void
    {
        $role = Role::find($this->selectedRole);

        $this->permissions = [];

        if ($role) {
            foreach ($role->permissions as $permission) {
                if (str_contains($permission->name, '.')) {
                    [$module, $action] = explode('.', $permission->name, 2);
                    $this->permissions[$module][$action] = true;
                } else {
                    // Caso tenha permissões sem ponto, ainda adiciona como plano
                    $this->permissions[$permission->name] = true;
                }
            }
        }
    }

    public function save(): void
    {
        $role = Role::find($this->selectedRole);

        $selectedPermissions = collect($this->permissions)
            ->flatMap(function ($actions, $module) {
                return collect($actions)
                    ->filter()
                    ->keys()
                    ->map(fn ($action) => "{$module}.{$action}");
            })
            ->values()
            ->all();

        $role->syncPermissions($selectedPermissions);

        $this->toast('success', 'Permissões atualizadas com sucesso.', '', 'bottom', 'fas.save', 'alert-success');

    }

    public function render()
    {
        return view('livewire.admin.permission-manager');
    }
}
