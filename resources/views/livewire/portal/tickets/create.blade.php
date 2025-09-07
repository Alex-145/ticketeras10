<div x-data="pasteUploads()" x-init="init">
    <div class="card">
        <div class="card-header bg-primary text-white">Nuevo Ticket</div>
        <div class="card-body">
            <div class="form-group">
                <label>Título *</label>
                <input type="text" class="form-control" wire:model.defer="title">
                <x-input-error for="title" class="text-danger small" />
            </div>

            <div class="form-row">
                <div class="form-group col-md-6">
                    <label>Tipo *</label>
                    <select class="form-control" wire:model="kind">
                        <option value="error">Error</option>
                        <option value="consulta">Consulta</option>
                        <option value="capacitacion">Capacitación</option>
                    </select>
                    <x-input-error for="kind" class="text-danger small" />
                </div>
                <div class="form-group col-md-6">
                    <label>Prioridad *</label>
                    <select class="form-control" wire:model="priority">
                        <option value="low">Baja</option>
                        <option value="normal">Normal</option>
                        <option value="high">Alta</option>
                        <option value="urgent">Urgente</option>
                    </select>
                    <x-input-error for="priority" class="text-danger small" />
                </div>
            </div>

            <div class="form-group">
                <label>Descripción *</label>
                <textarea x-ref="desc" class="form-control" rows="4" wire:model.defer="description"
                    placeholder="Describe tu solicitud... (puedes pegar imágenes con Ctrl+V)"></textarea>
                <x-input-error for="description" class="text-danger small" />
            </div>

            <div class="form-group">
                <label>Imágenes</label>
                <input type="file" class="form-control" multiple accept="image/*" wire:model="uploads">
                <div class="small text-muted">También puedes pegar imágenes con Ctrl+V en la descripción.</div>
                <x-input-error for="uploads.*" class="text-danger small" />
                <div class="d-flex flex-wrap mt-2">
                    @foreach ($uploads as $img)
                        <img src="{{ $img->temporaryUrl() }}" class="mr-2 mb-2" style="height:80px;border-radius:8px;">
                    @endforeach
                </div>
            </div>
        </div>
        <div class="card-footer text-right">
            <button class="btn btn-primary" wire:click="save" wire:loading.attr="disabled">
                <span wire:loading wire:target="save" class="spinner-border spinner-border-sm mr-1"></span>
                Crear ticket
            </button>
        </div>
    </div>
</div>

@push('js')
    <script>
        function pasteUploads() {
            return {
                init() {
                    this.$nextTick(() => {
                        const el = this.$refs.desc;
                        if (!el) return;
                        el.addEventListener('paste', (e) => {
                            const files = Array.from(e.clipboardData?.files || []).filter(f => f.type
                                .startsWith('image/'));
                            if (files.length) {
                                @this.uploadMultiple('uploads', files, () => {}, (err) => console.error(
                                    err));
                            }
                        });
                    });
                }
            }
        }
    </script>
@endpush
