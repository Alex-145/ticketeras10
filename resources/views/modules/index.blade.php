@extends('layouts.app')

@section('title', 'Modules')
@section('content_header_title', 'Modules')
@section('content_header_subtitle', 'Management')

@section('content_body')
    <div class="card card-primary card-outline">
        <div class="card-header">
            <h3 class="card-title mb-0"><i class="fas fa-puzzle-piece mr-1"></i> Modules</h3>
        </div>
        <div class="card-body">
            @livewire('modules.index')
        </div>
    </div>
@endsection
