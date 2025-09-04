@extends('layouts.app')

@section('title', 'Categories')
@section('content_header_title', 'Categories')
@section('content_header_subtitle', 'Management')

@section('content_body')
    <div class="card card-primary card-outline">
        <div class="card-header">
            <h3 class="card-title mb-0"><i class="fas fa-tags mr-1"></i> Categories</h3>
        </div>
        <div class="card-body">
            @livewire('categories.index')
        </div>
    </div>
@endsection
