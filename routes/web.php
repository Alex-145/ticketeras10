<?php

use Illuminate\Support\Facades\Route;
use App\Models\Ticket;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {
    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/agents', fn() => view('agents.index'))->name('agents.index');
    Route::get('/companies', fn() => view('companies.index'))->name('companies.index');
    Route::get('/modules', fn() => view('modules.index'))->name('modules.index');
    Route::get('/categories', fn() => view('categories.index'))->name('categories.index');
    Route::get('/applicants', fn() => view('applicants.index'))->name('applicants.index');
    Route::get('/tickets', fn() => view('tickets.board'))->name('tickets.board');
});

// Portal Applicant (crear ticket)
Route::middleware(['auth', 'verified', 'role:applicant'])
    ->prefix('portal')->name('portal.')->group(function () {
        Route::view('/tickets/create', 'portal.tickets.create')
            ->name('tickets.create');

        // Mostrar ticket (chat) para applicant
        Route::get('/tickets/{ticket}', function (Ticket $ticket) {
            return view('tickets.chat', compact('ticket'));
        })->name('tickets.show');
    });

// Staff/Admin (abrir chat por fuera del portal)
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/tickets/{ticket}/chat', function (Ticket $ticket) {
        return view('tickets.chat', compact('ticket'));
    })->name('tickets.chat');
});
