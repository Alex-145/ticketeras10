@extends('layouts.app')

@section('title', 'Tickets')
@section('content_header_title', 'Tickets Board')
@section('content_header_subtitle', 'To Do · Doing · Done')
{{-- En una vista específica --}}


@section('content_body')
    <div class="card card-primary card-outline">
        <div class="card-header">
            <h3 class="card-title mb-0"><i class="fas fa-columns mr-1"></i> Kanban</h3>
        </div>
        <div class="card-body">
            @livewire('tickets.board')
        </div>
    </div>
@endsection


@push('js')
    @vite('resources/js/app.js')
    <script>
        function ticketBoard() {
            return {
                draggingId: null,

                boot() {
                    // Verifica echo
                    if (!window.Echo) {
                        console.warn('[Realtime] Echo no está inicializado');
                        this.$wire.clientEvent('echo.missing');
                        return;
                    }

                    // Logs de conexión Reverb (compatibles con protocolo Pusher)
                    const pusher = window.Echo.connector.pusher;
                    if (pusher && pusher.connection) {
                        pusher.connection.bind('state_change', (states) => {
                            console.log('[Realtime] state_change', states);
                            this.$wire.clientEvent('echo.state_change', states);
                        });
                        pusher.connection.bind('connected', () => {
                            console.log('[Realtime] connected');
                            this.$wire.clientEvent('echo.connected');
                        });
                        pusher.connection.bind('disconnected', () => {
                            console.warn('[Realtime] disconnected');
                            this.$wire.clientEvent('echo.disconnected');
                        });
                        pusher.connection.bind('error', (err) => {
                            console.error('[Realtime] error', err);
                            this.$wire.clientEvent('echo.error', {
                                message: err?.error || err
                            });
                        });
                    }

                    // Suscripción al canal y log de suscripción
                    const ch = window.Echo.channel('tickets');

                    // Pusher expone eventos especiales de suscripción:
                    const raw = window.Echo.connector.pusher?.channel('tickets');
                    raw?.bind('pusher:subscription_succeeded', () => {
                        console.log('[Realtime] subscription_succeeded:tickets');
                        this.$wire.clientEvent('echo.subscription_succeeded', {
                            channel: 'tickets'
                        });
                    });
                    raw?.bind('pusher:subscription_error', (status) => {
                        console.error('[Realtime] subscription_error:tickets', status);
                        this.$wire.clientEvent('echo.subscription_error', {
                            channel: 'tickets',
                            status
                        });
                    });

                    // Evento de negocio: cuando alguien mueve un ticket
                    ch.listen('.TicketStatusChanged', (e) => {
                        console.log('[Realtime] TicketStatusChanged', e);
                        this.$wire.clientEvent('event.TicketStatusChanged', e);
                        // refresca el board livewire
                        this.$wire.refreshBoard();
                    });
                },

                onDragStart(evt, id) {
                    this.draggingId = id;
                    evt.dataTransfer.effectAllowed = 'move';
                },

                onDrop(evt, toCol) {
                    evt.preventDefault();
                    if (!this.draggingId) return;
                    this.$wire.moveTicket(this.draggingId, toCol);
                    this.draggingId = null;
                },
            }
        }
    </script>
@endpush
