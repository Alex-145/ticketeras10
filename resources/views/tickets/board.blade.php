@extends('layouts.app')

@section('title', 'Tickets')
@section('content_header_title', 'Tickets Board')
@section('content_header_subtitle', 'To Do · Doing · Done')

@section('content_body')
    <div class="card card-primary card-outline">
        <div class="card-header">
            <h3 class="card-title mb-0"><i class="fas fa-columns mr-1"></i> Kanban</h3>
        </div>
        <div class="card-body">
            @livewire('tickets.board')
        </div>
    </div>
@endsection
@push('js')
    <script>
        /**
         * Maneja pegar/arrastrar imágenes y texto hacia Livewire:
         * - Imagen: $wire.upload('image', file, ...)
         * - Texto:  $wire.onPastedText(text)
         */
        function ticketPasteDrop($wire) {
            return {
                isOver: false,
                isUploading: false,
                previewUrl: null,
                error: null,

                // Pegar desde portapapeles
                async handlePaste(evt) {
                    this.error = null;
                    const items = evt.clipboardData?.items || [];
                    // 1) imagen en el portapapeles
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
                    // 2) texto en el portapapeles
                    const text = evt.clipboardData?.getData('text') || '';
                    if (text.trim()) {
                        $wire.onPastedText(text);
                    }
                },

                // Arrastrar imagen
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

                // Subir a Livewire y mostrar preview
                async uploadFile(file) {
                    this.isUploading = true;
                    this.previewUrl = URL.createObjectURL(file);
                    try {
                        await $wire.upload('image', file,
                            () => {}, // success
                            () => {
                                this.error = 'Upload failed.';
                            }, // error
                            (event) => {} // progress
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
