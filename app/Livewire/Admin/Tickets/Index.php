<?php

namespace App\Livewire\Admin\Tickets;

use App\Models\Company;
use App\Models\Ticket;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class Index extends Component
{
    use WithPagination;

    public string $tab = 'open';         // open | closed
    public string $search = '';          // título o número
    public int    $perPage = 10;

    public ?int   $companyId = null;     // filtro por compañía
    public string $applicantName = '';   // filtro por nombre del solicitante
    public ?string $closedDate = null;   // YYYY-MM-DD (solo aplica a 'closed')

    public function updatingSearch()
    {
        $this->resetPage();
    }
    public function updatingCompanyId()
    {
        $this->resetPage();
    }
    public function updatingApplicantName()
    {
        $this->resetPage();
    }
    public function updatingClosedDate()
    {
        $this->resetPage();
    }
    public function updatingPerPage()
    {
        $this->resetPage();
    }

    public function setTab(string $t)
    {
        $this->tab = in_array($t, ['open', 'closed']) ? $t : 'open';
        $this->resetPage();
    }

    public function render()
    {
        // Solo Admin / Agent (si quieres, puedes mover esto a un Policy)
        abort_unless(auth()->user()->hasAnyRole(['admin', 'agent']), 403);

        $q = Ticket::query()
            ->with([
                'applicant:id,name,company_id',
                'applicant.company:id,name',
                'module:id,name',
                'category:id,name',
            ])
            // Abiertos / Resueltos
            ->when($this->tab === 'open', fn($w) => $w->where('status', '!=', 'done'))
            ->when($this->tab === 'closed', fn($w) => $w->where('status', 'done'))
            // Búsqueda por número / título
            ->when($this->search !== '', function ($w) {
                $term = '%' . $this->search . '%';
                $w->where(function ($x) use ($term) {
                    $x->where('number', 'like', $term)
                        ->orWhere('title', 'like', $term);
                });
            })
            // Filtro por compañía (relación applicant.company)
            ->when($this->companyId, function ($w) {
                $w->whereHas('applicant', fn($a) => $a->where('company_id', $this->companyId));
            })
            // Filtro por nombre del solicitante
            ->when($this->applicantName !== '', function ($w) {
                $term = '%' . $this->applicantName . '%';
                $w->whereHas('applicant', fn($a) => $a->where('name', 'like', $term));
            })
            // Filtro por día de cierre (usamos last_moved_at cuando status=done)
            ->when($this->closedDate && $this->tab === 'closed', function ($w) {
                $w->whereDate('last_moved_at', $this->closedDate);
            })
            ->orderByDesc('created_at');

        $rows = $q->paginate($this->perPage);

        $companies = Company::orderBy('name')->get(['id', 'name']);

        return view('livewire.admin.tickets.index', compact('rows', 'companies'));
    }
}
