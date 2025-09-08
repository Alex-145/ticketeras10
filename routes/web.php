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

/* =========================
   Admin/Agent: listado admin
   ========================= */
Route::middleware(['auth', 'verified', 'role:admin|agent'])->group(function () {
    Route::view('/admin/tickets', 'admin.tickets.index')->name('admin.tickets.index');
});

/* =========================
   SOLO ADMIN: Agents
   ========================= */
Route::middleware(['auth', 'verified', 'role:admin'])->group(function () {
    Route::view('/agents', 'agents.index')->name('agents.index');
});

/* =========================
   STAFF (admin|agent): catálogos y board
   ========================= */
Route::middleware(['auth', 'verified', 'role:admin|agent'])->group(function () {
    Route::view('/companies', 'companies.index')->name('companies.index');
    Route::view('/modules', 'modules.index')->name('modules.index');
    Route::view('/categories', 'categories.index')->name('categories.index');
    Route::view('/applicants', 'applicants.index')->name('applicants.index');

    // Board de tickets del staff
    Route::view('/tickets', 'tickets.board')->name('tickets.board');
});

/* =========================
   Portal Applicant
   ========================= */
Route::middleware(['auth', 'verified', 'role:applicant'])
    ->prefix('portal')->name('portal.')->group(function () {

        // LISTA
        Route::view('/tickets', 'portal.tickets.index')->name('tickets.index');

        // CREAR
        Route::view('/tickets/create', 'portal.tickets.create')->name('tickets.create');

        // CHAT / SHOW (misma compañía; opcionalmente permitir staff)
        Route::get('/tickets/{ticket}', function (Ticket $ticket) {
            $ticket->loadMissing('applicant.company', 'module', 'category');

            $user = auth()->user();

            // Si quisieras permitir que un staff también entre por aquí, descomenta:
            // if ($user->hasAnyRole(['admin','agent'])) {
            //     return view('tickets.chat', compact('ticket'));
            // }

            // Applicant del usuario y validación de misma compañía
            $meApplicant = \App\Models\Applicant::with('company')->where('user_id', $user->id)->first();
            $sameCompany = $meApplicant
                && $ticket->applicant
                && $meApplicant->company_id
                && $meApplicant->company_id === $ticket->applicant->company_id;

            abort_unless($sameCompany, 403);

            return view('tickets.chat', compact('ticket'));
        })->name('tickets.show');
    });

/* =========================
   Staff/Admin: abrir chat directo
   ========================= */
Route::middleware(['auth', 'verified', 'role:admin|agent'])->group(function () {
    Route::get('/tickets/{ticket}/chat', function (Ticket $ticket) {
        $ticket->loadMissing('applicant.company', 'module', 'category');
        return view('tickets.chat', compact('ticket'));
    })->name('tickets.chat');
});
