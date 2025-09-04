@extends('layouts.app')

@section('title', 'Applicants')
@section('content_header_title', 'Applicants')
@section('content_header_subtitle', 'Management')

@section('content_body')
    <div class="card card-primary card-outline">
        <div class="card-header">
            <h3 class="card-title mb-0"><i class="fas fa-user-tag mr-1"></i> Applicants</h3>
        </div>
        <div class="card-body">
            @livewire('applicants.index')
        </div>
    </div>
@endsection
