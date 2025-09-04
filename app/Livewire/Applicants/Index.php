<?php

namespace App\Livewire\Applicants;

use App\Models\Applicant;
use App\Models\ApplicantAlias;
use App\Models\Company;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class Index extends Component
{
    use WithPagination;

    // --- NUEVAS props (encima o debajo de las existentes) ---
    public bool $showAliasModal = false;
    public ?int $aliasApplicantId = null;
    /** @var array<int, array{id:int|null, alias:string|null}> */
    public array $aliasItems = []; // filas de alias (id + alias)
    // List
    public string $search = '';
    public int $perPage = 10;
    public string $sortField = 'name';
    public string $sortDirection = 'asc';

    // Form
    public ?int $editingId = null;
    public string $name = '';
    public string $phone_code = '+51';   // prefijo editable
    public ?string $phone = null;        // número sin prefijo
    public ?int $company_id = null;
    public array $aliases = [];          // NUEVO: lista de seudónimos

    // UI
    public bool $showFormModal = false;
    public bool $showDeleteModal = false;
    public ?int $deletingId = null;

    protected function rules(): array
    {
        return [
            'name'        => ['required', 'string', 'max:255'],
            'phone_code'  => ['required', 'regex:/^\+\d{1,4}$/'],
            'phone'       => ['nullable', 'string', 'max:30'],
            'company_id'  => ['required', 'integer', 'exists:companies,id'],

            // Aliases
            'aliases'     => ['array', 'max:20'], // límite sano
            'aliases.*'   => ['nullable', 'string', 'max:100', 'distinct'], // distinct en request
        ];
    }

    /* ----- tabla helpers ----- */
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

    /* ----- acciones ----- */
    public function openCreate(): void
    {
        $this->resetForm();
        $this->showFormModal = true;
    }

    public function openEdit(int $id): void
    {
        $a = Applicant::with('aliases')->findOrFail($id);

        $this->editingId  = $a->id;
        $this->name       = $a->name;
        $this->company_id = $a->company_id;

        // Split teléfono a prefijo + número
        $this->phone_code = '+51';
        $this->phone      = $a->phone;
        if (is_string($a->phone) && preg_match('/^\s*(\+\d{1,4})\s*(.*)$/', $a->phone, $m)) {
            $this->phone_code = $m[1] ?: '+51';
            $this->phone      = $m[2] ?: null;
        }

        // Cargar alias a array simple
        $this->aliases = $a->aliases->pluck('alias')->all();

        $this->showFormModal = true;
    }

    public function save(): void
    {
        $validated = $this->validate();

        // Combinar prefijo + número para DB
        $validated['phone'] = trim($this->phone_code) . ' ' . trim((string)($validated['phone'] ?? ''));
        unset($validated['phone_code']);

        if ($this->editingId) {
            $applicant = Applicant::findOrFail($this->editingId);
            $applicant->update($validated);
        } else {
            $applicant = Applicant::create($validated);
            $this->editingId = $applicant->id;
        }

        // ---- SYNC de alias ----
        // Normalizar: trim, remover vacíos, único (case-insensitive)
        $incoming = collect($this->aliases)
            ->map(fn($a) => trim((string)$a))
            ->filter(fn($a) => $a !== '')
            ->unique(function ($a) {
                return mb_strtolower($a);
            })
            ->values();

        // Borrar los que ya no están
        $applicant->aliases()->whereNotIn('alias', $incoming)->delete();

        // Crear los nuevos (idempotente gracias a unique en DB)
        foreach ($incoming as $alias) {
            ApplicantAlias::firstOrCreate([
                'applicant_id' => $applicant->id,
                'alias'        => $alias,
            ]);
        }
        // ------------------------

        $this->dispatch('toast', type: 'success', message: 'Applicant saved.');
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
            Applicant::whereKey($this->deletingId)->delete();
            $this->dispatch('toast', type: 'success', message: 'Applicant deleted.');
        }
        $this->showDeleteModal = false;
        $this->deletingId = null;
        $this->resetPage();
    }

    // Repeater helpers
    public function addAliasRow(): void
    {
        $this->aliases[] = '';
    }
    public function removeAliasRow(int $i): void
    {
        if (isset($this->aliases[$i])) {
            unset($this->aliases[$i]);
            $this->aliases = array_values($this->aliases); // reindex
        }
    }

    private function resetForm(): void
    {
        $this->editingId  = null;
        $this->name       = '';
        $this->phone_code = '+51';
        $this->phone      = null;
        $this->company_id = null;
        $this->aliases    = ['']; // empieza con una fila
    }

    public function render()
    {
        $rows = Applicant::query()
            ->with('company')
            ->when($this->search !== '', function ($w) {
                $term = '%' . $this->search . '%';
                // Cambia ILIKE->LIKE si usas MySQL
                $w->where('name', 'ILIKE', $term)
                    ->orWhere('phone', 'ILIKE', $term)
                    ->orWhereHas('company', fn($q) => $q->where('name', 'ILIKE', $term))
                    ->orWhereHas('aliases', fn($q) => $q->where('alias', 'ILIKE', $term));
            })
            ->when(in_array($this->sortField, ['name', 'phone']), fn($q) => $q->orderBy($this->sortField, $this->sortDirection))
            ->when(
                $this->sortField === 'company',
                fn($q) =>
                $q->join('companies', 'companies.id', '=', 'applicants.company_id')
                    ->orderBy('companies.name', $this->sortDirection)
                    ->select('applicants.*')
            )
            ->paginate($this->perPage);

        $companies = Company::orderBy('name')->pluck('name', 'id');

        return view('livewire.applicants.index', compact('rows', 'companies'));
    }
    // --- ABRE modal y carga alias ---
    public function openAliases(int $applicantId): void
    {
        $app = \App\Models\Applicant::with('aliases')->findOrFail($applicantId);

        $this->aliasApplicantId = $app->id;
        // Cargar alias a array de edición (id + alias)
        $this->aliasItems = $app->aliases
            ->map(fn($a) => ['id' => $a->id, 'alias' => $a->alias])
            ->values()
            ->all();

        if (empty($this->aliasItems)) {
            $this->aliasItems = [['id' => null, 'alias' => null]];
        }

        $this->showAliasModal = true;
    }

    // --- Agregar fila de alias ---
    public function aliasAddRow(): void
    {
        $this->aliasItems[] = ['id' => null, 'alias' => null];
    }

    // --- Quitar fila de alias por índice ---
    public function aliasRemoveRow(int $i): void
    {
        if (isset($this->aliasItems[$i])) {
            unset($this->aliasItems[$i]);
            $this->aliasItems = array_values($this->aliasItems);
        }
    }

    // --- Guardar cambios de alias ---
    public function aliasSave(): void
    {
        $this->validate([
            'aliasItems' => ['array', 'max:50'],
            'aliasItems.*.alias' => ['nullable', 'string', 'max:100'],
        ]);

        $app = \App\Models\Applicant::with('aliases')->findOrFail($this->aliasApplicantId);

        // Normalizar: quitar vacíos, trim, únicos por case-insensitive
        $incoming = collect($this->aliasItems)
            ->map(fn($row) => ['id' => $row['id'], 'alias' => trim((string)($row['alias'] ?? ''))])
            ->filter(fn($row) => $row['alias'] !== '')
            ->unique(fn($row) => mb_strtolower($row['alias']))
            ->values();

        // Eliminar todos y recrear (simple y seguro) — también puedes hacer diff si prefieres
        $app->aliases()->delete();
        foreach ($incoming as $row) {
            \App\Models\ApplicantAlias::create([
                'applicant_id' => $app->id,
                'alias'        => $row['alias'],
            ]);
        }

        $this->dispatch('toast', type: 'success', message: 'Aliases saved.');
        $this->showAliasModal = false;
        $this->aliasApplicantId = null;
        $this->aliasItems = [];
    }
}
