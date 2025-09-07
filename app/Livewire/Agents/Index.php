<?php

namespace App\Livewire\Agents;

use App\Models\Agent;
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

    // Listado
    public string $search = '';
    public int $perPage = 10;
    public string $sortField = 'name';
    public string $sortDirection = 'asc';

    // Form
    public ?int $editingId = null;
    public string $name = '';
    public string $email = '';
    public string $phone_code = '+51';
    public ?string $phone = null;

    // Password
    public bool $auto_password = false;     // crear => genera aleatoria
    public bool $change_password = false;   // editar => cambiar manual
    public string $password = '';
    public string $password_confirmation = '';

    // UI
    public bool $showFormModal = false;
    public bool $showDeleteModal = false;
    public ?int $deletingId = null;

    protected function baseRules(): array
    {
        return [
            'name'  => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('agents', 'email')->ignore($this->editingId),
            ],
            'phone_code' => ['required', 'regex:/^\+\d{1,4}$/'],
            'phone'      => ['nullable', 'string', 'max:30'],
        ];
    }

    protected function passwordRules(): array
    {
        $rules = [];
        if (is_null($this->editingId)) {
            if (!$this->auto_password) {
                $rules['password'] = ['required', 'string', 'min:8', 'confirmed'];
            }
        } else {
            if ($this->change_password) {
                $rules['password'] = ['required', 'string', 'min:8', 'confirmed'];
            }
        }
        return $rules;
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
        $a = Agent::findOrFail($id);
        $this->editingId = $a->id;
        $this->name      = $a->name;
        $this->email     = (string)$a->email;

        $this->phone_code = '+51';
        $this->phone      = $a->phone;
        if (is_string($a->phone) && preg_match('/^\s*(\+\d{1,4})\s*(.*)$/', $a->phone, $m)) {
            $this->phone_code = $m[1] ?: '+51';
            $this->phone      = $m[2] ?: null;
        }

        $this->change_password = false;
        $this->password = '';
        $this->password_confirmation = '';

        $this->showFormModal = true;
    }

    public function save(): void
    {
        $rules = array_merge($this->baseRules(), $this->passwordRules());
        $validated = $this->validate($rules, [], [
            'name' => 'nombre',
            'email' => 'correo',
            'phone_code' => 'código',
            'phone' => 'teléfono',
            'password' => 'contraseña',
        ]);

        // Combina teléfono
        $validated['phone'] = trim($this->phone_code) . ' ' . trim((string)($validated['phone'] ?? ''));
        unset($validated['phone_code']);

        // Crear/Actualizar Agent
        if ($this->editingId) {
            $agent = Agent::findOrFail($this->editingId);
            $agent->update($validated);
        } else {
            $agent = Agent::create($validated);
            $this->editingId = $agent->id;
        }

        // Crear/Ligar User por email
        $user = User::where('email', $this->email)->first();

        if (!$user) {
            $plainPassword = $this->auto_password ? Str::password(12) : $this->password;
            $user = User::create([
                'name'     => $this->name,
                'email'    => $this->email,
                'password' => Hash::make($plainPassword),
            ]);
            if ($this->auto_password) {
                $this->dispatch('toast', type: 'info', message: 'Se generó una contraseña aleatoria para el agente.');
            }
        } else {
            if ($user->name !== $this->name) {
                $user->forceFill(['name' => $this->name])->save();
            }
            if ($this->editingId && $this->change_password && filled($this->password)) {
                $user->forceFill(['password' => Hash::make($this->password)])->save();
            }
        }

        // Asigna rol agent (idempotente)
        if (!$user->hasRole('agent')) {
            $user->assignRole('agent');
        }

        // Liga agent->user
        if ($agent->user_id !== $user->id) {
            $agent->user()->associate($user);
            $agent->save();
        }

        $this->dispatch('toast', type: 'success', message: 'Agent guardado.');
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
            Agent::whereKey($this->deletingId)->delete();
            $this->dispatch('toast', type: 'success', message: 'Agent eliminado.');
        }
        $this->showDeleteModal = false;
        $this->deletingId = null;
        $this->resetPage();
    }

    private function resetForm(): void
    {
        $this->editingId = null;
        $this->name = '';
        $this->email = '';
        $this->phone_code = '+51';
        $this->phone = null;

        $this->auto_password = false;
        $this->change_password = false;
        $this->password = '';
        $this->password_confirmation = '';
    }

    public function render()
    {
        $rows = Agent::query()
            ->when($this->search !== '', function ($w) {
                $term = '%' . $this->search . '%';
                // ILIKE para PostgreSQL; usa LIKE en MySQL
                $w->where('name', 'ILIKE', $term)
                    ->orWhere('email', 'ILIKE', $term)
                    ->orWhere('phone', 'ILIKE', $term);
            })
            ->when(
                in_array($this->sortField, ['name', 'email', 'phone']),
                fn($q) => $q->orderBy($this->sortField, $this->sortDirection)
            )
            ->paginate($this->perPage);

        return view('livewire.agents.index', compact('rows'));
    }
}
