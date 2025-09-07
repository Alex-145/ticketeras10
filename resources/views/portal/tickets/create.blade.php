@extends('layouts.app')

@section('title', 'New Ticket')

@section('content_header')
    <h1 class="text-muted">Create Ticket</h1>
@endsection

@section('content')
    {{-- Livewire: portal ticket create --}}
    <livewire:portal.tickets.create />
@endsection
