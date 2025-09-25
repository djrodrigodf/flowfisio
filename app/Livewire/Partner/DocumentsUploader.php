<?php

namespace App\Livewire\Partner;

use App\Models\DocumentCategory;
use App\Models\Partner;
use Livewire\Component;
use Livewire\WithFileUploads;
use Mary\Traits\Toast;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class DocumentsUploader extends Component
{
    use Toast, WithFileUploads;

    public Partner $partner;

    public ?int $document_category_id = null;

    public ?string $description = null;

    public $file;

    public ?int $filterCategory = null;

    public array $headers = [
        ['key' => 'name', 'label' => 'Nome'],
        ['key' => 'category', 'label' => 'Categoria'],
        ['key' => 'description', 'label' => 'Descrição'],
        ['key' => 'actions', 'label' => '', 'class' => 'w-24 text-right'],
    ];

    public function rules(): array
    {
        return [
            'document_category_id' => 'required|exists:document_categories,id',
            'file' => 'required|file|max:10240', // 10MB
            'description' => 'nullable|string|max:255',
        ];
    }

    public function getFilteredDocumentsProperty()
    {
        return $this->partner->getMedia('documents')
            ->filter(function ($media) {
                return $this->filterCategory
                    ? $media->getCustomProperty('document_category_id') == $this->filterCategory
                    : true;
            });
    }

    public function sendFile()
    {

        $this->validate();

        $category = DocumentCategory::find($this->document_category_id);

        $this->partner
            ->addMedia($this->file->getRealPath())
            ->usingFileName($this->file->getClientOriginalName())
            ->usingName($this->file->getClientOriginalName())
            ->withCustomProperties([
                'document_category_id' => $this->document_category_id,
                'document_name' => $category->name,
                'description' => $this->description,
            ])
            ->toMediaCollection('documents');

        $this->reset('document_category_id', 'description', 'file');
        $this->toast('success', 'Documento enviado com sucesso!');
    }

    public function removeDocument($mediaId)
    {
        Media::findOrFail($mediaId)->delete();
        $this->toast('success', 'Documento removido com sucesso.');
    }

    public function render()
    {
        return view('livewire.partner.documents-uploader', [
            'categories' => DocumentCategory::orderBy('name')->get(),
            'documents' => $this->filteredDocuments,
        ]);
    }
}
