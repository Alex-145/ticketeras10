<?php

namespace App\Livewire\Portal\Tickets;

use App\Models\Applicant;
use App\Models\Ticket;
use App\Models\TicketMessage;
use App\Models\TicketMessageAttachment;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('layouts.app')]
class Create extends Component
{
    use WithFileUploads;

    public string $title = '';
    public string $description = '';
    public string $priority = 'normal';           // low|normal|high|urgent
    public string $kind = 'consulta';             // error|consulta|capacitacion
    /** @var array<int, \Livewire\Features\SupportFileUploads\TemporaryUploadedFile> */
    public array $uploads = [];

    protected function rules(): array
    {
        return [
            'title'       => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:5000'],
            'priority'    => ['required', 'in:low,normal,high,urgent'],
            'kind'        => ['required', 'in:error,consulta,capacitacion'],
            'uploads.*'   => ['image', 'mimes:jpg,jpeg,png,webp,gif', 'max:8192'],
        ];
    }

    public function save()
    {
        $this->validate();

        $user = Auth::user();
        $applicant = Applicant::where('user_id', $user->id)->firstOrFail();

        $ticket = Ticket::create([
            'number'       => $this->makeNumber(),
            'title'        => $this->title,
            'description'  => $this->description,
            'applicant_id' => $applicant->id,
            // company_id, module_id, category_id — los llenará el agente
            'status'       => 'todo',
            'priority'     => $this->priority,
            'kind'         => $this->kind,
        ]);

        $msg = TicketMessage::create([
            'ticket_id'   => $ticket->id,
            'sender_type' => 'applicant',
            'sender_id'   => $applicant->id,
            'body'        => $this->description,
        ]);

        foreach ($this->uploads as $file) {
            $path = $file->store('tickets/' . $ticket->id, 'public');
            TicketMessageAttachment::create([
                'message_id'    => $msg->id,
                'path'          => $path,
                'original_name' => $file->getClientOriginalName(),
                'mime'          => $file->getMimeType(),
                'size'          => $file->getSize(),
            ]);
        }

        return redirect()->route('portal.tickets.show', $ticket);
    }

    private function makeNumber(): string
    {
        return 'TCK-' . now()->format('Ymd') . '-' . now()->format('His') . random_int(10, 99);
    }

    public function render()
    {
        return view('livewire.portal.tickets.create');
    }
}
