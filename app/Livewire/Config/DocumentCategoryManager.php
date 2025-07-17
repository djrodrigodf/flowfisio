<?php

namespace App\Livewire\Config;

use App\Models\DocumentCategory;
use Livewire\Component;
use Mary\Traits\Toast;

class DocumentCategoryManager extends Component
{
    use Toast;

    public $categories;
    public $name = '';
    public $categoryId = null;
    public $showModal = false;

    protected $rules = [
        'name' => 'required|string|max:255',
    ];

    public function mount()
    {
        $this->loadCategories();
    }

    public function loadCategories()
    {
        $this->categories = DocumentCategory::orderBy('name')->get();
    }

    public function create()
    {
        $this->reset(['name', 'categoryId']);
        $this->showModal = true;
    }

    public function edit($id)
    {
        $category = DocumentCategory::findOrFail($id);
        $this->name = $category->name;
        $this->categoryId = $category->id;
        $this->showModal = true;
    }

    public function save()
    {
        $this->validate();

        DocumentCategory::updateOrCreate(
            ['id' => $this->categoryId],
            ['name' => $this->name]
        );

        $this->toast('success', 'Categoria salva com sucesso!');
        $this->showModal = false;
        $this->loadCategories();
    }

    public function delete($id)
    {
        DocumentCategory::findOrFail($id)->delete();
        $this->toast('success', 'Categoria excluída com sucesso!');
        $this->loadCategories();
    }

    public function render()
    {
        return view('livewire.config.document-category-manager');
    }
}
