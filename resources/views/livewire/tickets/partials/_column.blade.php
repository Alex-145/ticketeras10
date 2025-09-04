<div class="col-md-4 mb-3">
    <div
        class="card card-outline @if ($col === 'todo') card-secondary @elseif($col === 'doing') card-warning @else card-success @endif">
        <div class="card-header py-2 d-flex align-items-center justify-content-between">
            <strong>{{ $label }}</strong>
            <span class="badge badge-light">{{ count($items) }}</span>
        </div>

        <div class="card-body p-2" :data-col="'{{ $col }}'" @dragover.prevent
            @drop="onDrop($event,'{{ $col }}')" style="min-height: 60vh;">
            @forelse ($items as $t)
                <div class="card mb-2 shadow-sm" draggable="true" @dragstart="onDragStart($event, {{ $t['id'] }})">
                    <div class="card-body p-2">
                        <div class="d-flex align-items-center">
                            <div class="mr-2"><i class="far fa-sticky-note text-muted"></i></div>
                            <div class="flex-grow-1">
                                <div class="d-flex justify-content-between">
                                    <strong>ID: {{ $t['id'] }}</strong>
                                    @if (!empty($t['image_path']))
                                        <a class="small" href="{{ asset('storage/' . $t['image_path']) }}"
                                            target="_blank">
                                            <i class="far fa-image"></i> Image
                                        </a>
                                    @endif
                                </div>
                                <div class="text-muted small mt-1">
                                    <i class="fas fa-user mr-1 text-muted"></i>
                                    {{ $t['applicant']['name'] ?? '' }}
                                    @if (!empty($t['company']))
                                        <span class="text-muted"> · </span>
                                        <i class="fas fa-building mr-1 text-muted"></i>
                                        {{ $t['company']['name'] ?? '' }}
                                    @endif
                                </div>
                                @if ($col === 'doing')
                                    <div class="small text-muted mt-1">
                                        <i class="fas fa-user-check mr-1"></i>
                                        In progress by
                                        <strong>
                                            {{ $t['claimed_by']['name'] ?? ($t['last_moved_by']['name'] ?? '—') }}
                                        </strong>
                                    </div>
                                @endif

                                @if (!empty($t['description']))
                                    <div class="small mt-1">
                                        {{ \Illuminate\Support\Str::limit($t['description'], 140) }}
                                    </div>
                                @endif

                            </div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="text-center text-muted small py-4">Empty</div>
            @endforelse
        </div>
    </div>
</div>
