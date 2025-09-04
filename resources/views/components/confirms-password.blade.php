@props([
    'title' => __('Confirm Password'),
    'content' => __('For your security, please confirm your password to continue.'),
    'button' => __('Confirm'),
])

@php
    $confirmableId = md5($attributes->wire('then'));
@endphp

<span {{ $attributes->wire('then') }} x-data x-ref="span"
    x-on:click="$wire.startConfirmingPassword('{{ $confirmableId }}')"
    x-on:password-confirmed.window="setTimeout(() => $event.detail.id === '{{ $confirmableId }}' && $refs.span.dispatchEvent(new CustomEvent('then', { bubbles: false })), 250);">
    {{ $slot }}
</span>

@once
    <x-dialog-modal wire:model.live="confirmingPassword">
        <x-slot name="title">
            <h4 class="modal-title">{{ $title }}</h4>
        </x-slot>

        <x-slot name="content">
            <p class="text-muted">{{ $content }}</p>

            <div class="form-group mt-3" x-data="{}"
                x-on:confirming-password.window="setTimeout(() => $refs.confirmable_password.focus(), 250)">
                <input type="password" class="form-control" placeholder="{{ __('Password') }}"
                    autocomplete="current-password" x-ref="confirmable_password" wire:model="confirmablePassword"
                    wire:keydown.enter="confirmPassword">

                <x-input-error for="confirmable_password" class="text-danger mt-2" />
            </div>
        </x-slot>

        <x-slot name="footer">
            <button type="button" class="btn btn-secondary" wire:click="stopConfirmingPassword"
                wire:loading.attr="disabled">
                {{ __('Cancel') }}
            </button>

            <button type="button" class="btn btn-primary ms-2" dusk="confirm-password-button" wire:click="confirmPassword"
                wire:loading.attr="disabled">
                {{ $button }}
            </button>
        </x-slot>
    </x-dialog-modal>
@endonce
