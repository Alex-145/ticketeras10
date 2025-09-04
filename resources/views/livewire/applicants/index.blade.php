<div x-data="applicantsCtxMenu()" x-init="boot()">
    {{-- Toolbar --}}
    <div class="d-flex flex-wrap align-items-center mb-3">
        <div class="input-group input-group-sm mr-2" style="max-width: 320px;">
            <div class="input-group-prepend">
                <span class="input-group-text"><i class="fas fa-search"></i></span>
            </div>
            <input type="text" class="form-control" placeholder="Search applicants..."
                wire:model.debounce.400ms="search">
        </div>

        <div class="form-inline mr-auto">
            <label class="mr-2 mb-0 small text-muted">Rows</label>
            <select class="form-control form-control-sm" wire:model="perPage">
                <option>10</option>
                <option>25</option>
                <option>50</option>
                <option>100</option>
            </select>
        </div>

        <button class="btn btn-primary btn-sm" wire:click="openCreate">
            <i class="fas fa-plus-circle mr-1"></i> New Applicant
        </button>
    </div>

    {{-- Table --}}
    <div class="table-responsive">
        <table class="table table-sm table-hover table-striped">
            <thead class="thead-light">
                <tr>
                    <th wire:click="sortBy('name')" class="cursor-pointer">
                        Name
                        @if ($sortField === 'name')
                            <i class="fas fa-sort-{{ $sortDirection === 'asc' ? 'up' : 'down' }}"></i>
                        @endif
                    </th>
                    <th wire:click="sortBy('phone')" class="cursor-pointer" style="width:180px;">
                        Phone
                        @if ($sortField === 'phone')
                            <i class="fas fa-sort-{{ $sortDirection === 'asc' ? 'up' : 'down' }}"></i>
                        @endif
                    </th>
                    <th wire:click="sortBy('company')" class="cursor-pointer">
                        Company
                        @if ($sortField === 'company')
                            <i class="fas fa-sort-{{ $sortDirection === 'asc' ? 'up' : 'down' }}"></i>
                        @endif
                    </th>
                    <th style="width:130px;" class="text-right">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($rows as $item)
                    <tr @contextmenu.prevent="openRowMenu($event, {{ $item->id }})">
                        <td><strong>{{ $item->name }}</strong></td>
                        <td>{{ $item->phone }}</td>
                        <td>{{ $item->company?->name }}</td>
                        <td class="text-right">
                            <button class="btn btn-info btn-xs" wire:click="openEdit({{ $item->id }})">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="btn btn-danger btn-xs" wire:click="confirmDelete({{ $item->id }})">
                                <i class="fas fa-trash"></i>
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="text-center text-muted">No results.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-2">
        {{ $rows->onEachSide(1)->links() }}
    </div>
    {{-- Context Menu (tabla de applicants) --}}
    <div x-show="visible" x-transition class="dropdown-menu show shadow" :style="style" @click.away="close"
        @keydown.escape.window="close">
        <button type="button" class="dropdown-item d-flex align-items-center" @click="aliases()"
            :disabled="busy">
            <i class="fas fa-user-secret mr-2 text-primary"></i> Aliases
        </button>
        <div class="dropdown-divider"></div>
        <button type="button" class="dropdown-item d-flex align-items-center" @click="edit()" :disabled="busy">
            <i class="fas fa-edit mr-2 text-info"></i> Edit
        </button>
        <button type="button" class="dropdown-item d-flex align-items-center text-danger" @click="del()"
            :disabled="busy">
            <i class="fas fa-trash mr-2"></i> Delete
        </button>
    </div>

    {{-- Modal: Create/Edit --}}
    <div class="modal fade @if ($showFormModal) show d-block @endif" tabindex="-1" role="dialog"
        @if ($showFormModal) style="background:rgba(0,0,0,.5);" @endif
        aria-hidden="{{ $showFormModal ? 'false' : 'true' }}">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable" role="document" wire:ignore.self>
            <div class="modal-content">
                <div class="modal-header bg-primary">
                    <h5 class="modal-title text-white mb-0">
                        <i class="fas fa-user-tag mr-1"></i>
                        {{ $editingId ? 'Edit Applicant' : 'New Applicant' }}
                    </h5>
                    <button type="button" class="close text-white" aria-label="Close"
                        wire:click="$set('showFormModal', false)">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>

                <form wire:submit.prevent="save">
                    <div class="modal-body">
                        @if ($errors->any())
                            <div class="alert alert-danger py-2">
                                <i class="fas fa-exclamation-triangle mr-1"></i>
                                Please check the highlighted fields.
                            </div>
                        @endif

                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label>Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" wire:model.defer="name" autofocus>
                                <div class="text-danger small"><x-input-error for="name" /></div>
                            </div>

                            <div class="form-group col-md-6">
                                <label>Phone</label>
                                <div class="input-group">
                                    <input type="text" class="form-control" style="max-width: 90px; flex: 0 0 90px;"
                                        wire:model.defer="phone_code" placeholder="+51">
                                    <input type="text" class="form-control" wire:model.defer="phone"
                                        placeholder="9xxxxxxxx">
                                </div>
                                <div class="text-danger small">
                                    <x-input-error for="phone_code" />
                                    <x-input-error for="phone" />
                                </div>
                            </div>


                            <div class="form-group col-md-12">
                                <label>Company <span class="text-danger">*</span></label>
                                <select class="form-control" wire:model="company_id">
                                    <option value="">-- Select company --</option>
                                    @foreach ($companies as $id => $text)
                                        <option value="{{ $id }}">{{ $text }}</option>
                                    @endforeach
                                </select>
                                <div class="text-danger small"><x-input-error for="company_id" /></div>
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary"
                            wire:click="$set('showFormModal', false)">Close</button>
                        <button type="submit" class="btn btn-primary" wire:loading.attr="disabled">
                            <span wire:loading wire:target="save"
                                class="spinner-border spinner-border-sm mr-1"></span>
                            Save
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Modal: Delete --}}
    <div class="modal fade @if ($showDeleteModal) show d-block @endif" tabindex="-1" role="dialog"
        @if ($showDeleteModal) style="background:rgba(0,0,0,.5);" @endif
        aria-hidden="{{ $showDeleteModal ? 'false' : 'true' }}">
        <div class="modal-dialog" role="document" wire:ignore.self>
            <div class="modal-content">
                <div class="modal-header bg-danger">
                    <h5 class="modal-title text-white mb-0">
                        <i class="fas fa-trash mr-1"></i> Delete applicant
                    </h5>
                    <button type="button" class="close text-white" aria-label="Close"
                        wire:click="$set('showDeleteModal', false)">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>

                <div class="modal-body">
                    Are you sure you want to delete this record?
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary"
                        wire:click="$set('showDeleteModal', false)">Cancel</button>
                    <button type="button" class="btn btn-danger" wire:click="delete">
                        <span wire:loading wire:target="delete" class="spinner-border spinner-border-sm mr-1"></span>
                        Delete
                    </button>
                </div>
            </div>
        </div>
    </div>
    {{-- Modal: Aliases del Applicant --}}
    <div class="modal fade @if ($showAliasModal) show d-block @endif" tabindex="-1" role="dialog"
        @if ($showAliasModal) style="background:rgba(0,0,0,.5);" @endif
        aria-hidden="{{ $showAliasModal ? 'false' : 'true' }}">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable" role="document" wire:ignore.self>
            <div class="modal-content" x-data="aliasesCtx()">
                <div class="modal-header bg-secondary">
                    <h5 class="modal-title text-white mb-0">
                        <i class="fas fa-user-secret mr-1"></i>
                        Applicant Aliases
                    </h5>
                    <button type="button" class="close text-white" aria-label="Close"
                        wire:click="$set('showAliasModal', false)">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>

                <div class="modal-body">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <div class="text-muted small">Right-click an alias for actions</div>
                        <button type="button" class="btn btn-outline-primary btn-sm" @click="$wire.aliasAddRow()">
                            <i class="fas fa-plus"></i> Add alias
                        </button>
                    </div>

                    @forelse ($aliasItems as $i => $row)
                        <div class="input-group mb-2" wire:key="alias-row-{{ $i }}"
                            @contextmenu.prevent="open($event, {{ $i }})">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><i class="fas fa-user-secret"></i></span>
                            </div>
                            <input id="alias-input-{{ $i }}" type="text" class="form-control"
                                placeholder="Alias" wire:model.defer="aliasItems.{{ $i }}.alias">
                            <div class="input-group-append">
                                <button type="button" class="btn btn-outline-danger"
                                    @click="$wire.aliasRemoveRow({{ $i }})">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        </div>
                        <div class="text-danger small"><x-input-error for="aliasItems.{{ $i }}.alias" />
                        </div>
                    @empty
                        <div class="text-muted small">No aliases yet.</div>
                    @endforelse

                    {{-- Mini menú dentro del modal --}}
                    <div x-show="visible" x-transition class="dropdown-menu show shadow" :style="style"
                        @click.away="close" @keydown.escape.window="close">
                        <button type="button" class="dropdown-item d-flex align-items-center" @click="rename()">
                            <i class="fas fa-i-cursor mr-2 text-info"></i> Rename
                        </button>
                        <button type="button" class="dropdown-item d-flex align-items-center" @click="add()">
                            <i class="fas fa-plus mr-2 text-primary"></i> Add new
                        </button>
                        <div class="dropdown-divider"></div>
                        <button type="button" class="dropdown-item d-flex align-items-center text-danger"
                            @click="remove()">
                            <i class="fas fa-trash mr-2"></i> Delete
                        </button>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary"
                        wire:click="$set('showAliasModal', false)">Close</button>
                    <button type="button" class="btn btn-primary" wire:click="aliasSave"
                        wire:loading.attr="disabled">
                        <span wire:loading wire:target="aliasSave"
                            class="spinner-border spinner-border-sm mr-1"></span>
                        Save aliases
                    </button>
                </div>
            </div>
        </div>
    </div>

</div>

@push('js')
    <script>
        /* ---- Context menu para filas de Applicants ---- */
        function applicantsCtxMenu() {
            return {
                visible: false,
                busy: false,
                x: 0,
                y: 0,
                selectedId: null,
                get style() {
                    return `position:fixed; left:${this.x}px; top:${this.y}px; z-index: 1060;`;
                },
                boot() {
                    window.addEventListener('scroll', () => this.close(), true);
                    window.addEventListener('resize', () => this.close());
                    document.addEventListener('click', (e) => {
                        if (this.visible && !e.target.closest('.dropdown-menu')) this.close();
                    });
                },
                openRowMenu(evt, id) {
                    this.selectedId = id;
                    const mw = 220,
                        mh = 150,
                        vw = innerWidth,
                        vh = innerHeight;
                    let px = evt.clientX,
                        py = evt.clientY;
                    if (px + mw > vw) px = vw - mw - 8;
                    if (py + mh > vh) py = vh - mh - 8;
                    this.x = Math.max(8, px);
                    this.y = Math.max(8, py);
                    this.visible = true;
                },
                close() {
                    this.visible = false;
                    this.busy = false;
                },
                async aliases() {
                    if (!this.selectedId) return;
                    this.busy = true;
                    await this.$wire.openAliases(this.selectedId);
                    this.close();
                },
                async edit() {
                    if (!this.selectedId) return;
                    this.busy = true;
                    await this.$wire.openEdit(this.selectedId);
                    this.close();
                },
                async del() {
                    if (!this.selectedId) return;
                    this.busy = true;
                    await this.$wire.confirmDelete(this.selectedId);
                    this.close();
                },
            }
        }

        /* ---- Context menu dentro del modal de Aliases ---- */
        function aliasesCtx() {
            return {
                visible: false,
                x: 0,
                y: 0,
                index: null,
                get style() {
                    return `position:fixed; left:${this.x}px; top:${this.y}px; z-index: 1070;`;
                },
                open(evt, i) {
                    this.index = i;
                    const mw = 200,
                        mh = 130,
                        vw = innerWidth,
                        vh = innerHeight;
                    let px = evt.clientX,
                        py = evt.clientY;
                    if (px + mw > vw) px = vw - mw - 8;
                    if (py + mh > vh) py = vh - mh - 8;
                    this.x = Math.max(8, px);
                    this.y = Math.max(8, py);
                    this.visible = true;
                },
                close() {
                    this.visible = false;
                },
                rename() {
                    // Simple: enfocar input de la fila
                    this.$nextTick(() => {
                        const el = document.getElementById(`alias-input-${this.index}`);
                        if (el) {
                            el.focus();
                            el.select();
                        }
                    });
                    this.close();
                },
                add() {
                    this.$wire.aliasAddRow();
                    this.close();
                },
                remove() {
                    if (this.index !== null) this.$wire.aliasRemoveRow(this.index);
                    this.close();
                },
            }
        }
    </script>
@endpush
