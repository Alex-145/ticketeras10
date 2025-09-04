{{-- resources/views/profile/update-password-form.blade.php --}}
<div>
    {{-- Mensaje de éxito --}}
    <div>
        <x-action-message class="text-success mb-3" on="saved">
            <i class="fas fa-check-circle mr-1"></i> {{ __('La contraseña se actualizó correctamente.') }}
        </x-action-message>
    </div>

    <form wire:submit.prevent="updatePassword">
        <div class="row">
            {{-- Contraseña actual --}}
            <div class="form-group col-md-12">
                <label for="current_password">
                    <i class="fas fa-lock mr-1 text-muted"></i> {{ __('Contraseña actual') }}
                </label>
                <input id="current_password" type="password" class="form-control" wire:model="state.current_password"
                    autocomplete="current-password">
                <x-input-error for="current_password" class="text-danger mt-1" />
            </div>

            {{-- Nueva contraseña --}}
            <div class="form-group col-md-6">
                <label for="password">
                    <i class="fas fa-key mr-1 text-muted"></i> {{ __('Nueva contraseña') }}
                </label>
                <input id="password" type="password" class="form-control" wire:model="state.password"
                    autocomplete="new-password">
                <x-input-error for="password" class="text-danger mt-1" />
            </div>

            {{-- Confirmar nueva contraseña --}}
            <div class="form-group col-md-6">
                <label for="password_confirmation">
                    <i class="fas fa-check-double mr-1 text-muted"></i> {{ __('Confirmar contraseña') }}
                </label>
                <input id="password_confirmation" type="password" class="form-control"
                    wire:model="state.password_confirmation" autocomplete="new-password">
                <x-input-error for="password_confirmation" class="text-danger mt-1" />
            </div>
        </div>

        {{-- Botón guardar --}}
        <div class="text-right">
            <button type="submit" class="btn btn-warning" wire:loading.attr="disabled">
                <i class="fas fa-save mr-1"></i> {{ __('Actualizar contraseña') }}
            </button>
        </div>
    </form>
</div>
