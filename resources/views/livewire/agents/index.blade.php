<div>
    {{-- Toolbar --}}
    <div class="d-flex flex-wrap align-items-center mb-3">
        <div class="input-group input-group-sm mr-2" style="max-width: 360px;">
            <div class="input-group-prepend">
                <span class="input-group-text"><i class="fas fa-search"></i></span>
            </div>
            <input type="text" class="form-control" placeholder="Search agents..." wire:model.debounce.400ms="search">
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
            <i class="fas fa-plus-circle mr-1"></i> New Agent
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
                    <th wire:click="sortBy('email')" class="cursor-pointer">
                        Email
                        @if ($sortField === 'email')
                            <i class="fas fa-sort-{{ $sortDirection === 'asc' ? 'up' : 'down' }}"></i>
                        @endif
                    </th>
                    <th wire:click="sortBy('phone')" class="cursor-pointer" style="width:180px;">
                        Phone
                        @if ($sortField === 'phone')
                            <i class="fas fa-sort-{{ $sortDirection === 'asc' ? 'up' : 'down' }}"></i>
                        @endif
                    </th>
                    <th style="width:150px;" class="text-right">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($rows as $item)
                    <tr>
                        <td><strong>{{ $item->name }}</strong></td>
                        <td>{{ $item->email }}</td>
                        <td>{{ $item->phone }}</td>
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

    <div class="mt-2">{{ $rows->onEachSide(1)->links() }}</div>

    {{-- Modal: Create/Edit --}}
    <div class="modal fade @if ($showFormModal) show d-block @endif" tabindex="-1" role="dialog"
        @if ($showFormModal) style="background:rgba(0,0,0,.5);" @endif
        aria-hidden="{{ $showFormModal ? 'false' : 'true' }}">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable" role="document" wire:ignore.self>
            <div class="modal-content">
                <div class="modal-header bg-primary">
                    <h5 class="modal-title text-white mb-0">
                        <i class="fas fa-user-tie mr-1"></i>
                        {{ $editingId ? 'Edit Agent' : 'New Agent' }}
                    </h5>
                    <button type="button" class="close text-white" aria-label="Close"
                        wire:click="$set('showFormModal', false)">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>

                <form wire:submit.prevent="save" x-data="{ showPass: false }">
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
                                <label>Email <span class="text-danger">*</span></label>
                                <input type="email" class="form-control" wire:model.defer="email"
                                    placeholder="agent@example.com">
                                <div class="text-danger small"><x-input-error for="email" /></div>
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
                        </div>

                        {{-- Password block --}}
                        <div class="border rounded p-2">
                            @if (!$editingId)
                                <div class="custom-control custom-switch mb-2">
                                    <input type="checkbox" class="custom-control-input" id="autoPassSwitch"
                                        wire:model="auto_password">
                                    <label class="custom-control-label" for="autoPassSwitch">
                                        Generar contraseña aleatoria
                                    </label>
                                </div>

                                @if (!$auto_password)
                                    <div class="form-row">
                                        <div class="form-group col-md-6">
                                            <label>Password <span class="text-danger">*</span></label>
                                            <div class="input-group">
                                                <input :type="showPass ? 'text' : 'password'" class="form-control"
                                                    wire:model.defer="password" autocomplete="new-password">
                                                <div class="input-group-append">
                                                    <button type="button" class="btn btn-outline-secondary"
                                                        @click="showPass=!showPass" tabindex="-1">
                                                        <i class="far"
                                                            :class="showPass ? 'fa-eye-slash' : 'fa-eye'"></i>
                                                    </button>
                                                </div>
                                            </div>
                                            <div class="text-danger small"><x-input-error for="password" /></div>
                                        </div>
                                        <div class="form-group col-md-6">
                                            <label>Confirm password <span class="text-danger">*</span></label>
                                            <input :type="showPass ? 'text' : 'password'" class="form-control"
                                                wire:model.defer="password_confirmation" autocomplete="new-password">
                                        </div>
                                    </div>
                                @endif
                            @else
                                <div class="custom-control custom-switch mb-2">
                                    <input type="checkbox" class="custom-control-input" id="changePassSwitch"
                                        wire:model="change_password">
                                    <label class="custom-control-label" for="changePassSwitch">
                                        Cambiar contraseña
                                    </label>
                                </div>

                                @if ($change_password)
                                    <div class="form-row">
                                        <div class="form-group col-md-6">
                                            <label>New password <span class="text-danger">*</span></label>
                                            <div class="input-group">
                                                <input :type="showPass ? 'text' : 'password'" class="form-control"
                                                    wire:model.defer="password" autocomplete="new-password">
                                                <div class="input-group-append">
                                                    <button type="button" class="btn btn-outline-secondary"
                                                        @click="showPass=!showPass" tabindex="-1">
                                                        <i class="far"
                                                            :class="showPass ? 'fa-eye-slash' : 'fa-eye'"></i>
                                                    </button>
                                                </div>
                                            </div>
                                            <div class="text-danger small"><x-input-error for="password" /></div>
                                        </div>
                                        <div class="form-group col-md-6">
                                            <label>Confirm password <span class="text-danger">*</span></label>
                                            <input :type="showPass ? 'text' : 'password'" class="form-control"
                                                wire:model.defer="password_confirmation" autocomplete="new-password">
                                        </div>
                                    </div>
                                @endif
                            @endif
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
                        <i class="fas fa-trash mr-1"></i> Delete agent
                    </h5>
                    <button type="button" class="close text-white" aria-label="Close"
                        wire:click="$set('showDeleteModal', false)">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>

                <div class="modal-body">Are you sure you want to delete this record?</div>

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
