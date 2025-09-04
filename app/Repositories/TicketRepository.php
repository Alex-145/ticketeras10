<?php

namespace App\Repositories;

use App\Models\Ticket;
use App\Support\DbDialect;

class TicketRepository
{
    public function searchBoard(?string $term)
    {
        $like = DbDialect::like();

        return Ticket::with(['applicant.company'])
            ->when($term !== null && $term !== '', function ($q) use ($term, $like) {
                $t = "%$term%";
                $q->where(function ($w) use ($t, $like) {
                    $w->where('description', $like, $t)
                        ->orWhere('title', $like, $t)
                        ->orWhere('number', $like, $t)
                        ->orWhereHas('applicant', fn($a) => $a->where('name', $like, $t))
                        ->orWhereHas('company', fn($c) => $c->where('name', $like, $t));
                });
            })
            ->orderByDesc('id')
            ->get();
    }
}
