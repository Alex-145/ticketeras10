<div x-data="ticketBoard()" x-init="boot">
    {{-- Toolbar --}}
    <div class="d-flex flex-wrap align-items-center mb-3">
        <div class="input-group input-group-sm mr-2" style="max-width: 360px;">
            <div class="input-group-prepend">
                <span class="input-group-text"><i class="fas fa-search"></i></span>
            </div>
            <input type="text" class="form-control" placeholder="Search tickets / applicants / companies..."
                wire:model.debounce.400ms="search">
        </div>

        <div class="ml-auto">
            <button class="btn btn-primary btn-sm" @click="$wire.set('showCreateModal', true)">
                <i class="fas fa-plus-circle mr-1"></i> New Ticket
            </button>
        </div>
    </div>

    {{-- Board --}}
    <div class="row">
        @foreach (['todo' => 'To Do', 'doing' => 'Doing', 'done' => 'Done'] as $col => $label)
            <div class="col-md-4 mb-3">
                <div
                    class="card card-outline @if ($col === 'todo') card-secondary @elseif($col === 'doing') card-warning @else card-success @endif">
                    <div class="card-header py-2 d-flex align-items-center justify-content-between">
                        <strong>{{ $label }}</strong>
                        <span class="badge badge-light">{{ count($$col) }}</span>
                    </div>

                    <div class="card-body p-2" :data-col="'{{ $col }}'" @dragover.prevent
                        @drop="onDrop($event,'{{ $col }}')" style="min-height: 60vh;">

                        @foreach ($$col as $t)
                            <div class="card mb-2 shadow-sm" draggable="true"
                                @dragstart="onDragStart($event, {{ $t['id'] }})">
                                <div class="card-body p-2">
                                    <div class="d-flex">
                                        <div class="mr-2">
                                            <i class="far fa-sticky-note text-muted"></i>
                                        </div>
                                        <div class="flex-grow-1">
                                            <div class="d-flex justify-content-between">
                                                <strong>
                                                    <i class="fas fa-user mr-1 text-muted"></i>
                                                    {{ $t['applicant']['name'] ?? '—' }}
                                                </strong>
                                                <small class="text-muted">ID: {{ $t['id'] }}</small>
                                            </div>

                                            @if (!empty($t['company']))
                                                <div class="small text-muted">
                                                    <i class="fas fa-building mr-1"></i>
                                                    {{ $t['company']['name'] ?? '' }}
                                                </div>
                                            @endif

                                            <div class="text-muted small mt-1">
                                                {{ \Illuminate\Support\Str::limit($t['description'] ?? 'No detail', 140) }}
                                            </div>

                                            @if (!empty($t['image_path']))
                                                <div class="mt-1">
                                                    <a class="small" href="{{ asset('storage/' . $t['image_path']) }}"
                                                        target="_blank">
                                                        <i class="far fa-image mr-1"></i> Image
                                                    </a>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach

                        @if (empty($$col))
                            <div class="text-center text-muted small py-4">Empty</div>
                        @endif
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    {{-- Modal: Create Ticket --}}
    <div class="modal fade @if ($showCreateModal) show d-block @endif" tabindex="-1" role="dialog"
        @if ($showCreateModal) style="background:rgba(0,0,0,.5);" @endif
        aria-hidden="{{ $showCreateModal ? 'false' : 'true' }}">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg" role="document"
            wire:ignore.self>
            <div class="modal-content">
                <div class="modal-header bg-primary">
                    <h5 class="modal-title text-white mb-0">
                        <i class="fas fa-ticket-alt mr-1"></i> Create Ticket
                    </h5>
                    <button type="button" class="close text-white" aria-label="Close"
                        wire:click="$set('showCreateModal', false)">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>

                <div class="modal-body">
                    {{-- Tabs --}}
                    <ul class="nav nav-tabs mb-3">
                        <li class="nav-item">
                            <a class="nav-link @if ($createTab === 'image') active @endif" href="#"
                                @click.prevent="$wire.set('createTab','image')">
                                <i class="far fa-image mr-1"></i> From Image / Clipboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link @if ($createTab === 'manual') active @endif" href="#"
                                @click.prevent="$wire.set('createTab','manual')">
                                <i class="fas fa-keyboard mr-1"></i> Manual
                            </a>
                        </li>
                    </ul>

                    {{-- From Image --}}
                    @if ($createTab === 'image')
                        <div x-data="ticketPasteDrop($wire)" class="border rounded p-3">
                            {{-- Zona paste/drop --}}
                            <div class="form-group mb-3">
                                <label class="d-flex align-items-center mb-1">
                                    <i class="far fa-clipboard mr-2 text-muted"></i>
                                    Paste / Drop area
                                </label>

                                <div class="border rounded d-flex align-items-center justify-content-center text-center py-4 px-3"
                                    :class="{ 'border-primary': isOver }" @dragover.prevent="isOver = true"
                                    @dragleave.prevent="isOver = false" @drop.prevent="handleDrop($event)"
                                    @paste.prevent="handlePaste($event)" tabindex="0"
                                    title="Paste image or text here (Ctrl/Cmd + V)">
                                    <div>
                                        <div class="mb-1">Paste an image or text here (Ctrl/Cmd + V)</div>
                                        <small class="text-muted">You can also drag & drop an image.</small>
                                    </div>
                                </div>

                                {{-- Preview / estado --}}
                                <template x-if="previewUrl">
                                    <div class="mt-3">
                                        <img :src="previewUrl" class="img-fluid rounded border"
                                            style="max-height: 200px;">
                                    </div>
                                </template>

                                <div class="small text-muted mt-2" x-show="isUploading">
                                    <span class="spinner-border spinner-border-sm mr-1"></span> Uploading pasted
                                    image...
                                </div>
                                <div class="small text-danger mt-2" x-text="error" x-show="error"></div>
                            </div>

                            {{-- Input de archivo (opcional) --}}
                            <div class="form-group">
                                <label>Image (optional if you pasted one)</label>
                                <input type="file" class="form-control-file" wire:model="image" accept="image/*">
                                <div class="text-danger small"><x-input-error for="image" /></div>
                                <div wire:loading wire:target="image" class="small text-muted mt-1">
                                    <span class="spinner-border spinner-border-sm"></span> Uploading...
                                </div>
                            </div>

                            {{-- Campos extraídos y fallback --}}
                            <div class="form-row">
                                <div class="form-group col-md-4">
                                    <label>Extracted applicant name / alias</label>
                                    <input type="text" class="form-control"
                                        wire:model.defer="extracted_applicant_name" placeholder="John Doe or alias">
                                    <small class="text-muted">We match by alias first, then by name.</small>
                                </div>

                                <div class="form-group col-md-4">
                                    <label>Extracted phone (backup)</label>
                                    <input type="text" class="form-control" wire:model.defer="extracted_phone"
                                        placeholder="+51 9xxxxxxxx">
                                    <small class="text-muted">Phone is used only if name/alias not found.</small>
                                </div>

                                <div class="form-group col-md-4">
                                    <label>Fallback applicant</label>
                                    <select class="form-control" wire:model="fallback_applicant_id">
                                        <option value="">-- Select applicant --</option>
                                        @foreach ($applicants as $id => $text)
                                            <option value="{{ $id }}">{{ $text }}</option>
                                        @endforeach
                                    </select>
                                    <small class="text-muted">Used if auto-detection fails.</small>
                                </div>
                            </div>

                            <div class="form-group">
                                <label>Extracted detail</label>
                                <textarea class="form-control" rows="5" wire:model.defer="extracted_detail"
                                    placeholder="Paste will auto-fill. You can edit before creating."></textarea>
                            </div>

                            <div class="alert alert-info py-2">
                                <i class="fas fa-info-circle mr-1"></i>
                                We will only save: <strong>Image</strong>, <strong>Applicant</strong> and
                                <strong>Detail</strong>.
                                Classification (module, category) and resolution time are set later.
                            </div>
                        </div>
                    @endif

                    {{-- Manual --}}
                    @if ($createTab === 'manual')
                        <div>
                            <div class="form-group">
                                <label>Applicant <span class="text-danger">*</span></label>
                                <select class="form-control" wire:model="applicant_id">
                                    <option value="">-- Select applicant --</option>
                                    @foreach ($applicants as $id => $text)
                                        <option value="{{ $id }}">{{ $text }}</option>
                                    @endforeach
                                </select>
                                <div class="text-danger small"><x-input-error for="applicant_id" /></div>
                            </div>

                            <div class="form-group">
                                <label>Detail</label>
                                <textarea class="form-control" rows="5" wire:model.defer="description"
                                    placeholder="Describe the issue/request..."></textarea>
                            </div>

                            <div class="alert alert-info py-2">
                                <i class="fas fa-info-circle mr-1"></i>
                                Manual tickets also save only <strong>Applicant</strong> and <strong>Detail</strong> for
                                now.
                            </div>
                        </div>
                    @endif
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary"
                        wire:click="$set('showCreateModal', false)">Close</button>

                    @if ($createTab === 'image')
                        <button type="button" class="btn btn-primary" wire:click="createFromImage"
                            wire:loading.attr="disabled">
                            <span wire:loading wire:target="createFromImage"
                                class="spinner-border spinner-border-sm mr-1"></span>
                            Create from image
                        </button>
                    @else
                        <button type="button" class="btn btn-primary" wire:click="createManual"
                            wire:loading.attr="disabled">
                            <span wire:loading wire:target="createManual"
                                class="spinner-border spinner-border-sm mr-1"></span>
                            Create ticket
                        </button>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

@push('js')
    <script>
        function ticketBoard() {
            return {
                draggingId: null,
                boot() {},
                onDragStart(evt, id) {
                    this.draggingId = id;
                    evt.dataTransfer.effectAllowed = 'move';
                },
                onDrop(evt, toCol) {
                    evt.preventDefault();
                    if (!this.draggingId) return;
                    this.$wire.moveTicket(this.draggingId, toCol);
                    this.draggingId = null;
                },
            }
        }

        /**
         * Paste/Drop helper:
         *  - Image -> $wire.upload('image', file, ...)
         *  - Text  -> $wire.onPastedText(text)
         */
        function ticketPasteDrop($wire) {
            return {
                isOver: false,
                isUploading: false,
                previewUrl: null,
                error: null,

                async handlePaste(evt) {
                    this.error = null;
                    const items = evt.clipboardData?.items || [];
                    // 1) image in clipboard
                    for (const it of items) {
                        if (it.kind === 'file' && it.type.startsWith('image/')) {
                            const blob = it.getAsFile();
                            if (blob) {
                                const file = new File([blob], `pasted-${Date.now()}.png`, {
                                    type: blob.type || 'image/png'
                                });
                                return this.uploadFile(file);
                            }
                        }
                    }
                    // 2) text
                    const text = evt.clipboardData?.getData('text') || '';
                    if (text.trim()) {
                        $wire.onPastedText(text);
                    }
                },

                async handleDrop(evt) {
                    this.isOver = false;
                    this.error = null;
                    const files = evt.dataTransfer?.files || [];
                    if (files.length === 0) return;
                    const file = files[0];
                    if (!file.type?.startsWith('image/')) {
                        this.error = 'Only image files are allowed here.';
                        return;
                    }
                    await this.uploadFile(file);
                },

                async uploadFile(file) {
                    this.isUploading = true;
                    this.previewUrl = URL.createObjectURL(file);
                    try {
                        await $wire.upload(
                            'image', file,
                            () => {},
                            () => {
                                this.error = 'Upload failed.';
                            },
                            (event) => {}
                        );
                    } catch (e) {
                        this.error = 'Upload error.';
                    } finally {
                        this.isUploading = false;
                    }
                }
            }
        }
    </script>
@endpush

@push('css')
    <style>
        .card[draggable="true"] {
            cursor: move;
        }

        .dropdown-item[disabled] {
            pointer-events: none;
            opacity: .6;
        }

        .card-body[data-col] {
            transition: background-color .15s;
        }
    </style>
@endpush
