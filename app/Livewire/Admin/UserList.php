<?php

namespace App\Livewire\Admin;

use App\Models\User;
use Livewire\Component;
use Livewire\WithPagination;

class UserList extends Component
{
    use WithPagination;

    public string $search = '';
    public $perPage = 10;
    public $page = 1;
    protected $queryString = ['page'];

    public function getPropertyHeaders(): array
    {
        return [
            ['key' => 'name', 'label' => 'Nome'],
            ['key' => 'email', 'label' => 'E-mail'],
            ['key' => 'roles', 'label' => 'Funções'],
            ['key' => 'actions', 'label' => 'Ações'],
        ];
    }

    public function render()
    {
        $users = User::query()
            ->with('roles')
            ->when($this->search, fn ($q) =>
            $q->where('name', 'like', "%{$this->search}%")
                ->orWhere('email', 'like', "%{$this->search}%")
            )
            ->paginate($this->perPage);


        return view('livewire.admin.user-list', [
            'users' => $users,
            'headers' => $this->getPropertyHeaders(),
        ]);
    }
}

