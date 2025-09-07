<?php
// routes/channels.php

use App\Models\Ticket;
use App\Models\Applicant;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Log;

Broadcast::channel('tickets.{ticketId}', function ($user, $ticketId) {
    $ticket = Ticket::with('applicant.company')->find($ticketId);
    if (!$ticket) {
        Log::warning('channels:tickets auth - ticket not found', ['ticketId' => $ticketId]);
        return false;
    }

    // Staff siempre entra
    if ($user->hasAnyRole(['admin', 'agent'])) {
        return ['id' => $user->id, 'type' => 'staff'];
    }

    // Applicant por misma compañía
    $meApplicant = Applicant::with('company')->where('user_id', $user->id)->first();
    if (!$meApplicant) {
        Log::info('channels:tickets auth - not applicant', ['user_id' => $user->id]);
        return false;
    }

    $sameCompany = $ticket->applicant
        && $meApplicant->company_id
        && $meApplicant->company_id === $ticket->applicant->company_id;

    if ($sameCompany) {
        return ['id' => $meApplicant->id, 'type' => 'applicant'];
    }

    Log::info('channels:tickets auth - different company', [
        'user_applicant_id' => $meApplicant->id ?? null,
        'user_company_id'   => $meApplicant->company_id ?? null,
        'ticket_company_id' => $ticket->applicant->company_id ?? null,
    ]);

    return false;
});
