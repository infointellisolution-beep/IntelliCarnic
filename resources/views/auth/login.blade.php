@extends('layouts.app')

@section('content')
<div class="login-container">
    <div class="glass-panel">
        <i class="fa-solid fa-cube" style="font-size: 3rem; color: var(--primary); margin-bottom: 1rem;"></i>
        <h2>Bienvenido</h2>
        <p>Inicia sesión en tu nuevo TPV</p>

        @if ($errors->any())
            <div class="card" style="margin-bottom: 1rem; border-color: rgba(239, 68, 68, 0.35); background: #fef2f2; color: #7f1d1d; text-align: left;">
                {{ $errors->first() }}
            </div>
        @endif

        <form action="{{ route('login.post') }}" method="POST">
            @csrf
            <div class="input-group">
                <label for="email">Usuario o Email</label>
                <input type="text" id="email" name="email" class="input-modern" placeholder="tunombre@gmail.com" value="{{ old('email') }}" required>
            </div>
            
            <div class="input-group">
                <label for="password">Contraseña</label>
                <input type="password" id="password" name="password" class="input-modern" placeholder="••••••••" required>
            </div>

            <button type="submit" class="btn-modern">Entrar al Sistema</button>
        </form>
    </div>
</div>
@endsection
