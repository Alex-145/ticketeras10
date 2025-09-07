@extends('layouts.app')

@section('title', 'Ticket Chat')

@section('content_header')
    <h1 class="text-muted">
        Ticket Chat
        @isset($ticket)
            <small class="text-muted">#{{ $ticket->number ?? $ticket->id }}</small>
        @endisset
    </h1>
@endsection

@section('content')
    {{-- Livewire: chat, recibe el modelo Ticket --}}
    <livewire:tickets.chat :ticket="$ticket" />
@endsection
