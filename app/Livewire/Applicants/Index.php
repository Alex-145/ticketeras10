<?php

namespace App\Livewire\Applicants;

use App\Models\Applicant;
use App\Models\ApplicantAlias;
use App\Models\Company;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class Index extends Component
{
    use WithPagination;

    // Context menu / Aliases modal
    public bool $showAliasModal = false;
    public ?int $aliasApplicantId = null;
    /** @var array<int, array{id:int|null, alias:string|null}> */
    public array $aliasItems = [];

    // List / sorting / filters
    public string $search = '';
    public int $perPage = 10;
    public string $sortField = 'name';
    public string $sortDirection = 'asc';

    // Form (create/edit)
    public ?int $editingId = null;
    public string $name = '';
    public string $email = '';
    public string $phone_code = '+51';
    public ?string $phone = null;
    public ?int $company_id = null;
    public array $aliases = [];

    // Password controls
    public bool $auto_password = false;     // crear: genera aleatoria si true
    public bool $change_password = false;   // editar: habilita cambio
    public string $password = '';
    public string $password_confirmation = '';

    // UI modals
    public bool $showFormModal = false;
    public bool $showDeleteModal = false;
    public ?int $deletingId = null;

    /* ---------------- Rules dinámicas ---------------- */

    protected function baseRules(): array
    {
        return [
            'name'        => ['required', 'string', 'max:255'],
            'email'       => [
                'required',
                'email',
                'max:255',
                Rule::unique('applicants', 'email')->ignore($this->editingId),
            ],
            'phone_code'  => ['required', 'regex:/^\+\d{1,4}$/'],
            'phone'       => ['nullable', 'string', 'max:30'],
            'company_id'  => ['required', 'integer', 'exists:companies,id'],

            'aliases'     => ['array', 'max:20'],
            'aliases.*'   => ['nullable', 'string', 'max:100', 'distinct'],
        ];
    }

    protected function passwordRulesForSave(): array
    {
        $rules = [];

        // Crear
        if (is_null($this->editingId)) {
            if (!$this->auto_password) {
                $rules['password'] = ['required', 'string', 'min:8', 'confirmed'];
            }
        } else {
            // Editar
            if ($this->change_password) {
                $rules['password'] = ['required', 'string', 'min:8', 'confirmed'];
            }
        }

        return $rules;
    }

    /* --------------- Tabla helpers ------------------- */

    public function updatingSearch(): void
    {
        $this->resetPage();
    }
    public function updatingPerPage(): void
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

    /* ---------------- Acciones ----------------------- */

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
        $this->email      = (string)($a->email ?? '');
        $this->company_id = $a->company_id;

        // Teléfono: prefijo + número
        $this->phone_code = '+51';
        $this->phone      = $a->phone;
        if (is_string($a->phone) && preg_match('/^\s*(\+\d{1,4})\s*(.*)$/', $a->phone, $m)) {
            $this->phone_code = $m[1] ?: '+51';
            $this->phone      = $m[2] ?: null;
        }

        // Aliases
        $this->aliases = $a->aliases->pluck('alias')->all();

        // Password controls (editar)
        $this->change_password = false;
        $this->password = '';
        $this->password_confirmation = '';

        $this->showFormModal = true;
    }

    public function save(): void
    {
        // Validación dinámica
        $rules = array_merge($this->baseRules(), $this->passwordRulesForSave());
        $validated = $this->validate($rules, [], [
            'name' => 'nombre',
            'email' => 'correo',
            'phone_code' => 'código',
            'phone' => 'teléfono',
            'company_id' => 'empresa',
            'password' => 'contraseña',
        ]);

        // Combinar prefijo + número
        $validated['phone'] = trim($this->phone_code) . ' ' . trim((string)($validated['phone'] ?? ''));
        unset($validated['phone_code']);

        // --- Crear/Actualizar Applicant
        if ($this->editingId) {
            $applicant = Applicant::findOrFail($this->editingId);
            $applicant->update($validated);
        } else {
            $applicant = Applicant::create($validated);
            $this->editingId = $applicant->id;
        }

        // --- Crear / Ligar User por email ---
        $user = User::where('email', $this->email)->first();

        if (!$user) {
            $plainPassword = $this->auto_password
                ? Str::password(12)
                : $this->password;

            $user = User::create([
                'name'     => $this->name,
                'email'    => $this->email,
                'password' => Hash::make($plainPassword),
            ]);

            // (Opcional) podrías despachar un evento para mostrar la contraseña generada una sola vez.
            if ($this->auto_password) {
                $this->dispatch('toast', type: 'info', message: 'Se generó una contraseña aleatoria para el usuario.');
            }
        } else {
            // Mantener sincronizado el nombre
            if ($user->name !== $this->name) {
                $user->forceFill(['name' => $this->name])->save();
            }
            // Cambiar contraseña si corresponde (editar)
            if ($this->editingId && $this->change_password && filled($this->password)) {
                $user->forceFill(['password' => Hash::make($this->password)])->save();
            }
        }

        // Asignar rol applicant (idempotente)
        if (!$user->hasRole('applicant')) {
            $user->assignRole('applicant');
        }

        // Asociar applicant->user
        if ($applicant->user_id !== $user->id) {
            $applicant->user()->associate($user);
            $applicant->save();
        }

        // ---- SYNC de aliases (igual que tu lógica) ----
        $incoming = collect($this->aliases)
            ->map(fn($a) => trim((string)$a))
            ->filter(fn($a) => $a !== '')
            ->unique(fn($a) => mb_strtolower($a))
            ->values();

        $applicant->aliases()->whereNotIn('alias', $incoming)->delete();

        foreach ($incoming as $alias) {
            ApplicantAlias::firstOrCreate([
                'applicant_id' => $applicant->id,
                'alias'        => $alias,
            ]);
        }
        // -----------------------------------------------

        $this->dispatch('toast', type: 'success', message: 'Applicant guardado.');
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
            $this->dispatch('toast', type: 'success', message: 'Applicant eliminado.');
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
            $this->aliases = array_values($this->aliases);
        }
    }

    private function resetForm(): void
    {
        $this->editingId  = null;
        $this->name       = '';
        $this->email      = '';
        $this->phone_code = '+51';
        $this->phone      = null;
        $this->company_id = null;
        $this->aliases    = [''];

        // Password controls
        $this->auto_password = false;
        $this->change_password = false;
        $this->password = '';
        $this->password_confirmation = '';
    }

    public function render()
    {
        $rows = Applicant::query()
            ->with('company')
            ->when($this->search !== '', function ($w) {
                $term = '%' . $this->search . '%';
                $w->where('name', 'ILIKE', $term)
                    ->orWhere('email', 'ILIKE', $term)
                    ->orWhere('phone', 'ILIKE', $term)
                    ->orWhereHas('company', fn($q) => $q->where('name', 'ILIKE', $term))
                    ->orWhereHas('aliases', fn($q) => $q->where('alias', 'ILIKE', $term));
            })
            ->when(
                in_array($this->sortField, ['name', 'phone', 'email']),
                fn($q) => $q->orderBy($this->sortField, $this->sortDirection)
            )
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

    /* --------------- Aliases modal ------------------- */

    public function openAliases(int $applicantId): void
    {
        $app = Applicant::with('aliases')->findOrFail($applicantId);

        $this->aliasApplicantId = $app->id;
        $this->aliasItems = $app->aliases
            ->map(fn($a) => ['id' => $a->id, 'alias' => $a->alias])
            ->values()
            ->all();

        if (empty($this->aliasItems)) {
            $this->aliasItems = [['id' => null, 'alias' => null]];
        }

        $this->showAliasModal = true;
    }

    public function aliasAddRow(): void
    {
        $this->aliasItems[] = ['id' => null, 'alias' => null];
    }

    public function aliasRemoveRow(int $i): void
    {
        if (isset($this->aliasItems[$i])) {
            unset($this->aliasItems[$i]);
            $this->aliasItems = array_values($this->aliasItems);
        }
    }

    public function aliasSave(): void
    {
        $this->validate([
            'aliasItems' => ['array', 'max:50'],
            'aliasItems.*.alias' => ['nullable', 'string', 'max:100'],
        ]);

        $app = Applicant::with('aliases')->findOrFail($this->aliasApplicantId);

        $incoming = collect($this->aliasItems)
            ->map(fn($row) => ['id' => $row['id'], 'alias' => trim((string)($row['alias'] ?? ''))])
            ->filter(fn($row) => $row['alias'] !== '')
            ->unique(fn($row) => mb_strtolower($row['alias']))
            ->values();

        $app->aliases()->delete();
        foreach ($incoming as $row) {
            ApplicantAlias::create([
                'applicant_id' => $app->id,
                'alias'        => $row['alias'],
            ]);
        }

        $this->dispatch('toast', type: 'success', message: 'Aliases guardados.');
        $this->showAliasModal = false;
        $this->aliasApplicantId = null;
        $this->aliasItems = [];
    }
}
