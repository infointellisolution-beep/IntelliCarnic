@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
<section class="page-hero">
    <div class="hero-top">
        <div>
            <div class="hero-kicker"><i class="fa-solid fa-chart-line"></i> Visión General</div>
            <p class="hero-copy">Métricas clave de las ventas de hoy y el estado del negocio.</p>
        </div>
    </div>
</section>

<div class="grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1.5rem; margin-bottom: 2rem;">
    <!-- Ventas Hoy -->
    <article class="stat-card">
        <div class="stat-card-header">
            <div>
                <div class="stat-label">Ventas Hoy</div>
                <div class="stat-value">{{ number_format($ventasHoy, 0) }}</div>
            </div>
            <div class="stat-card-icon blue"><i class="fa-solid fa-receipt"></i></div>
        </div>
        <div class="stat-note">Tickets cobrados en el día</div>
    </article>

    <!-- Caja del Día -->
    <article class="stat-card">
        <div class="stat-card-header">
            <div>
                <div class="stat-label">Caja del Día</div>
                <div class="stat-value">${{ number_format($cajaHoy, 2) }}</div>
            </div>
            <div class="stat-card-icon green"><i class="fa-solid fa-money-bill-wave"></i></div>
        </div>
        <div class="stat-note">Ingresos brutos facturados</div>
    </article>

    <!-- Peso Vendido -->
    <article class="stat-card">
        <div class="stat-card-header">
            <div>
                <div class="stat-label">Cantidad Vendida</div>
                <div class="stat-value">{{ number_format($pesoVendido, 3) }} {{ $settings['unidad_peso'] ?? 'kg' }}</div>
            </div>
            <div class="stat-card-icon orange"><i class="fa-solid fa-weight-scale"></i></div>
        </div>
        <div class="stat-note">Volumen de mercancía despachada</div>
    </article>
</div>

<!-- Gráfica de Ventas -->
<div class="card">
    <div class="hero-kicker" style="margin-bottom: 1rem;"><i class="fa-solid fa-chart-bar"></i> Ventas de la Semana</div>
    <div style="position: relative; height: 350px; width: 100%;">
        <canvas id="ventasChart"></canvas>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const ctx = document.getElementById('ventasChart').getContext('2d');
        const chartData = @json($graficaVentas);
        
        const labels = chartData.map(d => d.fecha);
        const data = chartData.map(d => d.total);

        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Caja del día ($)',
                    data: data,
                    backgroundColor: 'rgba(59, 130, 246, 0.5)',
                    borderColor: 'rgb(59, 130, 246)',
                    borderWidth: 1,
                    borderRadius: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(0, 0, 0, 0.05)'
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    }
                }
            }
        });
    });
</script>
@endsection
