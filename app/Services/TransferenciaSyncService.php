<?php

namespace App\Services;

use App\Models\Transferencia;
use App\Models\Setting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TransferenciaSyncService
{
    protected string $endpoint;
    protected string $apiToken;
    protected int $timeout;

    public function __construct(?string $endpoint = null, ?string $apiToken = null)
    {
        $this->endpoint = rtrim($endpoint ?: Setting::getValue('cloud_sync_endpoint', 'https://intellicarnicsync.intellisolution.net'), '/');
        $this->apiToken = $apiToken ?: Setting::getValue('cloud_sync_token', 'IntelliCarnic_Sync_2026_Key');
        $this->timeout = 10; // segundos
    }

    /**
     * Verificar si la sincronización cloud está habilitada y configurada.
     */
    public function isConfigured(): bool
    {
        return !empty($this->endpoint) && !empty($this->apiToken);
    }

    /**
     * Probar la conexión con el servidor cloud.
     */
    public function testConexion(): array
    {
        if (!$this->isConfigured()) {
            return ['success' => false, 'error' => 'Sincronización cloud no configurada. Ingrese el endpoint y token.'];
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiToken,
                'Accept' => 'application/json',
            ])->withoutVerifying()->timeout($this->timeout)
              ->get($this->endpoint . '/?action=status');

            if ($response->successful()) {
                $data = $response->json();
                return [
                    'success' => true,
                    'message' => 'Conexión exitosa con el servidor cloud.',
                    'server_status' => $data['status'] ?? 'online',
                    'timestamp' => $data['timestamp'] ?? date('Y-m-d H:i:s'),
                ];
            }

            if ($response->status() === 403) {
                return ['success' => false, 'error' => 'Código 403 (Acceso Denegado): El API Token ingresado es incorrecto o está vacío. Verifique el token con el icono del ojo 👁️.'];
            }

            return ['success' => false, 'error' => 'El servidor respondió con código HTTP ' . $response->status()];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => 'No se pudo conectar: ' . $e->getMessage()];
        }
    }

    /**
     * Enviar una transferencia a la nube (Sucursal Origen → Hostinger).
     */
    public function enviarNube(Transferencia $transferencia): array
    {
        if (!$this->isConfigured()) {
            return ['success' => false, 'error' => 'Cloud sync no configurado.', 'offline' => true];
        }

        try {
            $payload = $transferencia->buildPayload();

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiToken,
                'Accept' => 'application/json',
            ])->withoutVerifying()->timeout($this->timeout)
              ->post($this->endpoint . '/?action=enviar', $payload);

            if ($response->successful()) {
                $data = $response->json();

                // Actualizar transferencia con el tipo de sincronización
                $transferencia->update([
                    'tipo_sincronizacion' => 'cloud',
                    'payload_json' => json_encode($payload, JSON_UNESCAPED_UNICODE),
                ]);

                Log::info("Transferencia {$transferencia->folio} enviada a la nube exitosamente.");

                return [
                    'success' => true,
                    'message' => $data['message'] ?? 'Transferencia enviada a la nube.',
                    'folio' => $transferencia->folio,
                ];
            }

            $errorMsg = $response->json()['error'] ?? 'Error HTTP ' . $response->status();
            Log::warning("Error al enviar transferencia {$transferencia->folio} a la nube: {$errorMsg}");

            return ['success' => false, 'error' => $errorMsg, 'offline' => false];

        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::warning("Sin conexión a internet al enviar transferencia {$transferencia->folio}: " . $e->getMessage());
            return ['success' => false, 'error' => 'Sin conexión a internet.', 'offline' => true];
        } catch (\Exception $e) {
            Log::error("Excepción al enviar transferencia {$transferencia->folio}: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage(), 'offline' => false];
        }
    }

    /**
     * Consultar transferencias pendientes dirigidas a esta sucursal (Hostinger → Sucursal Destino).
     */
    public function consultarPendientesNube(string $codigoSucursal): array
    {
        if (!$this->isConfigured()) {
            return ['success' => false, 'error' => 'Cloud sync no configurado.', 'transferencias' => []];
        }

        try {
            $url = $this->endpoint . '/?' . http_build_query([
                'action' => 'pendientes',
                'sucursal' => $codigoSucursal,
            ]);

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiToken,
                'Accept' => 'application/json',
            ])->withoutVerifying()->timeout($this->timeout)->get($url);

            if ($response->successful()) {
                $data = $response->json();
                return [
                    'success' => true,
                    'count' => $data['count'] ?? 0,
                    'transferencias' => $data['transferencias'] ?? [],
                ];
            }

            return ['success' => false, 'error' => 'Error HTTP ' . $response->status(), 'transferencias' => []];

        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage(), 'transferencias' => []];
        }
    }

    /**
     * Notificar al cloud que la transferencia fue recibida.
     */
    public function marcarRecibidaNube(string $folio, string $usuarioRecepcion): array
    {
        if (!$this->isConfigured()) {
            return ['success' => false, 'error' => 'Cloud sync no configurado.'];
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiToken,
                'Accept' => 'application/json',
            ])->withoutVerifying()->timeout($this->timeout)
              ->post($this->endpoint . '/?action=marcar-recibida', [
                  'folio' => $folio,
                  'usuario_recepcion' => $usuarioRecepcion,
              ]);

            if ($response->successful()) {
                return ['success' => true, 'message' => 'Transferencia marcada como recibida en la nube.'];
            }

            return ['success' => false, 'error' => $response->json()['error'] ?? 'Error HTTP ' . $response->status()];

        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Cancelar una transferencia en la nube.
     */
    public function cancelarNube(string $folio): array
    {
        if (!$this->isConfigured()) {
            return ['success' => false, 'error' => 'Cloud sync no configurado.'];
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiToken,
                'Accept' => 'application/json',
            ])->withoutVerifying()->timeout($this->timeout)
              ->post($this->endpoint . '/?action=cancelar', [
                  'folio' => $folio,
              ]);

            if ($response->successful()) {
                return ['success' => true, 'message' => 'Transferencia cancelada en la nube.'];
            }

            return ['success' => false, 'error' => $response->json()['error'] ?? 'Error'];

        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Generar archivo .trn descargable como respaldo offline.
     */
    public function generarArchivoTrn(Transferencia $transferencia): string
    {
        $payload = $transferencia->buildPayload();
        $payload['_trn_version'] = '1.0';
        $payload['_generated_at'] = now()->toIso8601String();

        return json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }

    /**
     * Parsear y validar un archivo .trn subido manualmente.
     */
    public function procesarArchivoTrn(string $contenido): array
    {
        $data = json_decode($contenido, true);

        if (!$data || !isset($data['folio']) || !isset($data['payload'])) {
            return ['success' => false, 'error' => 'Archivo .trn inválido o corrupto.'];
        }

        return [
            'success' => true,
            'data' => $data,
        ];
    }

    /**
     * Consultar todas las transferencias alojadas en el buzón cloud para todas las sucursales.
     */
    public function consultarTodasEnNube(): array
    {
        if (!$this->isConfigured()) {
            return ['success' => false, 'error' => 'Cloud sync no configurado.', 'transferencias' => []];
        }

        $sucursales = \App\Models\Sucursal::all();
        $todas = [];
        $seenFolios = [];

        foreach ($sucursales as $sucursal) {
            $res = $this->consultarPendientesNube($sucursal->codigo);
            if ($res['success'] && !empty($res['transferencias'])) {
                foreach ($res['transferencias'] as $trn) {
                    if (!in_array($trn['folio'], $seenFolios)) {
                        $seenFolios[] = $trn['folio'];
                        // Añadir nombre de sucursal origen y destino para visualización clara
                        $origenObj = $sucursales->firstWhere('codigo', $trn['sucursal_origen'] ?? '');
                        $destinoObj = $sucursales->firstWhere('codigo', $trn['sucursal_destino'] ?? '');
                        $trn['sucursal_origen_nombre'] = $origenObj ? $origenObj->nombre : ($trn['sucursal_origen'] ?? 'Origen');
                        $trn['sucursal_destino_nombre'] = $destinoObj ? $destinoObj->nombre : ($trn['sucursal_destino'] ?? 'Destino');
                        $todas[] = $trn;
                    }
                }
            }
        }

        return [
            'success' => true,
            'count' => count($todas),
            'transferencias' => $todas,
        ];
    }
}
