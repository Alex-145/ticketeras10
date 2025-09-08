@extends('layouts.app')

@section('title', 'Mis Tickets')

@section('content_header')
    <h1 class="text-muted">Mis Tickets</h1>
@endsection

@section('content')
    <livewire:portal.tickets.index />
@endsection
