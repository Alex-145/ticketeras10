<?php

namespace App\Http\Livewire;

use Livewire\Component;
use App\Models\Ticket;

class Tickets extends Component
{
    public $tickets;

    public function mount()
    {
        $this->tickets = Ticket::with('company')->get();
    }

    public function edit($id)
    {
        session()->flash('message', "Editar ticket #$id");
    }

    public function delete($id)
    {
        Ticket::find($id)?->delete();
        $this->tickets = Ticket::with('company')->get();
        session()->flash('message', "Ticket eliminado");
    }

    public function render()
    {
        return view('livewire.tickets');
    }
}
