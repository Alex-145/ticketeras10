{{-- resources/views/profile/delete-user-form.blade.php --}}
<div>
    <div class="alert alert-danger d-flex align-items-center" role="alert">
        <i class="fas fa-exclamation-triangle fa-2x mr-2"></i>
        <div>
            <strong>{{ __('¡Atención!') }}</strong>
            {{ __('Una vez que elimines tu cuenta, todos los datos y recursos serán eliminados de forma permanente.') }}
        </div>
    </div>

    <div class="text-center my-3">
        <button type="button" class="btn btn-lg btn-outline-danger" wire:click="confirmUserDeletion"
            wire:loading.attr="disabled">
            <i class="fas fa-user-times mr-1"></i> {{ __('Eliminar cuenta') }}
        </button>
    </div>

    <!-- Modal de confirmación -->
    <x-dialog-modal wire:model.live="confirmingUserDeletion">
        <x-slot name="title">
            <i class="fas fa-exclamation-circle text-danger mr-1"></i> {{ __('Confirmar eliminación') }}
        </x-slot>

        <x-slot name="content">
            <p class="text-muted">
                {{ __('¿Seguro que deseas eliminar tu cuenta? Esta acción no se puede deshacer. Ingresa tu contraseña para confirmar.') }}
            </p>

            <div class="mt-3" x-data="{}"
                x-on:confirming-delete-user.window="setTimeout(() => $refs.password.focus(), 250)">
                <x-input type="password" class="form-control" autocomplete="current-password"
                    placeholder="{{ __('Contraseña') }}" x-ref="password" wire:model="password"
                    wire:keydown.enter="deleteUser" />

                <x-input-error for="password" class="mt-2 text-danger" />
            </div>
        </x-slot>

        <x-slot name="footer">
            <button type="button" class="btn btn-secondary" wire:click="$toggle('confirmingUserDeletion')"
                wire:loading.attr="disabled">
                <i class="fas fa-times mr-1"></i> {{ __('Cancelar') }}
            </button>

            <button type="button" class="btn btn-danger ml-2" wire:click="deleteUser" wire:loading.attr="disabled">
                <i class="fas fa-trash-alt mr-1"></i> {{ __('Eliminar definitivamente') }}
            </button>
        </x-slot>
    </x-dialog-modal>
</div>
