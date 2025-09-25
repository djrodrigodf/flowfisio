<?php

namespace App\Livewire\Concerns;

trait WithMaryTable
{
    public array $sortBy = ['column' => 'id', 'direction' => 'desc'];

    public int $perPage = 10;

    public string $search = '';

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatedPerPage()
    {
        $this->resetPage();
    }

    public function updatedSortBy()
    {
        $this->resetPage();
    }
}
