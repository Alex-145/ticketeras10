<div x-data="companiesCtxMenu()" x-init="boot()">

    {{-- Toolbar --}}
    <div class="d-flex flex-wrap align-items-center mb-3">
        <div class="input-group input-group-sm mr-2" style="max-width: 320px;">
            <div class="input-group-prepend">
                <span class="input-group-text"><i class="fas fa-search"></i></span>
            </div>
            <input type="text" class="form-control" placeholder="Search companies..."
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
            <i class="fas fa-plus-circle mr-1"></i> New Company
        </button>
    </div>

    {{-- Table --}}
    <div class="table-responsive">
        <table class="table table-sm table-hover table-striped">
            <thead class="thead-light">
                <tr>
                    <th style="width:42px;"></th>
                    <th wire:click="sortBy('name')" class="cursor-pointer">
                        Name
                        @if ($sortField === 'name')
                            <i class="fas fa-sort-{{ $sortDirection === 'asc' ? 'up' : 'down' }}"></i>
                        @endif
                    </th>
                    <th wire:click="sortBy('ruc')" class="cursor-pointer" style="width:160px;">
                        RUC
                        @if ($sortField === 'ruc')
                            <i class="fas fa-sort-{{ $sortDirection === 'asc' ? 'up' : 'down' }}"></i>
                        @endif
                    </th>
                    <th wire:click="sortBy('phone')" class="cursor-pointer" style="width:180px;">
                        Phone
                        @if ($sortField === 'phone')
                            <i class="fas fa-sort-{{ $sortDirection === 'asc' ? 'up' : 'down' }}"></i>
                        @endif
                    </th>
                    <th style="width:100px;" class="text-center">Active</th>
                    <th style="width:130px;" class="text-right">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($rows as $item)
                    <tr
                        @contextmenu.prevent="open($event, {{ $item->id }}, {{ $item->is_active ? 'true' : 'false' }})">

                        <td>
                            @if ($item->logo_url)
                                <img src="{{ $item->logo_url }}" class="rounded"
                                    style="width:32px; height:32px; object-fit:cover">
                            @else
                                <span class="badge badge-secondary">No logo</span>
                            @endif
                        </td>
                        <td><strong>{{ $item->name }}</strong></td>
                        <td>{{ $item->ruc }}</td>
                        <td>{{ $item->phone }}</td>
                        <td class="text-center">
                            <button class="btn btn-{{ $item->is_active ? 'success' : 'secondary' }} btn-xs"
                                wire:click="toggleActive({{ $item->id }})">
                                {{ $item->is_active ? 'Yes' : 'No' }}
                            </button>
                        </td>
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
                        <td colspan="6" class="text-center text-muted">No results.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-2">
        {{ $rows->onEachSide(1)->links() }}
    </div>
    {{-- Context Menu --}}
    <div x-show="visible" x-transition class="dropdown-menu show shadow" :style="style" @click.away="close"
        @keydown.escape.window="close">
        <button type="button" class="dropdown-item d-flex align-items-center" @click="toggle()" :disabled="busy">
            <i class="fas fa-toggle-on mr-2" :class="{ 'text-success': isActive, 'text-secondary': !isActive }"></i>
            <span x-text="isActive ? 'Deactivate' : 'Activate'"></span>
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

    {{-- Modal: Create/Edit (responsive + scroll + XL) --}}
    <div class="modal fade @if ($showFormModal) show d-block @endif" tabindex="-1" role="dialog"
        @if ($showFormModal) style="background:rgba(0,0,0,.5);" @endif
        aria-hidden="{{ $showFormModal ? 'false' : 'true' }}">

        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-xl" role="document"
            wire:ignore.self>
            <div class="modal-content">
                <div class="modal-header bg-primary">
                    <h5 class="modal-title text-white mb-0">
                        <i class="fas fa-building mr-1"></i>
                        {{ $editingId ? 'Edit Company' : 'New Company' }}
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
                                <label>RUC</label>
                                <input type="text" class="form-control" wire:model.defer="ruc" maxlength="11">
                                <div class="text-danger small"><x-input-error for="ruc" /></div>
                            </div>

                            <div class="form-group col-md-6">
                                <label>Phone</label>
                                <input type="text" class="form-control" wire:model.defer="phone">
                                <div class="text-danger small"><x-input-error for="phone" /></div>
                            </div>

                            <div class="form-group col-md-3">
                                <label>Active</label>
                                <div>
                                    <input type="checkbox" id="chkActive" class="mr-1 align-middle"
                                        wire:model.defer="is_active">
                                    <label for="chkActive" class="mb-0 align-middle">Yes</label>
                                </div>
                                <div class="text-danger small"><x-input-error for="is_active" /></div>
                            </div>

                            <div class="form-group col-md-6">
                                <label>Logo</label>
                                <input type="file" class="form-control-file" wire:model="logo" accept="image/*">
                                <div class="text-danger small"><x-input-error for="logo" /></div>
                                <div wire:loading wire:target="logo" class="small text-muted mt-1">
                                    <span class="spinner-border spinner-border-sm"></span> Uploading...
                                </div>
                            </div>

                            <div class="form-group col-md-6">
                                @if ($logo)
                                    <label class="d-block">Preview</label>
                                    <img src="{{ $logo->temporaryUrl() }}" class="border rounded img-fluid"
                                        style="max-height:120px;">
                                @elseif ($editingId && optional(\App\Models\Company::find($editingId))->logo_url)
                                    <label class="d-block">Current logo</label>
                                    <img src="{{ \App\Models\Company::find($editingId)->logo_url }}"
                                        class="border rounded img-fluid" style="max-height:120px;">
                                @endif
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
                        <i class="fas fa-trash mr-1"></i> Delete company
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
</div>
