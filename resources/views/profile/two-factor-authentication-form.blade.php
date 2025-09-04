{{-- resources/views/profile/two-factor-authentication-form.blade.php --}}
<div>
    {{-- Estado de 2FA --}}
    <h5 class="mb-3">
        @if ($this->enabled)
            @if ($showingConfirmation)
                <span class="text-warning">
                    <i class="fas fa-hourglass-half mr-1"></i>
                    {{ __('Finaliza la activación de la autenticación en dos pasos.') }}
                </span>
            @else
                <span class="text-success">
                    <i class="fas fa-check-circle mr-1"></i> {{ __('La autenticación en dos pasos está habilitada.') }}
                </span>
            @endif
        @else
            <span class="text-danger">
                <i class="fas fa-times-circle mr-1"></i> {{ __('La autenticación en dos pasos no está habilitada.') }}
            </span>
        @endif
    </h5>

    <p class="text-muted">
        {{ __('Cuando la autenticación en dos pasos está habilitada, se te pedirá un token seguro y aleatorio durante el inicio de sesión. Este token lo puedes obtener en la aplicación Google Authenticator de tu teléfono.') }}
    </p>

    {{-- Mostrar QR y clave --}}
    @if ($this->enabled && $showingQrCode)
        <div class="alert alert-info mt-3">
            <strong>
                @if ($showingConfirmation)
                    {{ __('Escanea este código QR o ingresa la clave para finalizar la activación de la autenticación en dos pasos.') }}
                @else
                    {{ __('Escanea este código QR o ingresa la clave para vincular tu aplicación de autenticación.') }}
                @endif
            </strong>
        </div>

        <div class="bg-white p-3 d-inline-block rounded shadow-sm">
            {!! $this->user->twoFactorQrCodeSvg() !!}
        </div>

        <p class="mt-3"><strong>{{ __('Clave de configuración:') }}</strong>
            {{ decrypt($this->user->two_factor_secret) }}</p>

        {{-- Campo de código cuando se confirma --}}
        @if ($showingConfirmation)
            <div class="form-group mt-3">
                <label for="code"><i class="fas fa-key mr-1 text-muted"></i> {{ __('Código') }}</label>
                <input id="code" type="text" class="form-control w-50" wire:model="code"
                    autocomplete="one-time-code" inputmode="numeric" autofocus
                    wire:keydown.enter="confirmTwoFactorAuthentication">
                <x-input-error for="code" class="text-danger mt-1" />
            </div>
        @endif
    @endif

    {{-- Mostrar recovery codes --}}
    @if ($this->enabled && $showingRecoveryCodes)
        <div class="alert alert-warning mt-3">
            <strong>{{ __('Guarda estos códigos en un gestor de contraseñas seguro.') }}</strong>
            <br>{{ __('Podrás usarlos para acceder a tu cuenta si pierdes el dispositivo de autenticación.') }}
        </div>

        <div class="bg-light border rounded p-3 font-monospace">
            @foreach (json_decode(decrypt($this->user->two_factor_recovery_codes), true) as $code)
                <div>{{ $code }}</div>
            @endforeach
        </div>
    @endif

    {{-- Botones de acción --}}
    <div class="mt-4">
        @if (!$this->enabled)
            <x-confirms-password wire:then="enableTwoFactorAuthentication">
                <button type="button" class="btn btn-info" wire:loading.attr="disabled">
                    <i class="fas fa-shield-alt mr-1"></i> {{ __('Habilitar 2FA') }}
                </button>
            </x-confirms-password>
        @else
            @if ($showingRecoveryCodes)
                <x-confirms-password wire:then="regenerateRecoveryCodes">
                    <button type="button" class="btn btn-warning mr-2">
                        <i class="fas fa-redo mr-1"></i> {{ __('Regenerar códigos') }}
                    </button>
                </x-confirms-password>
            @elseif ($showingConfirmation)
                <x-confirms-password wire:then="confirmTwoFactorAuthentication">
                    <button type="button" class="btn btn-success mr-2" wire:loading.attr="disabled">
                        <i class="fas fa-check mr-1"></i> {{ __('Confirmar') }}
                    </button>
                </x-confirms-password>
            @else
                <x-confirms-password wire:then="showRecoveryCodes">
                    <button type="button" class="btn btn-secondary mr-2">
                        <i class="fas fa-eye mr-1"></i> {{ __('Mostrar códigos') }}
                    </button>
                </x-confirms-password>
            @endif

            @if ($showingConfirmation)
                <x-confirms-password wire:then="disableTwoFactorAuthentication">
                    <button type="button" class="btn btn-outline-danger" wire:loading.attr="disabled">
                        <i class="fas fa-times mr-1"></i> {{ __('Cancelar') }}
                    </button>
                </x-confirms-password>
            @else
                <x-confirms-password wire:then="disableTwoFactorAuthentication">
                    <button type="button" class="btn btn-danger" wire:loading.attr="disabled">
                        <i class="fas fa-ban mr-1"></i> {{ __('Deshabilitar 2FA') }}
                    </button>
                </x-confirms-password>
            @endif
        @endif
    </div>
</div>
