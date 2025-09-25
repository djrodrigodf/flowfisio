<div class="space-y-4">

    <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
        <x-input type="date" label="Data" wire:model.live="date" />
        <x-choices-offline
            label="Profissional"
            wire:model.live="partner_id"
            :options="$partnerOptions"
            single
            clearable
        />
    </div>

    <x-card title="Presenças" class="bg-base-300">
        <x-table
            :headers="$headers"
            :rows="$this->rows"
            :sort-by="$sortBy"
            with-pagination
            per-page="perPage"
            :per-page-values="[10,25,50]"
            show-empty-text
        >
            @scope('cell_start_at', $row)
            {{ \Illuminate\Support\Carbon::parse($row->start_at)->format('H:i') }}
            @endscope

            @scope('cell_status', $row)
            <x-badge :value="$row->status" class="badge-soft" />
            @endscope

            @scope('actions', $row)
            <div class="flex gap-4">
                <x-button icon="o-check"     class="btn-success btn-xs" wire:click="checkIn({{ $row->id }})" spinner />
                <x-button icon="o-no-symbol" class="btn-warning btn-xs" wire:click="markNoShow({{ $row->id }})" spinner />
                <x-button icon="o-x-mark"    class="btn-error btn-xs"   wire:click="markCanceled({{ $row->id }})" spinner />
                <x-button icon="o-eye"       class="btn-ghost btn-xs"   link="{{ route('admin.appointments.show',$row->id) }}" />
            </div>
            @endscope
        </x-table>
    </x-card>


</div>
