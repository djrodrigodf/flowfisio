<div class="space-y-4">
    @if (session('success'))
        <x-alert type="success" message="{{ session('success') }}" />
    @endif
    <x-card>
        <div class="flex justify-between items-center mb-4">
            <x-input wire:model.live.debounce.500ms="search" placeholder="Buscar por nome ou e-mail" />
            <x-button link="{{route('admin.usuarios.form')}}" icon="fas.plus" class="btn-primary">Novo Usuário</x-button>
        </div>

        <x-table :headers="$headers" :rows="$users" with-pagination per-page="perPage" :per-page-values="[10, 25, 50]">
            @scope('cell_roles', $user)
            @foreach($user->roles as $role)
                <span class="badge badge-secondary">{{ mb_ucfirst($role->name) }}</span>
            @endforeach
            @endscope

            @scope('actions', $user)
            <div class="flex gap-4">
                <x-button link="{{ route('admin.usuarios.form', ['user' => $user->id]) }}" icon="fas.edit" class="btn-secondary" title="Editar Usuário"></x-button>
                <x-button wire:click="disableUser({{$user->id}})" icon="fas.user-slash" class="btn-error" title="Remover Usuário"></x-button>
            </div>
            @endscope
        </x-table>

    </x-card>
</div>
