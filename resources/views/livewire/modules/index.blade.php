<div>
    {{-- Toolbar --}}
    <div class="d-flex flex-wrap align-items-center mb-3">
        <div class="input-group input-group-sm mr-2" style="max-width: 320px;">
            <div class="input-group-prepend">
                <span class="input-group-text"><i class="fas fa-search"></i></span>
            </div>
            <input type="text" class="form-control" placeholder="Search modules..." wire:model.debounce.400ms="search">
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
            <i class="fas fa-plus-circle mr-1"></i> New Module
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
                    <th style="width:130px;" class="text-right">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($rows as $item)
                    <tr>
                        <td><strong>{{ $item->name }}</strong></td>
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
                        <td colspan="2" class="text-center text-muted">No results.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-2">
        {{ $rows->onEachSide(1)->links() }}
    </div>

    {{-- Modal: Create/Edit --}}
    <div class="modal fade @if ($showFormModal) show d-block @endif" tabindex="-1" role="dialog"
        @if ($showFormModal) style="background:rgba(0,0,0,.5);" @endif
        aria-hidden="{{ $showFormModal ? 'false' : 'true' }}">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable" role="document" wire:ignore.self>
            <div class="modal-content">
                <div class="modal-header bg-primary">
                    <h5 class="modal-title text-white mb-0">
                        <i class="fas fa-puzzle-piece mr-1"></i>
                        {{ $editingId ? 'Edit Module' : 'New Module' }}
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

                        <div class="form-group">
                            <label>Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" wire:model.defer="name" autofocus>
                            <div class="text-danger small"><x-input-error for="name" /></div>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary"
                            wire:click="$set('showFormModal', false)">Close</button>
                        <button type="submit" class="btn btn-primary" wire:loading.attr="disabled">
                            <span wire:loading wire:target="save" class="spinner-border spinner-border-sm mr-1"></span>
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
                        <i class="fas fa-trash mr-1"></i> Delete module
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
