<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('tickets.{ticketId}', function ($user, $ticketId) {
    $ticket = \App\Models\Ticket::find($ticketId);
    if (!$ticket) return false;

    // Staff autenticado (admin o agent)
    if (method_exists($user, 'hasRole') && ($user->hasRole('admin') || $user->hasRole('agent'))) {
        return ['id' => $user->id, 'name' => $user->name];
    }

    // Applicant dueño del ticket
    if ($ticket->applicant_id && $user) {
        $applicant = \App\Models\Applicant::where('user_id', $user->id)->first();
        if ($applicant && $applicant->id == $ticket->applicant_id) {
            return ['id' => $user->id, 'name' => $user->name];
        }
    }

    return false;
});
