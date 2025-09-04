{{-- resources/views/profile/update-profile-information-form.blade.php --}}
<div>
    {{-- Mensaje de éxito --}}
    <div>
        <x-action-message class="text-success mb-3" on="saved">
            <i class="fas fa-check-circle mr-1"></i> {{ __('Cambios guardados correctamente.') }}
        </x-action-message>
    </div>

    <form wire:submit.prevent="updateProfileInformation">
        <div class="row">
            {{-- Foto de perfil --}}
            @if (Laravel\Jetstream\Jetstream::managesProfilePhotos())
                <div class="col-md-12 mb-4 text-center" x-data="{ photoName: null, photoPreview: null }">
                    <!-- Input oculto -->
                    <input type="file" id="photo" class="d-none" wire:model.live="photo" x-ref="photo"
                        x-on:change="
                            photoName = $refs.photo.files[0].name;
                            const reader = new FileReader();
                            reader.onload = (e) => { photoPreview = e.target.result; };
                            reader.readAsDataURL($refs.photo.files[0]);
                        " />

                    <!-- Foto actual -->
                    <div class="mb-2" x-show="! photoPreview">
                        <img src="{{ $this->user->profile_photo_url }}" alt="{{ $this->user->name }}"
                            class="rounded-circle img-thumbnail"
                            style="width: 100px; height: 100px; object-fit: cover;">
                    </div>

                    <!-- Preview nueva foto -->
                    <div class="mb-2" x-show="photoPreview" style="display: none;">
                        <img :src="photoPreview" class="rounded-circle img-thumbnail"
                            style="width: 100px; height: 100px; object-fit: cover;">
                    </div>

                    <div>
                        <button type="button" class="btn btn-sm btn-outline-primary mr-2"
                            x-on:click.prevent="$refs.photo.click()">
                            <i class="fas fa-upload mr-1"></i> {{ __('Subir nueva foto') }}
                        </button>

                        @if ($this->user->profile_photo_path)
                            <button type="button" class="btn btn-sm btn-outline-danger"
                                wire:click="deleteProfilePhoto">
                                <i class="fas fa-trash mr-1"></i> {{ __('Quitar foto') }}
                            </button>
                        @endif
                    </div>

                    <x-input-error for="photo" class="text-danger mt-2" />
                </div>
            @endif

            {{-- Nombre --}}
            <div class="form-group col-md-6">
                <label for="name">{{ __('Nombre') }}</label>
                <input id="name" type="text" class="form-control" wire:model="state.name" required
                    autocomplete="name">
                <x-input-error for="name" class="text-danger mt-1" />
            </div>

            {{-- Email --}}
            <div class="form-group col-md-6">
                <label for="email">{{ __('Correo electrónico') }}</label>
                <input id="email" type="email" class="form-control" wire:model="state.email" required
                    autocomplete="username">
                <x-input-error for="email" class="text-danger mt-1" />

                @if (Laravel\Fortify\Features::enabled(Laravel\Fortify\Features::emailVerification()) &&
                        !$this->user->hasVerifiedEmail())
                    <p class="text-warning mt-2 small">
                        <i class="fas fa-exclamation-circle mr-1"></i> {{ __('Tu correo aún no está verificado.') }}
                        <button type="button" class="btn btn-link p-0 text-sm"
                            wire:click.prevent="sendEmailVerification">
                            {{ __('Reenviar verificación') }}
                        </button>
                    </p>

                    @if ($this->verificationLinkSent)
                        <p class="text-success mt-2 small">
                            <i class="fas fa-check-circle mr-1"></i>
                            {{ __('Se ha enviado un nuevo enlace de verificación a tu correo.') }}
                        </p>
                    @endif
                @endif
            </div>
        </div>

        {{-- Botón guardar --}}
        <div class="text-right">
            <button type="submit" class="btn btn-primary" wire:loading.attr="disabled" wire:target="photo">
                <i class="fas fa-save mr-1"></i> {{ __('Guardar cambios') }}
            </button>
        </div>
    </form>
</div>
