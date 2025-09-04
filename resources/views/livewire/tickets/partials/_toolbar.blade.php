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
