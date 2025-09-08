@extends('layouts.app')

@section('title', 'Tickets (Admin)')

@section('content_header')
    <h1 class="text-muted">Tickets — Administración</h1>
@endsection

@section('content')
    <livewire:admin.tickets.index />
@endsection
