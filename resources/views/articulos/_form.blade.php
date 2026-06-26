@php
    $isEdit = $method === 'PUT';
@endphp

@if ($errors->any())
    <div class="card" style="margin-bottom: 1rem; border-color: rgba(239, 68, 68, 0.35); background: #fef2f2; color: #7f1d1d;">
        <strong>Revisa los campos del formulario.</strong>
        <ul style="margin: 0.5rem 0 0; padding-left: 1.2rem;">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<form action="{{ $action }}" method="POST" class="card" style="padding: 0;">
    @csrf
    @if ($isEdit)
        @method('PUT')
    @endif

    <div style="padding: 1.5rem; border-bottom: 1px solid var(--border-color);">
        <div class="hero-kicker"><i class="fa-solid fa-box-open"></i> {{ $pageTitle }}</div>
        <h2 class="hero-title" style="font-size: 1.5rem; margin-top: 0.25rem;">{{ $articulo->exists ? $articulo->descripcion : 'Registrar nuevo producto' }}</h2>
        <p class="page-subtitle" style="margin-bottom: 0;">Completa los datos para dar de alta, actualizar o corregir el catálogo.</p>
    </div>

    <div style="padding: 1.5rem;">
        @include('articulos._fields', ['modalArticulo' => $articulo, 'familias' => $familias])
    </div>

    <div style="padding: 1.25rem 1.5rem; border-top: 1px solid var(--border-color); display: flex; justify-content: space-between; gap: 1rem; align-items: center; flex-wrap: wrap;">
        <div style="color: var(--text-muted); font-size: 0.9rem;">El campo PVP puede calcularse automáticamente si lo dejas vacío.</div>
        <div class="flex-gap" style="width: auto;">
            <a href="{{ route('articulos.index') }}" class="btn-modern btn-secondary" style="width: auto; text-decoration: none; display: inline-flex; align-items: center; justify-content: center;">Cancelar</a>
            <button type="submit" class="btn-modern btn-accent" style="width: auto;">{{ $submitLabel }}</button>
        </div>
    </div>
</form>