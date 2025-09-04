<?php

namespace App\Livewire\Companies;

use App\Models\Company;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class Index extends Component
{
    use WithPagination, WithFileUploads;

    // List state
    public string $search = '';
    public int $perPage = 10;
    public string $sortField = 'name';
    public string $sortDirection = 'asc';

    // Form state (simplified)
    public ?int $editingId = null;
    public string $name = '';
    public ?string $ruc = null;                // 11 dígitos PE
    public ?string $phone = null;              // celular/teléfono
    public bool $is_active = true;
    public $logo;                              // upload temporal (image)
    // DB stores it in 'logo_path'

    // UI state
    public bool $showFormModal = false;
    public bool $showDeleteModal = false;
    public ?int $deletingId = null;

    protected function rules(): array
    {
        // Nota: unique permite NULL múltiples; ignoramos el id al editar
        return [
            'name'      => ['required', 'string', 'max:255'],
            'ruc'       => [
                'nullable',
                'string',
                'size:11',                       // si quieres solo dígitos: 'regex:/^\d{11}$/'
                Rule::unique('companies', 'ruc')->ignore($this->editingId),
            ],
            'phone'     => ['nullable', 'string', 'max:30'],
            'is_active' => ['boolean'],
            'logo'      => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ];
    }

    /* ---------- List helpers ---------- */
    public function updatingSearch()
    {
        $this->resetPage();
    }
    public function updatingPerPage()
    {
        $this->resetPage();
    }

    public function sortBy(string $field): void
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
    }

    /* ---------- Form actions ---------- */
    public function openCreate(): void
    {
        $this->resetForm();
        $this->showFormModal = true;
    }

    public function openEdit(int $id): void
    {
        $c = Company::findOrFail($id);

        $this->editingId = $c->id;
        $this->name      = (string) $c->name;
        $this->ruc       = $c->ruc;
        $this->phone     = $c->phone;
        $this->is_active = (bool) $c->is_active;
        $this->logo      = null;

        $this->showFormModal = true;
    }

    public function save(): void
    {
        $validated = $this->validate();

        // Handle logo upload
        if ($this->logo) {
            $path = $this->logo->store('logos', 'public'); // storage/app/public/logos
            $validated['logo_path'] = $path;
        }

        if ($this->editingId) {
            Company::findOrFail($this->editingId)->update($validated);
        } else {
            $company = Company::create($validated);
            $this->editingId = $company->id;
        }

        $this->dispatch('toast', type: 'success', message: 'Company saved.');
        $this->showFormModal = false;
        $this->resetForm();
    }

    public function confirmDelete(int $id): void
    {
        $this->deletingId = $id;
        $this->showDeleteModal = true;
    }

    public function delete(): void
    {
        if ($this->deletingId) {
            Company::whereKey($this->deletingId)->delete();
            $this->dispatch('toast', type: 'success', message: 'Company deleted.');
        }
        $this->showDeleteModal = false;
        $this->deletingId = null;
        $this->resetPage();
    }

    public function toggleActive(int $id): void
    {
        $c = Company::findOrFail($id);
        $c->is_active = ! $c->is_active;
        $c->save();
    }

    private function resetForm(): void
    {
        $this->editingId = null;
        $this->name = '';
        $this->ruc = null;
        $this->phone = null;
        $this->is_active = true;
        $this->logo = null;
    }

    public function render()
    {
        $q = Company::query()
            ->when($this->search !== '', function ($w) {
                $term = '%' . $this->search . '%';
                $w->where(function ($qq) use ($term) {
                    // Postgres: ILIKE (insensible a mayúsculas); si usas MySQL cambia a LIKE
                    $qq->where('name', 'ILIKE', $term)
                        ->orWhere('ruc', 'ILIKE', $term)
                        ->orWhere('phone', 'ILIKE', $term);
                });
            })
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate($this->perPage);

        return view('livewire.companies.index', ['rows' => $q]);
    }
}
