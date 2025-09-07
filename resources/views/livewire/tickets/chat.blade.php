<div x-data="chatPaste({{ $ticket->id }})" x-init="boot">
    <div class="card">
        <div class="card-header bg-secondary text-white d-flex justify-content-between">
            <div>
                <strong>#{{ $ticket->number }}</strong> — {{ $ticket->title }}
                <span class="badge badge-light ml-2">{{ strtoupper($ticket->status) }}</span>
            </div>
        </div>

        <div class="card-body" style="height: 58vh; overflow:auto;" id="chat-scroll">
            @forelse ($messages as $m)
                @php $mine = $m->sender_type === 'applicant' ? optional(\App\Models\Applicant::where('user_id', auth()->id())->first())->id === $m->sender_id : auth()->id() === $m->sender_id; @endphp

                <div class="d-flex mb-3 {{ $mine ? 'justify-content-end' : 'justify-content-start' }}">
                    <div class="p-2 rounded {{ $mine ? 'bg-primary text-white' : 'bg-light' }}" style="max-width:70%;">
                        @if ($m->body)
                            <div class="mb-1" style="white-space: pre-wrap">{{ $m->body }}</div>
                        @endif
                        @if ($m->attachments->count())
                            <div class="d-flex flex-wrap">
                                @foreach ($m->attachments as $att)
                                    <a href="{{ asset('storage/' . $att->path) }}" target="_blank" class="mr-2 mb-2">
                                        <img src="{{ asset('storage/' . $att->path) }}"
                                            style="height:110px;border-radius:8px;">
                                    </a>
                                @endforeach
                            </div>
                        @endif
                        <div class="small text-muted mt-1">{{ $m->created_at->format('d/m H:i') }}</div>
                    </div>
                </div>
            @empty
                <div class="text-center text-muted">No messages yet.</div>
            @endforelse
        </div>

        <div class="card-footer">
            <form wire:submit.prevent="send" x-data="{ showDrop: false }">
                <div class="form-group mb-2">
                    <textarea x-ref="msg" class="form-control" rows="2"
                        placeholder="Escribe un mensaje... (pega imágenes con Ctrl+V)" wire:model.defer="message"></textarea>
                </div>
                <div class="d-flex align-items-center flex-wrap mb-2">
                    <input type="file" multiple accept="image/*" wire:model="uploads" class="mr-2">
                    <div class="d-flex flex-wrap">
                        @foreach ($uploads as $i => $img)
                            <img src="{{ $img->temporaryUrl() }}" class="mr-2 mb-2"
                                style="height:60px;border-radius:8px;">
                        @endforeach
                    </div>
                </div>
                <div class="text-right">
                    <button class="btn btn-primary" type="submit" wire:loading.attr="disabled">
                        <span wire:loading wire:target="send" class="spinner-border spinner-border-sm mr-1"></span>
                        Enviar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>


@push('js')
    @vite('resources/js/app.js')

    <script>
        function chatPaste(ticketId) {
            return {
                boot() {
                    console.log("[Chat] Boot ticket:", ticketId);

                    document.addEventListener('paste', (e) => {
                        if (!e.clipboardData?.files?.length) return;
                        const imgs = Array.from(e.clipboardData.files).filter(f => f.type.startsWith('image/'));
                        if (imgs.length) {
                            console.log("[Chat] paste images:", imgs.map(f => `${f.name}(${f.type},${f.size})`));
                            @this.uploadMultiple('uploads', imgs, () => {}, (err) => console.error(
                                "[Chat] uploadMultiple error:", err));
                        }
                    });

                    if (window.Echo) {
                        const ch = `tickets.${ticketId}`;
                        console.log("[Chat] subscribing", ch);

                        window.Echo.private(ch)
                            .subscribed(() => {
                                console.log(`[Chat] subscribed ${ch}. socket_id:`, window.Echo.socketId?.());
                                setTimeout(() => {
                                    const sc = document.getElementById('chat-scroll');
                                    sc && (sc.scrollTop = sc.scrollHeight);
                                }, 60);
                            })
                            .error((e) => console.error(`[Chat] channel error ${ch}`, e))
                            .listen('.TicketMessageCreated', (e) => {
                                console.log(`[Chat] .TicketMessageCreated ${ch}`, e);
                                @this.messageArrived(e);
                                setTimeout(() => {
                                    const sc = document.getElementById('chat-scroll');
                                    sc && (sc.scrollTop = sc.scrollHeight);
                                }, 50);
                            });
                    } else {
                        console.error("[Chat] window.Echo is undefined");
                    }
                }
            }
        }
    </script>
@endpush
