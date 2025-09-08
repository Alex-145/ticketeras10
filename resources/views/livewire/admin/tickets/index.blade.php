<div>
    {{-- Toolbar --}}
    <div class="d-flex flex-wrap align-items-end mb-3">
        <div class="btn-group mr-2 mb-2">
            <button class="btn btn-sm {{ $tab === 'open' ? 'btn-primary' : 'btn-outline-primary' }}"
                wire:click="setTab('open')">
                <i class="fas fa-folder-open mr-1"></i> Abiertos
            </button>
            <button class="btn btn-sm {{ $tab === 'closed' ? 'btn-primary' : 'btn-outline-primary' }}"
                wire:click="setTab('closed')">
                <i class="fas fa-check mr-1"></i> Resueltos
            </button>
        </div>

        <div class="input-group input-group-sm mr-2 mb-2" style="max-width:280px;">
            <div class="input-group-prepend">
                <span class="input-group-text"><i class="fas fa-search"></i></span>
            </div>
            <input type="text" class="form-control" placeholder="Buscar # o título..."
                wire:model.debounce.400ms="search">
        </div>

        {{-- Filtro: Compañía --}}
        <div class="form-group mr-2 mb-2">
            <label class="small mb-1 d-block">Compañía</label>
            <select class="form-control form-control-sm" wire:model="companyId" style="min-width:220px;">
                <option value="">— todas —</option>
                @foreach ($companies as $c)
                    <option value="{{ $c->id }}">{{ $c->name }}</option>
                @endforeach
            </select>
        </div>

        {{-- Filtro: Solicitante --}}
        <div class="form-group mr-2 mb-2">
            <label class="small mb-1 d-block">Solicitante</label>
            <input type="text" class="form-control form-control-sm" placeholder="Nombre del solicitante"
                wire:model.debounce.400ms="applicantName" style="min-width:220px;">
        </div>

        {{-- Filtro: Día de cierre (solo aplica en Resueltos) --}}
        <div class="form-group mr-2 mb-2">
            <label class="small mb-1 d-block">Día de cierre</label>
            <input type="date" class="form-control form-control-sm" wire:model="closedDate"
                {{ $tab === 'closed' ? '' : 'disabled' }} style="min-width:160px;">
        </div>

        <div class="form-group ml-auto mb-2">
            <label class="small mb-1 d-block">Filas</label>
            <select class="form-control form-control-sm" wire:model="perPage">
                <option>10</option>
                <option>25</option>
                <option>50</option>
                <option>100</option>
            </select>
        </div>
    </div>

    {{-- Tabla --}}
    <div class="card">
        <div class="table-responsive">
            <table class="table table-sm table-hover mb-0">
                <thead class="thead-light">
                    <tr>
                        <th style="width:120px;">Número</th>
                        <th>Título</th>
                        <th style="width:180px;">Solicitante</th>
                        <th style="width:160px;">Compañía</th>
                        <th style="width:110px;">Prioridad</th>
                        <th style="width:110px;">Tipo</th>
                        <th style="width:110px;">Estado</th>
                        <th style="width:160px;">Creado</th>
                        <th style="width:160px;">Cerrado</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($rows as $t)
                        <tr ondblclick="window.location='{{ route('tickets.chat', $t) }}'" style="cursor: pointer;"
                            title="Doble clic para abrir el chat">
                            <td><code>{{ $t->number }}</code></td>
                            <td>
                                <div class="font-weight-bold">{{ $t->title }}</div>
                                <div class="small text-muted">
                                    {{ optional($t->module)->name ?? '—' }} · {{ optional($t->category)->name ?? '—' }}
                                </div>
                            </td>
                            <td>{{ optional($t->applicant)->name ?? '—' }}</td>
                            <td>{{ optional($t->applicant?->company)->name ?? '—' }}</td>
                            <td>
                                @php
                                    $pMap = [
                                        'low' => 'secondary',
                                        'normal' => 'info',
                                        'high' => 'warning',
                                        'urgent' => 'danger',
                                    ];
                                    $pCol = $pMap[$t->priority] ?? 'secondary';
                                @endphp
                                <span class="badge badge-{{ $pCol }}">{{ strtoupper($t->priority) }}</span>
                            </td>
                            <td><span class="badge badge-primary">{{ strtoupper($t->kind) }}</span></td>
                            <td>
                                @php $stCol = $t->status === 'done' ? 'success' : 'secondary'; @endphp
                                <span class="badge badge-{{ $stCol }}">{{ strtoupper($t->status) }}</span>
                            </td>
                            <td>{{ $t->created_at?->format('d/m/Y H:i') }}</td>
                            <td>
                                @if ($t->status === 'done' && $t->last_moved_at)
                                    {{ \Illuminate\Support\Carbon::parse($t->last_moved_at)->format('d/m/Y H:i') }}
                                @else
                                    —
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center text-muted">Sin resultados.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="card-footer py-2">
            {{ $rows->onEachSide(1)->links() }}
        </div>
    </div>
</div>
