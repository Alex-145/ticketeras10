@extends('layouts.app')

@section('title', 'Agents')

@section('content_header')
    <h1 class="text-muted">Agents</h1>
@endsection

@section('content')
    {{-- Render del componente Livewire --}}
    <livewire:agents.index />
@endsection
