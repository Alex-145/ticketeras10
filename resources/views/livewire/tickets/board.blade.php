<div x-data="ticketBoard()" x-init="boot">
    @include('livewire.tickets.partials._toolbar')

    <div class="row">
        @foreach (['todo' => 'To Do', 'doing' => 'Doing', 'done' => 'Done'] as $col => $label)
            @include('livewire.tickets.partials._column', [
                'col' => $col,
                'label' => $label,
                'items' => $$col,
            ])
        @endforeach
    </div>

    @include('livewire.tickets.partials._create_modal')
</div>

@push('js')
    <script>
        function ticketBoard() {
            return {
                draggingId: null,
                boot() {
                    if (window.Echo) {
                        window.Echo.channel('tickets')
                            .listen('.TicketStatusChanged', (e) => {
                                // refresca livewire sin recargar
                                this.$wire.refreshBoard();
                            });
                    }
                },

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

        function ticketPasteDrop($wire) {
            return {
                isOver: false,
                isUploading: false,
                previewUrl: null,
                error: null,
                async handlePaste(evt) {
                    this.error = null;
                    const items = evt.clipboardData?.items || [];
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
                    const text = evt.clipboardData?.getData('text') || '';
                    if (text.trim()) $wire.onPastedText(text);
                },
                async handleDrop(evt) {
                    this.isOver = false;
                    this.error = null;
                    const files = evt.dataTransfer?.files || [];
                    if (!files.length) return;
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
                        await $wire.upload('image', file, () => {}, () => {
                            this.error = 'Upload failed.'
                        }, (e) => {});
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

        .card-body[data-col] {
            transition: background-color .15s;
        }

        .dropdown-item[disabled] {
            pointer-events: none;
            opacity: .6;
        }
    </style>
@endpush
