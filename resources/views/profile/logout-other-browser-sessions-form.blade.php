<x-action-section>
    <x-slot name="title">
        <h5 class="mb-0">{{ __('Browser Sessions') }}</h5>
    </x-slot>

    <x-slot name="description">
        <p class="text-muted mb-0">
            {{ __('Manage and log out your active sessions on other browsers and devices.') }}
        </p>
    </x-slot>

    <x-slot name="content">
        <div class="alert alert-info small">
            {{ __('If necessary, you may log out of all of your other browser sessions across all of your devices. Some of your recent sessions are listed below; however, this list may not be exhaustive. If you feel your account has been compromised, you should also update your password.') }}
        </div>

        @if (count($this->sessions) > 0)
            <ul class="list-group mb-4">
                @foreach ($this->sessions as $session)
                    <li class="list-group-item d-flex align-items-center">
                        <div>
                            @if ($session->agent->isDesktop())
                                <i class="fas fa-desktop fa-lg text-secondary"></i>
                            @else
                                <i class="fas fa-mobile-alt fa-lg text-secondary"></i>
                            @endif
                        </div>

                        <div class="ms-3">
                            <div class="fw-semibold small text-muted">
                                {{ $session->agent->platform() ? $session->agent->platform() : __('Unknown') }} -
                                {{ $session->agent->browser() ? $session->agent->browser() : __('Unknown') }}
                            </div>

                            <div class="small text-muted">
                                {{ $session->ip_address }},
                                @if ($session->is_current_device)
                                    <span class="text-success fw-bold">{{ __('This device') }}</span>
                                @else
                                    {{ __('Last active') }} {{ $session->last_active }}
                                @endif
                            </div>
                        </div>
                    </li>
                @endforeach
            </ul>
        @endif

        <div class="d-flex align-items-center">
            <button type="button" class="btn btn-primary" wire:click="confirmLogout" wire:loading.attr="disabled">
                {{ __('Log Out Other Browser Sessions') }}
            </button>

            <x-action-message class="ms-3 text-success" on="loggedOut">
                {{ __('Done.') }}
            </x-action-message>
        </div>

        <!-- Log Out Other Devices Confirmation Modal -->
        <x-dialog-modal wire:model.live="confirmingLogout">
            <x-slot name="title">
                <h5 class="modal-title">{{ __('Log Out Other Browser Sessions') }}</h5>
            </x-slot>

            <x-slot name="content">
                <p class="text-muted">
                    {{ __('Please enter your password to confirm you would like to log out of your other browser sessions across all of your devices.') }}
                </p>

                <div class="form-group mt-3" x-data="{}"
                    x-on:confirming-logout-other-browser-sessions.window="setTimeout(() => $refs.password.focus(), 250)">
                    <input type="password" class="form-control" autocomplete="current-password"
                        placeholder="{{ __('Password') }}" x-ref="password" wire:model="password"
                        wire:keydown.enter="logoutOtherBrowserSessions">

                    <x-input-error for="password" class="text-danger mt-2" />
                </div>
            </x-slot>

            <x-slot name="footer">
                <button type="button" class="btn btn-secondary" wire:click="$toggle('confirmingLogout')"
                    wire:loading.attr="disabled">
                    {{ __('Cancel') }}
                </button>

                <button type="button" class="btn btn-danger ms-2" wire:click="logoutOtherBrowserSessions"
                    wire:loading.attr="disabled">
                    {{ __('Log Out Other Browser Sessions') }}
                </button>
            </x-slot>
        </x-dialog-modal>
    </x-slot>
</x-action-section>
