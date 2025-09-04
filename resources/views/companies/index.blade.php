{{-- resources/views/companies/index.blade.php --}}
@extends('layouts.app')
@section('title', 'Companies')
@section('content_header_title', 'Companies')
@section('content_header_subtitle', 'Management')

@section('content_body')
    <div class="card card-primary card-outline">
        <div class="card-header">
            <h3 class="card-title mb-0"><i class="fas fa-building mr-1"></i> Companies</h3>
        </div>
        <div class="card-body">
            @livewire('companies.index') {{-- <== este nombre debe coincidir con App\Livewire\Companies\Index --}}
        </div>
    </div>
@endsection
@push('js')
    <script>
        function companiesCtxMenu() {
            return {
                visible: false,
                x: 0,
                y: 0,
                selectedId: null,
                isActive: false,
                busy: false,
                get style() {
                    return `position:fixed; left:${this.x}px; top:${this.y}px; z-index:1050;`;
                },
                boot() {
                    // Cerrar al desplazar/resize para que no quede colgado
                    window.addEventListener('scroll', () => this.close(), true);
                    window.addEventListener('resize', () => this.close());
                    // Clic en fondo para cerrar
                    document.addEventListener('click', (e) => {
                        if (this.visible && !e.target.closest('.dropdown-menu')) this.close();
                    });
                },
                open(evt, id, active) {
                    this.selectedId = id;
                    this.isActive = !!active;

                    // Posiciona con margen para que no se salga del viewport
                    const menuWidth = 220,
                        menuHeight = 150;
                    const vw = window.innerWidth,
                        vh = window.innerHeight;
                    let px = evt.clientX,
                        py = evt.clientY;
                    if (px + menuWidth > vw) px = vw - menuWidth - 8;
                    if (py + menuHeight > vh) py = vh - menuHeight - 8;

                    this.x = Math.max(8, px);
                    this.y = Math.max(8, py);
                    this.visible = true;
                },
                close() {
                    this.visible = false;
                    this.busy = false;
                },
                async toggle() {
                    if (!this.selectedId) return;
                    this.busy = true;
                    try {
                        await this.$wire.toggleActive(this.selectedId);
                    } finally {
                        this.close();
                    }
                },
                async edit() {
                    if (!this.selectedId) return;
                    this.busy = true;
                    try {
                        await this.$wire.openEdit(this.selectedId);
                    } finally {
                        this.close();
                    }
                },
                async del() {
                    if (!this.selectedId) return;
                    this.busy = true;
                    try {
                        await this.$wire.confirmDelete(this.selectedId);
                    } finally {
                        this.close();
                    }
                },
            }
        }
    </script>
@endpush
