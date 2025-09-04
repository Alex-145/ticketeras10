{{-- resources/views/profile/show.blade.php --}}
@extends('layouts.app')

@section('title', 'Perfil')

@section('content_header_title', 'Perfil de Usuario')
@section('content_header_subtitle', auth()->user()->name ?? '')

@section('content_body')
    <div class="row justify-content-center">
        <div class="col-lg-10">

            {{-- Información de Perfil --}}
            @if (Laravel\Fortify\Features::canUpdateProfileInformation())
                <div class="card card-primary card-outline mb-4 shadow-sm">
                    <div class="card-header bg-primary text-white">
                        <h3 class="card-title mb-0">
                            <i class="fas fa-user-circle mr-2"></i> Información del perfil
                        </h3>
                    </div>
                    <div class="card-body">
                        @livewire('profile.update-profile-information-form')
                    </div>
                </div>
            @endif

            {{-- Seguridad de la cuenta --}}
            <div class="card card-outline card-info mb-4 shadow-sm">
                <div class="card-header bg-info text-white">
                    <h3 class="card-title mb-0">
                        <i class="fas fa-shield-alt mr-2"></i> Seguridad de la cuenta
                    </h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        {{-- Cambiar contraseña --}}
                        @if (Laravel\Fortify\Features::enabled(Laravel\Fortify\Features::updatePasswords()))
                            <div class="col-md-6 mb-3">
                                <div class="border rounded p-3 h-100">
                                    <h5 class="text-warning">
                                        <i class="fas fa-key mr-1"></i> Cambiar contraseña
                                    </h5>
                                    <hr>
                                    @livewire('profile.update-password-form')
                                </div>
                            </div>
                        @endif

                        {{-- Autenticación 2FA --}}
                        @if (Laravel\Fortify\Features::canManageTwoFactorAuthentication())
                            <div class="col-md-6 mb-3">
                                <div class="border rounded p-3 h-100">
                                    <h5 class="text-info">
                                        <i class="fas fa-mobile-alt mr-1"></i> Autenticación en dos pasos
                                    </h5>
                                    <hr>
                                    @livewire('profile.two-factor-authentication-form')
                                </div>
                            </div>
                        @endif
                    </div>

                    {{-- Cerrar sesiones --}}
                    <div class="mt-3">
                        <div class="border rounded p-3">
                            <h5 class="text-secondary">
                                <i class="fas fa-sign-out-alt mr-1"></i> Cerrar otras sesiones
                            </h5>
                            <hr>
                            @livewire('profile.logout-other-browser-sessions-form')
                        </div>
                    </div>
                </div>
            </div>

            {{-- Eliminar cuenta --}}
            @if (Laravel\Jetstream\Jetstream::hasAccountDeletionFeatures())
                <div class="card card-outline card-danger shadow-sm">
                    <div class="card-header bg-danger text-white">
                        <h3 class="card-title mb-0">
                            <i class="fas fa-user-times mr-2"></i> Eliminar cuenta
                        </h3>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-danger">
                            ⚠️ {{ __('Una vez eliminada tu cuenta, todos tus datos serán borrados permanentemente.') }}
                        </div>
                        @livewire('profile.delete-user-form')
                    </div>
                </div>
            @endif

        </div>
    </div>
@endsection
