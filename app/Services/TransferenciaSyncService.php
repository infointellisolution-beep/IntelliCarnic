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
        $ep = $endpoint ?: Setting::getValue('cloud_sync_endpoint', 'https://intellicarnicsync.intellisolution.net');
        $tk = $apiToken ?: Setting::getValue('cloud_sync_token', 'IntelliCarnic_Sync_2026_Key');

        $this->endpoint = rtrim(trim((string)$ep), '/');
        $this->apiToken = trim(trim((string)$tk), '"\'');
        $this->timeout = 10; // segundos
    }

    /**
     * Cabeceras estándar simulando un navegador moderno completo para evitar bloqueos de ModSecurity/WAF.
     */
    protected function defaultHeaders(bool $withAuth = true): array
    {
        $headers = [
            'Accept' => 'application/json, text/plain, */*',
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36',
            'Accept-Language' => 'es-ES,es;q=0.9,en;q=0.8',
        ];

        if ($withAuth && !empty($this->apiToken)) {
            $headers['Authorization'] = 'Bearer ' . $this->apiToken;
        }

        return $headers;
    }

    /**
     * Motor de peticiones HTTP con fallback automático (Laravel Http / cURL -> PHP Native Streams).
     * Esto garantiza funcionamiento incluso si cURL/OpenSSL en alguna laptop es filtrado por WAFs de Hostinger.
     */
    protected function request(string $method, string $url, ?array $data = null, bool $withAuth = true): array
    {
        $headers = $this->defaultHeaders($withAuth);

        // Intento 1: Laravel Http Client (cURL)
        try {
            $client = Http::withHeaders($headers)
                ->withoutVerifying()
                ->timeout($this->timeout);

            $response = (strtoupper($method) === 'POST')
                ? $client->post($url, $data ?? [])
                : $client->get($url);

            // Si responde exitosamente o con 401 (token inválido legítimo), retornar de inmediato
            if ($response->successful() || $response->status() === 401) {
                return [
                    'ok' => $response->successful(),
                    'status' => $response->status(),
                    'data' => $response->json() ?? [],
                    'body' => $response->body(),
                ];
            }
        } catch (\Throwable $e) {
            // Continuar con fallback
        }

        // Intento 2: Fallback con PHP Native Streams (file_get_contents con contexto seguro)
        try {
            $streamHeaders = [];
            foreach ($headers as $k => $v) {
                $streamHeaders[] = "{$k}: {$v}";
            }
            if (strtoupper($method) === 'POST' && $data !== null) {
                $streamHeaders[] = 'Content-Type: application/json';
            }

            $opts = [
                'http' => [
                    'method' => strtoupper($method),
                    'header' => implode("\r\n", $streamHeaders) . "\r\n",
                    'timeout' => $this->timeout,
                    'ignore_errors' => true,
                ],
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                ]
            ];

            if (strtoupper($method) === 'POST' && $data !== null) {
                $opts['http']['content'] = json_encode($data);
            }

            $context = stream_context_create($opts);
            $raw = @file_get_contents($url, false, $context);

            $statusCode = 0;
            if (isset($http_response_header) && is_array($http_response_header)) {
                if (preg_match('#HTTP/\S+\s+(\d+)#i', $http_response_header[0], $matches)) {
                    $statusCode = (int)$matches[1];
                }
            }

            $jsonData = json_decode((string)$raw, true) ?? [];

            return [
                'ok' => ($statusCode >= 200 && $statusCode < 300),
                'status' => $statusCode,
                'data' => $jsonData,
                'body' => (string)$raw,
            ];
        } catch (\Throwable $e) {
            return [
                'ok' => false,
                'status' => 0,
                'data' => [],
                'body' => $e->getMessage(),
                'error' => $e->getMessage(),
            ];
        }
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
            // Paso 1: verificar que el servidor esté en línea (endpoint público, sin token)
            $statusRes = $this->request('GET', $this->endpoint . '/?action=status', null, false);

            if (!$statusRes['ok']) {
                $code = $statusRes['status'] ?: 'Sin respuesta';
                return ['success' => false, 'error' => "El servidor no está accesible (HTTP {$code}). Verifique la URL o la conexión a internet."];
            }

            // Paso 2: verificar el token con una petición autenticada (action=pendientes)
            $tokenRes = $this->request('GET', $this->endpoint . '/?action=pendientes&sucursal=TEST_PING', null, true);

            if ($tokenRes['status'] === 401) {
                return ['success' => false, 'error' => 'Token incorrecto (401 No Autorizado). Verifique que el API Token sea exactamente: IntelliCarnic_Sync_2026_Key'];
            }

            $data = $statusRes['data'];
            return [
                'success' => true,
                'message' => 'Conexión exitosa con el servidor cloud.',
                'server_status' => $data['status'] ?? 'online',
                'timestamp' => $data['timestamp'] ?? date('Y-m-d H:i:s'),
            ];

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
            $res = $this->request('POST', $this->endpoint . '/?action=enviar', $payload, true);

            if ($res['ok']) {
                $data = $res['data'];

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

            $errorMsg = $res['data']['error'] ?? 'Error HTTP ' . ($res['status'] ?: 'Desconocido');
            Log::warning("Error al enviar transferencia {$transferencia->folio} a la nube: {$errorMsg}");

            return ['success' => false, 'error' => $errorMsg, 'offline' => false];

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

            $res = $this->request('GET', $url, null, true);

            if ($res['ok']) {
                $data = $res['data'];
                return [
                    'success' => true,
                    'count' => $data['count'] ?? 0,
                    'transferencias' => $data['transferencias'] ?? [],
                ];
            }

            return ['success' => false, 'error' => $res['data']['error'] ?? ('Error HTTP ' . $res['status']), 'transferencias' => []];

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
            $res = $this->request('POST', $this->endpoint . '/?action=marcar-recibida', [
                'folio' => $folio,
                'usuario_recepcion' => $usuarioRecepcion,
            ], true);

            if ($res['ok']) {
                return ['success' => true, 'message' => 'Transferencia marcada como recibida en la nube.'];
            }

            return ['success' => false, 'error' => $res['data']['error'] ?? ('Error HTTP ' . $res['status'])];

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
            $res = $this->request('POST', $this->endpoint . '/?action=cancelar', [
                'folio' => $folio,
            ], true);

            if ($res['ok']) {
                return ['success' => true, 'message' => 'Transferencia cancelada en la nube.'];
            }

            return ['success' => false, 'error' => $res['data']['error'] ?? ('Error HTTP ' . $res['status'])];

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
