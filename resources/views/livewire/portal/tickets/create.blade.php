<div x-data="pasteUploads()">
    <div class="card">
        <div class="card-header bg-primary text-white">New Ticket</div>
        <div class="card-body">
            <div class="form-group">
                <label>Title *</label>
                <input type="text" class="form-control" wire:model.defer="title">
                <x-input-error for="title" class="text-danger small" />
            </div>

            <div class="form-group">
                <label>Description (first message)</label>
                <textarea x-ref="desc" class="form-control" rows="4" wire:model.defer="description"
                    placeholder="Describe your issue... (you can paste images here)"></textarea>
                <x-input-error for="description" class="text-danger small" />
            </div>

            <div class="form-row">
                <div class="form-group col-md-4">
                    <label>Module *</label>
                    <select class="form-control" wire:model="module_id">
                        <option value="">-- select --</option>
                        @foreach (\App\Models\Module::orderBy('name')->get() as $m)
                            <option value="{{ $m->id }}">{{ $m->name }}</option>
                        @endforeach
                    </select>
                    <x-input-error for="module_id" class="text-danger small" />
                </div>
                <div class="form-group col-md-4">
                    <label>Category *</label>
                    <select class="form-control" wire:model="category_id">
                        <option value="">-- select --</option>
                        @foreach (\App\Models\Category::orderBy('name')->get() as $c)
                            <option value="{{ $c->id }}">{{ $c->name }}</option>
                        @endforeach
                    </select>
                    <x-input-error for="category_id" class="text-danger small" />
                </div>
                <div class="form-group col-md-4">
                    <label>Company (optional)</label>
                    <select class="form-control" wire:model="company_id">
                        <option value="">-- select --</option>
                        @foreach (\App\Models\Company::orderBy('name')->get() as $co)
                            <option value="{{ $co->id }}">{{ $co->name }}</option>
                        @endforeach
                    </select>
                    <x-input-error for="company_id" class="text-danger small" />
                </div>
            </div>

            <div class="form-group">
                <label>Images</label>
                <input type="file" class="form-control" multiple accept="image/*" wire:model="uploads">
                <div class="small text-muted">Puedes pegar imágenes con Ctrl+V en la descripción.</div>
                <x-input-error for="uploads.*" class="text-danger small" />
                <div class="d-flex flex-wrap mt-2">
                    @foreach ($uploads as $i => $img)
                        <img src="{{ $img->temporaryUrl() }}" class="mr-2 mb-2" style="height:80px;border-radius:8px;">
                    @endforeach
                </div>
            </div>
        </div>
        <div class="card-footer text-right">
            <button class="btn btn-primary" wire:click="save" wire:loading.attr="disabled">
                <span wire:loading wire:target="save" class="spinner-border spinner-border-sm mr-1"></span>
                Create ticket
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
                        this.$refs.desc?.addEventListener('paste', this.onPaste.bind(this));
                    });
                },
                onPaste(e) {
                    if (!e.clipboardData || !e.clipboardData.files?.length) return;
                    const files = Array.from(e.clipboardData.files).filter(f => f.type.startsWith('image/'));
                    if (files.length) {
                        @this.uploadMultiple('uploads', files, () => {}, () => {});
                    }
                }
            }
        }
    </script>
@endpush
