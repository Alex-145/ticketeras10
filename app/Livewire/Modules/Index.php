<?php

namespace App\Livewire\Modules;

use App\Models\Module;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class Index extends Component
{
    use WithPagination;

    // List state
    public string $search = '';
    public int $perPage = 10;
    public string $sortField = 'name';
    public string $sortDirection = 'asc';

    // Form state
    public ?int $editingId = null;
    public string $name = '';

    // UI state
    public bool $showFormModal = false;
    public bool $showDeleteModal = false;
    public ?int $deletingId = null;

    protected function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('modules', 'name')->ignore($this->editingId),
            ],
        ];
    }

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

    public function openCreate(): void
    {
        $this->resetForm();
        $this->showFormModal = true;
    }

    public function openEdit(int $id): void
    {
        $m = Module::findOrFail($id);
        $this->editingId = $m->id;
        $this->name = $m->name;
        $this->showFormModal = true;
    }

    public function save(): void
    {
        $validated = $this->validate();

        if ($this->editingId) {
            Module::findOrFail($this->editingId)->update($validated);
        } else {
            $mod = Module::create($validated);
            $this->editingId = $mod->id;
        }

        $this->dispatch('toast', type: 'success', message: 'Module saved.');
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
            Module::whereKey($this->deletingId)->delete();
            $this->dispatch('toast', type: 'success', message: 'Module deleted.');
        }
        $this->showDeleteModal = false;
        $this->deletingId = null;
        $this->resetPage();
    }

    private function resetForm(): void
    {
        $this->editingId = null;
        $this->name = '';
    }

    public function render()
    {
        $rows = Module::query()
            ->when($this->search !== '', function ($w) {
                $term = '%' . $this->search . '%';
                // Postgres: ILIKE; si usas MySQL, cambia por LIKE
                $w->where('name', 'ILIKE', $term);
            })
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate($this->perPage);

        return view('livewire.modules.index', ['rows' => $rows]);
    }
}
