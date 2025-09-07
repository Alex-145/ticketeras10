<?php

namespace App\Livewire\Portal\Tickets;

use App\Models\Ticket;
use App\Models\TicketMessage;
use App\Models\TicketMessageAttachment;
use App\Models\Applicant;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;
use Illuminate\Validation\Rule;

#[Layout('layouts.app')]
class Create extends Component
{
    use WithFileUploads;

    public string $title = '';
    public string $description = ''; // mensaje inicial
    public ?int $module_id = null;
    public ?int $category_id = null;
    public ?int $company_id = null;

    /** @var array<int, \Livewire\Features\SupportFileUploads\TemporaryUploadedFile> */
    public array $uploads = [];   // múltiples imágenes

    protected function rules(): array
    {
        return [
            'title'       => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'module_id'   => ['required', 'integer', 'exists:modules,id'],
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'company_id'  => ['nullable', 'integer', 'exists:companies,id'],
            'uploads.*'   => ['image', 'mimes:jpg,jpeg,png,webp,gif', 'max:8192'], // 8MB c/u
        ];
    }

    public function save()
    {
        $this->validate();

        $user = Auth::user();
        $applicant = Applicant::where('user_id', $user->id)->firstOrFail();

        $ticket = Ticket::create([
            'number'      => $this->makeNumber(),
            'title'       => $this->title,
            'description' => $this->description, // también guardamos en cabecera
            'applicant_id' => $applicant->id,
            'company_id'  => $this->company_id,
            'module_id'   => $this->module_id,
            'category_id' => $this->category_id,
            'status'      => 'todo',
        ]);

        // primer mensaje (descripcion)
        $msg = TicketMessage::create([
            'ticket_id'   => $ticket->id,
            'sender_type' => 'applicant',
            'sender_id'   => $applicant->id,
            'body'        => $this->description,
        ]);

        // Adjuntos
        foreach ($this->uploads as $file) {
            $path = $file->store('tickets/' . $ticket->id, 'public');
            [$w, $h] = $this->imageSizeSafe($file->getRealPath());

            TicketMessageAttachment::create([
                'message_id'    => $msg->id,
                'path'          => $path,
                'original_name' => $file->getClientOriginalName(),
                'mime'          => $file->getMimeType(),
                'size'          => $file->getSize(),
                'width'         => $w,
                'height' => $h,
            ]);
        }

        // redirige al chat del ticket
        return redirect()->route('portal.tickets.show', $ticket);
    }

    private function makeNumber(): string
    {
        $seq = str_pad((string)(now()->format('His') . random_int(10, 99)), 6, '0', STR_PAD_LEFT);
        return 'TCK-' . now()->format('Ymd') . '-' . $seq;
    }

    private function imageSizeSafe(string $path): array
    {
        try {
            [$w, $h] = getimagesize($path) ?: [null, null];
            return [(int)($w ?? 0), (int)($h ?? 0)];
        } catch (\Throwable $e) {
            return [null, null];
        }
    }

    public function render()
    {
        return view('livewire.portal.tickets.create');
    }
}
