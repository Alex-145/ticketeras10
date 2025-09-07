@php
    // Mapear nombres de staff en 1 sola consulta
    $staffNames = \App\Models\User::whereIn(
        'id',
        collect($messages)->where('sender_type', 'staff')->pluck('sender_id')->unique(),
    )->pluck('name', 'id');
    $applicantName = optional($ticket->applicant)->name;
@endphp

<div x-data="chatUI({{ $ticket->id }}, {{ count($messages) }})" x-init="boot">
    <div class="row align-items-stretch">
        {{-- IZQUIERDA: CHAT --}}
        <div class="col-lg-8 col-xl-9 d-flex">
            <div class="card d-flex flex-column w-100 position-relative">
                <div class="card-header bg-secondary text-white d-flex justify-content-between align-items-center">
                    <div class="mr-2">
                        <div class="font-weight-bold">
                            #{{ $ticket->number }} — {{ $ticket->title }}
                        </div>
                        <div class="small mt-1">
                            <span class="badge badge-light text-dark">Estado: {{ strtoupper($ticket->status) }}</span>
                            <span class="badge badge-info ml-1">Prioridad: {{ strtoupper($ticket->priority) }}</span>
                            <span class="badge badge-primary ml-1">Tipo: {{ strtoupper($ticket->kind) }}</span>
                            <span class="badge badge-dark ml-1">
                                Compañía: {{ optional($ticket->applicant?->company)->name ?? '—' }}
                            </span>
                        </div>
                    </div>
                </div>

                {{-- MENSAJES --}}
                <div class="card-body flex-grow-1 overflow-auto" id="chat-scroll" @scroll.debounce.50ms="onScroll">
                    @forelse ($messages as $m)
                        @php
                            $isApplicant = $m->sender_type === 'applicant';
                            $mine = $isApplicant
                                ? optional(\App\Models\Applicant::where('user_id', auth()->id())->first())->id ===
                                    $m->sender_id
                                : auth()->id() === $m->sender_id;
                            $author = $isApplicant
                                ? $applicantName ?? 'Solicitante'
                                : $staffNames[$m->sender_id] ?? 'Staff';
                        @endphp

                        <div class="d-flex mb-3 {{ $mine ? 'justify-content-end' : 'justify-content-start' }}">
                            <div class="p-2 rounded {{ $mine ? 'bg-primary text-white' : 'bg-light' }}"
                                style="max-width:70%;">
                                {{-- Línea de cabecera: #mensaje • autor • hora --}}
                                <div class="small {{ $mine ? 'text-white-75' : 'text-muted' }}">
                                    <span
                                        class="badge {{ $mine ? 'badge-light' : 'badge-secondary' }}">#{{ $loop->iteration }}</span>
                                    <strong class="ml-1">{{ $author }}</strong>
                                    · {{ $m->created_at->format('d/m/Y H:i') }}
                                </div>

                                @if ($m->body)
                                    <div class="mb-1 mt-1" style="white-space: pre-wrap">{{ $m->body }}</div>
                                @endif

                                @if ($m->attachments->count())
                                    <div class="d-flex flex-wrap mt-1">
                                        @foreach ($m->attachments as $att)
                                            <a href="{{ asset('storage/' . $att->path) }}" target="_blank"
                                                class="mr-2 mb-2">
                                                <img src="{{ asset('storage/' . $att->path) }}"
                                                    style="height:110px;border-radius:8px;">
                                            </a>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        </div>
                    @empty
                        <div class="text-center text-muted">Sin mensajes.</div>
                    @endforelse
                </div>

                {{-- COMPOSER --}}
                <div class="card-footer">
                    <form wire:submit.prevent="send">
                        <div class="form-group mb-2">
                            <textarea x-ref="msg" class="form-control" rows="2"
                                placeholder="Escribe un mensaje... (pega imágenes con Ctrl+V)" wire:model.defer="message"></textarea>
                        </div>

                        <div class="d-flex align-items-center flex-wrap mb-2">
                            <input type="file" multiple accept="image/*" wire:model="uploads" class="mr-2">
                            <div class="d-flex flex-wrap">
                                @foreach ($uploads as $img)
                                    <img src="{{ $img->temporaryUrl() }}" class="mr-2 mb-2"
                                        style="height:60px;border-radius:8px;">
                                @endforeach
                            </div>
                        </div>

                        <div class="text-right">
                            <button class="btn btn-primary" type="submit" wire:loading.attr="disabled">
                                <span wire:loading wire:target="send"
                                    class="spinner-border spinner-border-sm mr-1"></span>
                                Enviar
                            </button>
                        </div>
                    </form>
                </div>

                {{-- BOTÓN FLOTANTE: ir al final --}}
                <button x-show="showJump" x-transition @click="scrollBottom(true)"
                    class="btn btn-sm btn-primary shadow position-absolute"
                    style="right: 16px; bottom: 82px; border-radius: 20px;">
                    <i class="fas fa-arrow-down"></i>
                </button>
            </div>
        </div>

        {{-- DERECHA: CARDS --}}
        <div class="col-lg-4 col-xl-3 d-flex flex-column">
            {{-- Solicitante --}}
            <div class="card mb-3">
                <div class="card-header bg-light">
                    <i class="fas fa-user mr-1"></i> Solicitante
                </div>
                <div class="card-body">
                    @php $app = $ticket->applicant; @endphp
                    <div class="mb-2">
                        <div class="text-muted small">Nombre</div>
                        <div class="font-weight-bold">{{ $app?->name ?? '—' }}</div>
                    </div>
                    <div class="mb-2">
                        <div class="text-muted small">Email</div>
                        <div class="font-weight-bold">{{ $app?->email ?? '—' }}</div>
                    </div>
                    <div class="mb-2">
                        <div class="text-muted small">Teléfono</div>
                        <div class="font-weight-bold">{{ $app?->phone ?? '—' }}</div>
                    </div>
                    <div class="mb-0">
                        <div class="text-muted small">Compañía</div>
                        <div class="font-weight-bold">{{ optional($app?->company)->name ?? '—' }}</div>
                    </div>
                </div>
            </div>

            {{-- Propiedades --}}
            <div class="card">
                <div class="card-header bg-light">
                    <i class="fas fa-info-circle mr-1"></i> Propiedades del ticket
                </div>
                <div class="card-body">
                    <div class="mb-2">
                        <div class="text-muted small">Estado</div>
                        <span class="badge badge-secondary">{{ strtoupper($ticket->status) }}</span>
                    </div>

                    <div class="mb-2">
                        <div class="text-muted small">Prioridad</div>
                        <span class="badge badge-info">{{ strtoupper($ticket->priority) }}</span>
                    </div>

                    <div class="mb-2">
                        <div class="text-muted small">Tipo</div>
                        <span class="badge badge-primary">{{ strtoupper($ticket->kind) }}</span>
                    </div>

                    <hr>

                    <div class="mb-2">
                        <div class="text-muted small">Creado</div>
                        <div class="font-weight-bold">{{ $ticket->created_at->format('d/m/Y H:i') }}</div>
                    </div>
                    <div class="mb-2">
                        <div class="text-muted small">1ª respuesta</div>
                        <div class="font-weight-bold">
                            @if ($firstResponseAt)
                                {{ \Illuminate\Support\Carbon::parse($firstResponseAt)->format('d/m/Y H:i') }}
                            @else
                                <span class="text-warning">Pendiente</span>
                            @endif
                        </div>
                    </div>
                    <div class="mb-0">
                        <div class="text-muted small">Tiempo transcurrido</div>
                        <div class="font-weight-bold">{{ $elapsed }}</div>
                    </div>

                    <hr>

                    {{-- Clasificación (solo staff) --}}
                    @if (auth()->user()->hasAnyRole(['admin', 'agent']))
                        <form wire:submit.prevent="saveMeta">
                            <div class="form-group">
                                <label class="small mb-1">Módulo</label>
                                <select class="form-control form-control-sm" wire:model="module_id">
                                    <option value="">— seleccionar —</option>
                                    @foreach (\App\Models\Module::orderBy('name')->get() as $m)
                                        <option value="{{ $m->id }}">{{ $m->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="form-group">
                                <label class="small mb-1">Categoría</label>
                                <select class="form-control form-control-sm" wire:model="category_id">
                                    <option value="">— seleccionar —</option>
                                    @foreach (\App\Models\Category::orderBy('name')->get() as $c)
                                        <option value="{{ $c->id }}">{{ $c->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="form-row">
                                <div class="form-group col-6">
                                    <label class="small mb-1">Prioridad</label>
                                    <select class="form-control form-control-sm" wire:model="priority">
                                        <option value="low">Baja</option>
                                        <option value="normal">Normal</option>
                                        <option value="high">Alta</option>
                                        <option value="urgent">Urgente</option>
                                    </select>
                                </div>
                                <div class="form-group col-6">
                                    <label class="small mb-1">Tipo</label>
                                    <select class="form-control form-control-sm" wire:model="kind">
                                        <option value="error">Error</option>
                                        <option value="consulta">Consulta</option>
                                        <option value="capacitacion">Capacitación</option>
                                    </select>
                                </div>
                            </div>

                            <div class="d-flex justify-content-between">
                                <button class="btn btn-sm btn-outline-primary" type="submit">
                                    <i class="fas fa-save mr-1"></i> Guardar
                                </button>
                                <button class="btn btn-sm btn-success" type="button" wire:click="resolve">
                                    <i class="fas fa-check mr-1"></i> Resolver
                                </button>
                            </div>
                        </form>
                    @else
                        {{-- Lectura para applicant --}}
                        <div class="mb-2">
                            <div class="text-muted small">Módulo</div>
                            <div class="font-weight-bold">{{ optional($ticket->module)->name ?? '—' }}</div>
                        </div>
                        <div class="mb-0">
                            <div class="text-muted small">Categoría</div>
                            <div class="font-weight-bold">{{ optional($ticket->category)->name ?? '—' }}</div>
                        </div>
                    @endif
                </div>
            </div>

        </div>
    </div>
</div>

@push('js')
    @vite('resources/js/app.js')

    <script>
        function chatUI(ticketId, initialCount) {
            return {
                showJump: false,
                wasAtBottom: true,
                boot() {
                    // Pegar imágenes desde portapapeles
                    document.addEventListener('paste', (e) => {
                        const imgs = Array.from(e.clipboardData?.files || []).filter(f => f.type?.startsWith(
                            'image/'));
                        if (imgs.length) {
                            @this.uploadMultiple('uploads', imgs, () => {}, (err) => console.error(err));
                        }
                    });

                    // Subscribirse realtime
                    if (window.Echo) {
                        const ch = `tickets.${ticketId}`;
                        window.Echo.private(ch)
                            .subscribed(() => this.scrollBottom(true))
                            .error((e) => console.error(`[Chat] channel error ${ch}`, e))
                            .listen('.TicketMessageCreated', (e) => {
                                // Si estaba en el fondo antes de llegar el mensaje, bajamos
                                const shouldAuto = this.isAtBottom();
                                @this.messageArrived(e);
                                // Espera el re-render de Livewire y scrollea si procede
                                setTimeout(() => {
                                    shouldAuto ? this.scrollBottom(true) : this.showJump = true;
                                }, 80);
                            });
                    }
                    // Autoscroll inicial
                    setTimeout(() => this.scrollBottom(true), 50);
                },
                onScroll() {
                    // Mostrar u ocultar el botón jump según posición
                    this.showJump = !this.isAtBottom();
                },
                scrollBottom(smooth = false) {
                    const sc = document.getElementById('chat-scroll');
                    if (!sc) return;
                    if (smooth) {
                        sc.scrollTo({
                            top: sc.scrollHeight,
                            behavior: 'smooth'
                        });
                    } else {
                        sc.scrollTop = sc.scrollHeight;
                    }
                    this.showJump = false;
                },
                isAtBottom() {
                    const sc = document.getElementById('chat-scroll');
                    if (!sc) return true;
                    const threshold = 24; // px
                    return (sc.scrollTop + sc.clientHeight) >= (sc.scrollHeight - threshold);
                }
            }
        }
    </script>
@endpush
