<div class="modal fade @if ($showCreateModal) show d-block @endif" tabindex="-1" role="dialog"
    @if ($showCreateModal) style="background:rgba(0,0,0,.5);" @endif
    aria-hidden="{{ $showCreateModal ? 'false' : 'true' }}">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg" role="document" wire:ignore.self>
        <div class="modal-content">
            <div class="modal-header bg-primary">
                <h5 class="modal-title text-white mb-0"><i class="fas fa-ticket-alt mr-1"></i> Create Ticket</h5>
                <button type="button" class="close text-white" aria-label="Close"
                    wire:click="$set('showCreateModal', false)">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <div class="modal-body">
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

                @if ($createTab === 'image')
                    <div x-data="ticketPasteDrop($wire)" class="border rounded p-3">
                        <div class="form-group mb-3">
                            <label class="d-flex align-items-center mb-1">
                                <i class="far fa-clipboard mr-2 text-muted"></i> Paste / Drop area
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

                            <template x-if="previewUrl">
                                <div class="mt-3">
                                    <img :src="previewUrl" class="img-fluid rounded border"
                                        style="max-height: 200px;">
                                </div>
                            </template>

                            <div class="small text-muted mt-2" x-show="isUploading">
                                <span class="spinner-border spinner-border-sm mr-1"></span> Uploading pasted image...
                            </div>
                            <div class="small text-danger mt-2" x-text="error" x-show="error"></div>
                        </div>

                        <div class="form-group">
                            <label>Image (optional if you pasted one)</label>
                            <input type="file" class="form-control-file" wire:model="image" accept="image/*">
                            <div class="text-danger small"><x-input-error for="image" /></div>
                            <div wire:loading wire:target="image" class="small text-muted mt-1">
                                <span class="spinner-border spinner-border-sm"></span> Uploading...
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group col-md-5">
                                <label>Extracted applicant name / alias</label>
                                <input type="text" class="form-control" wire:model.defer="extracted_applicant_name"
                                    placeholder="John Doe or alias">
                                <small class="text-muted">We match by alias/name first.</small>
                            </div>
                            <div class="form-group col-md-4">
                                <label>Extracted phone</label>
                                <input type="text" class="form-control" wire:model.defer="extracted_phone"
                                    placeholder="+51 9xxxxxxxx">
                                <small class="text-muted">Used only if name/alias not found.</small>
                            </div>
                            <div class="form-group col-md-3">
                                <label>Fallback applicant</label>
                                <select class="form-control" wire:model="fallback_applicant_id">
                                    <option value="">-- Optional --</option>
                                    @foreach ($applicants as $id => $text)
                                        <option value="{{ $id }}">{{ $text }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Extracted detail</label>
                            <textarea class="form-control" rows="3" wire:model.defer="extracted_detail"
                                placeholder="Optional edit of the extracted message"></textarea>
                        </div>
                        {{-- Debug opcional del OCR (toggle) --}}
                        <div x-data="{ open: false }" class="mt-2">
                            <a href="#" class="small" @click.prevent="open=!open">
                                <i class="fas fa-bug mr-1"></i> Show raw OCR text
                            </a>
                            <div x-show="open" class="mt-2">
                                <textarea class="form-control" rows="3" readonly>
{{ $extracted_detail }}
        </textarea>
                            </div>
                        </div>

                    </div>
                @endif

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
                            <label>Description</label>
                            <textarea class="form-control" rows="3" wire:model.defer="description"></textarea>
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
