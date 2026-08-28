<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\UploadedFile;

class DatabaseBackupService
{
    protected string $backupDir;

    public function __construct()
    {
        $this->backupDir = storage_path('app/backups');
        if (!File::exists($this->backupDir)) {
            File::makeDirectory($this->backupDir, 0755, true);
        }
    }

    /**
     * Generar un respaldo completo .sql de toda la base de datos (estructura y datos).
     */
    public function createBackup(string $prefix = 'backup_intellicarnic_'): array
    {
        $driver = DB::connection()->getDriverName();
        $timestamp = date('Y-m-d_H-i-s');
        $filename = "{$prefix}{$timestamp}.sql";
        $filepath = $this->backupDir . DIRECTORY_SEPARATOR . $filename;

        $sql = "-- ========================================================\n";
        $sql .= "-- IntelliCarnic - Respaldo Integral de Base de Datos\n";
        $sql .= "-- Fecha de Generación: " . date('Y-m-d H:i:s') . "\n";
        $sql .= "-- Motor / Driver: {$driver}\n";
        $sql .= "-- ========================================================\n\n";

        if ($driver === 'sqlite') {
            $sql .= "PRAGMA foreign_keys = OFF;\n\n";

            // Obtener todas las tablas de SQLite
            $tables = DB::select("SELECT name, sql FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%' ORDER BY name");

            foreach ($tables as $table) {
                $tableName = $table->name;
                $createSql = $table->sql;

                if (in_array($tableName, ['sqlite_sequence', 'sqlite_stat1'])) {
                    continue;
                }

                $sql .= "-- --------------------------------------------------------\n";
                $sql .= "-- Estructura y Registros de tabla: {$tableName}\n";
                $sql .= "-- --------------------------------------------------------\n";
                $sql .= "DROP TABLE IF EXISTS \"{$tableName}\";\n";
                $sql .= $createSql . ";\n\n";

                // Volcar datos
                $rows = DB::table($tableName)->get();
                if ($rows->count() > 0) {
                    foreach ($rows->chunk(100) as $chunk) {
                        foreach ($chunk as $row) {
                            $rowArray = (array) $row;
                            $columns = array_keys($rowArray);
                            $escapedColumns = array_map(fn($col) => "\"{$col}\"", $columns);

                            $escapedValues = array_map(function ($val) {
                                if (is_null($val)) return 'NULL';
                                if (is_numeric($val) && !is_string($val)) return $val;
                                return "'" . str_replace("'", "''", (string) $val) . "'";
                            }, array_values($rowArray));

                            $sql .= "INSERT INTO \"{$tableName}\" (" . implode(', ', $escapedColumns) . ") VALUES (" . implode(', ', $escapedValues) . ");\n";
                        }
                    }
                    $sql .= "\n";
                }
            }

            // Índices adicionales
            $indexes = DB::select("SELECT sql FROM sqlite_master WHERE type='index' AND sql IS NOT NULL AND name NOT LIKE 'sqlite_%'");
            if (!empty($indexes)) {
                $sql .= "-- --------------------------------------------------------\n";
                $sql .= "-- Índices y Triggers adicionales\n";
                $sql .= "-- --------------------------------------------------------\n";
                foreach ($indexes as $idx) {
                    $sql .= $idx->sql . ";\n";
                }
                $sql .= "\n";
            }

            $sql .= "PRAGMA foreign_keys = ON;\n";
        } else {
            // MySQL / MariaDB Dump
            $sql .= "SET FOREIGN_KEY_CHECKS=0;\nSET SQL_MODE = \"NO_AUTO_VALUE_ON_ZERO\";\nSTART TRANSACTION;\n\n";

            $tables = DB::select('SHOW TABLES');
            foreach ($tables as $table) {
                $arr = (array)$table;
                $tableName = reset($arr);

                $createResult = DB::select("SHOW CREATE TABLE `{$tableName}`");
                $createSql = ((array)$createResult[0])['Create Table'] ?? '';

                $sql .= "DROP TABLE IF EXISTS `{$tableName}`;\n";
                $sql .= $createSql . ";\n\n";

                $rows = DB::table($tableName)->get();
                if ($rows->count() > 0) {
                    foreach ($rows->chunk(100) as $chunk) {
                        foreach ($chunk as $row) {
                            $rowArray = (array) $row;
                            $columns = array_keys($rowArray);
                            $escapedColumns = array_map(fn($col) => "`{$col}`", $columns);

                            $escapedValues = array_map(function ($val) {
                                if (is_null($val)) return 'NULL';
                                if (is_numeric($val) && !is_string($val)) return $val;
                                return "'" . addslashes((string) $val) . "'";
                            }, array_values($rowArray));

                            $sql .= "INSERT INTO `{$tableName}` (" . implode(', ', $escapedColumns) . ") VALUES (" . implode(', ', $escapedValues) . ");\n";
                        }
                    }
                    $sql .= "\n";
                }
            }

            $sql .= "COMMIT;\nSET FOREIGN_KEY_CHECKS=1;\n";
        }

        File::put($filepath, $sql);
        $sizeBytes = File::size($filepath);

        Log::info("Respaldo de base de datos generado exitosamente: {$filename} ({$sizeBytes} bytes)");

        return [
            'success' => true,
            'filename' => $filename,
            'filepath' => $filepath,
            'size' => $this->formatBytes($sizeBytes),
            'size_bytes' => $sizeBytes,
            'created_at' => date('d/m/Y H:i:s'),
            'timestamp' => $timestamp,
        ];
    }

    /**
     * Listar todos los respaldos existentes en el almacenamiento local.
     */
    public function listBackups(): array
    {
        if (!File::exists($this->backupDir)) {
            return [];
        }

        $files = File::files($this->backupDir);
        $backups = [];

        foreach ($files as $file) {
            $ext = strtolower($file->getExtension());
            if ($ext === 'sql' || $ext === 'sqlite') {
                $fname = $file->getFilename();
                $isEmergency = str_starts_with($fname, 'pre_restore') || str_starts_with($fname, 'pre_reset');
                $isAuto = str_starts_with($fname, 'auto_backup');

                $tipo = 'manual';
                if ($isEmergency) {
                    $tipo = 'preventivo';
                } elseif ($isAuto) {
                    $tipo = 'automatico';
                }

                $backups[] = [
                    'filename' => $fname,
                    'size' => $this->formatBytes($file->getSize()),
                    'size_bytes' => $file->getSize(),
                    'created_at' => date('d/m/Y H:i:s', $file->getMTime()),
                    'timestamp' => $file->getMTime(),
                    'tipo' => $tipo,
                    'is_emergency' => $isEmergency,
                    'is_auto' => $isAuto,
                ];
            }
        }

        usort($backups, fn($a, $b) => $b['timestamp'] <=> $a['timestamp']);
        return $backups;
    }

    /**
     * Obtener ruta absoluta de un respaldo específico para descarga o lectura.
     */
    public function getBackupPath(string $filename): ?string
    {
        // Sanitizar el nombre del archivo para prevenir path traversal
        $filename = basename($filename);
        $filepath = $this->backupDir . DIRECTORY_SEPARATOR . $filename;

        return File::exists($filepath) ? $filepath : null;
    }

    /**
     * Eliminar una copia de seguridad específica.
     */
    public function deleteBackup(string $filename): bool
    {
        $path = $this->getBackupPath($filename);
        if ($path && File::exists($path)) {
            return File::delete($path);
        }
        return false;
    }

    /**
     * Restaurar la base de datos a partir de contenido SQL.
     */
    public function restoreFromSql(string $sqlContent): array
    {
        // 1. Crear respaldo preventivo de emergencia antes de cualquier cambio
        try {
            $this->createBackup('pre_restore_backup_');
        } catch (\Throwable $e) {
            Log::warning("No se pudo crear respaldo preventivo: " . $e->getMessage());
        }

        $driver = DB::connection()->getDriverName();
        $pdo = DB::connection()->getPdo();

        try {
            if ($driver === 'sqlite') {
                $pdo->exec("PRAGMA foreign_keys = OFF;");
                $pdo->exec($sqlContent);
                $pdo->exec("PRAGMA foreign_keys = ON;");
            } else {
                $pdo->exec("SET FOREIGN_KEY_CHECKS=0;");
                $pdo->exec($sqlContent);
                $pdo->exec("SET FOREIGN_KEY_CHECKS=1;");
            }

            // Limpiar cachés de la aplicación
            try {
                \Illuminate\Support\Facades\Artisan::call('cache:clear');
                \Illuminate\Support\Facades\Artisan::call('view:clear');
            } catch (\Throwable $e) {
                // Silencioso si artisan no puede limpiar en este contexto
            }

            Log::info("Base de datos restaurada exitosamente.");

            return [
                'success' => true,
                'message' => 'La base de datos fue restaurada íntegramente con éxito.',
            ];
        } catch (\Throwable $e) {
            Log::error("Error al restaurar base de datos: " . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Error durante la restauración SQL: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Restaurar desde un archivo subido vía formulario multipart.
     */
    public function restoreFromUploadedFile(UploadedFile $file): array
    {
        $ext = strtolower($file->getClientOriginalExtension());
        if ($ext !== 'sql') {
            return ['success' => false, 'error' => 'El archivo debe tener formato .sql válido.'];
        }

        $content = file_get_contents($file->getRealPath());
        if (empty($content)) {
            return ['success' => false, 'error' => 'El archivo proporcionado está vacío.'];
        }

        return $this->restoreFromSql($content);
    }

    /**
     * Restablecer la base de datos a 0 (Limpiar datos operativos y catálogo conservando admin y settings).
     */
    public function resetDatabaseToZero(): array
    {
        // 1. Crear respaldo preventivo de emergencia
        try {
            $this->createBackup('pre_reset_backup_');
        } catch (\Throwable $e) {
            Log::warning("No se pudo crear respaldo preventivo pre-reset: " . $e->getMessage());
        }

        $driver = DB::connection()->getDriverName();
        $pdo = DB::connection()->getPdo();

        try {
            if ($driver === 'sqlite') {
                $pdo->exec("PRAGMA foreign_keys = OFF;");
            } else {
                $pdo->exec("SET FOREIGN_KEY_CHECKS=0;");
            }

            // Tablas operativas, transaccionales y de catálogo a vaciar
            $tablesToTruncate = [
                'venta_detalles',
                'ventas',
                'devolucion_detalles',
                'devoluciones',
                'compra_detalles',
                'compras',
                'ajustes_inventario',
                'transferencia_detalles',
                'transferencias',
                'caja_movimientos',
                'caja_sesiones',
                'abonos',
                'articulos',
                'familias',
                'clientes',
                'proveedores',
            ];

            foreach ($tablesToTruncate as $tbl) {
                if ($driver === 'sqlite') {
                    $pdo->exec("DELETE FROM \"{$tbl}\";");
                    $pdo->exec("DELETE FROM sqlite_sequence WHERE name='{$tbl}';");
                } else {
                    $pdo->exec("TRUNCATE TABLE `{$tbl}`;");
                }
            }

            // Crear cliente por defecto "Público General"
            DB::table('clientes')->insert([
                'nombre' => 'Público General',
                'telefono' => '',
                'limite_credito' => 0,
                'saldo_deudor' => 0,
                'estado' => 'activo',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Limpiar usuarios que no sean admin@gmail.com
            DB::table('users')->where('email', '!=', 'admin@gmail.com')->delete();

            if ($driver === 'sqlite') {
                $pdo->exec("PRAGMA foreign_keys = ON;");
            } else {
                $pdo->exec("SET FOREIGN_KEY_CHECKS=1;");
            }

            // Limpiar cachés
            try {
                \Illuminate\Support\Facades\Artisan::call('cache:clear');
                \Illuminate\Support\Facades\Artisan::call('view:clear');
            } catch (\Throwable $e) {}

            Log::info("Base de datos reseteada a 0 exitosamente.");

            return [
                'success' => true,
                'message' => 'La base de datos fue reseteada a 0 exitosamente. El sistema está limpio para iniciar operaciones desde cero.',
            ];
        } catch (\Throwable $e) {
            Log::error("Error al resetear base de datos: " . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Error durante el reseteo: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Obtener estadísticas generales de la base de datos.
     */
    public function getDatabaseStats(): array
    {
        $driver = DB::connection()->getDriverName();
        $totalTables = 0;
        $totalRows = 0;
        $dbSize = '—';

        if ($driver === 'sqlite') {
            $tables = DB::select("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'");
            $totalTables = count($tables);
            foreach ($tables as $t) {
                $totalRows += DB::table($t->name)->count();
            }
            $dbPath = config('database.connections.sqlite.database');
            if (File::exists($dbPath)) {
                $dbSize = $this->formatBytes(File::size($dbPath));
            }
        } else {
            $tables = DB::select('SHOW TABLES');
            $totalTables = count($tables);
            foreach ($tables as $table) {
                $arr = (array)$table;
                $tableName = reset($arr);
                $totalRows += DB::table($tableName)->count();
            }
        }

        return [
            'driver' => strtoupper($driver),
            'total_tables' => $totalTables,
            'total_rows' => $totalRows,
            'db_size' => $dbSize,
            'backups_count' => count($this->listBackups()),
        ];
    }

    /**
     * Verificar y ejecutar respaldo automático si corresponde según la frecuencia configurada.
     */
    public function checkAndRunAutomaticBackup(string $trigger = 'scheduled'): ?array
    {
        $enabled = \App\Models\Setting::getValue('backup_auto_enabled', '1');
        if ($enabled !== '1' && $enabled !== true) {
            return null;
        }

        $frecuencia = \App\Models\Setting::getValue('backup_frecuencia', 'diario');

        // Si el trigger es cierre_caja pero la frecuencia configurada no es esa, o viceversa:
        if ($trigger === 'cierre_caja' && $frecuencia !== 'cierre_caja') {
            return null;
        }

        if ($trigger === 'scheduled' && $frecuencia === 'cierre_caja') {
            return null;
        }

        $ultimoAutoStr = \App\Models\Setting::getValue('backup_ultimo_auto', null);
        $debeEjecutar = false;

        if (!$ultimoAutoStr) {
            $debeEjecutar = true;
        } else {
            $ultimoTime = strtotime($ultimoAutoStr);
            $ahora = time();
            $segundosTranscurridos = $ahora - $ultimoTime;

            if ($frecuencia === 'semanal' && $segundosTranscurridos >= (86400 * 7)) {
                $debeEjecutar = true;
            } elseif ($frecuencia === 'quincenal' && $segundosTranscurridos >= (86400 * 15)) {
                $debeEjecutar = true;
            } elseif ($frecuencia === 'mensual' && $segundosTranscurridos >= (86400 * 30)) {
                $debeEjecutar = true;
            } elseif ($frecuencia === 'diario' && $segundosTranscurridos >= 86400) {
                $debeEjecutar = true;
            } elseif ($trigger === 'cierre_caja') {
                $debeEjecutar = true;
            }
        }

        if (!$debeEjecutar) {
            return null;
        }

        $result = $this->createBackup('auto_backup_');
        if ($result['success']) {
            \App\Models\Setting::setValue('backup_ultimo_auto', date('Y-m-d H:i:s'));
            $this->purgeOldAutoBackups();
        }

        return $result;
    }

    /**
     * Purgar respaldos automáticos antiguos según la cuota de retención configurada.
     */
    public function purgeOldAutoBackups(): void
    {
        $retencion = (int) \App\Models\Setting::getValue('backup_retencion_dias', 15);
        if ($retencion <= 0) $retencion = 15;

        $files = File::files($this->backupDir);
        $autoBackups = [];

        foreach ($files as $file) {
            if (str_starts_with($file->getFilename(), 'auto_backup_')) {
                $autoBackups[] = [
                    'path' => $file->getRealPath(),
                    'time' => $file->getMTime(),
                ];
            }
        }

        if (count($autoBackups) > $retencion) {
            usort($autoBackups, fn($a, $b) => $b['time'] <=> $a['time']);
            $toDelete = array_slice($autoBackups, $retencion);
            foreach ($toDelete as $item) {
                @unlink($item['path']);
            }
        }
    }

    /**
     * Comprobar si el recordatorio de respaldo está pendiente (más de X días sin ningún respaldo).
     */
    public function getBackupReminderStatus(): array
    {
        $diasConfig = (int) \App\Models\Setting::getValue('backup_recordatorio_dias', 3);
        $backups = $this->listBackups();

        if (empty($backups)) {
            return [
                'is_due' => true,
                'days_elapsed' => 999,
                'last_backup_date' => 'Nunca',
                'threshold_days' => $diasConfig,
            ];
        }

        $latest = $backups[0]; // ordenados por timestamp desc
        $diffSeconds = time() - $latest['timestamp'];
        $daysElapsed = floor($diffSeconds / 86400);

        return [
            'is_due' => $daysElapsed >= $diasConfig,
            'days_elapsed' => (int) $daysElapsed,
            'last_backup_date' => $latest['created_at'],
            'threshold_days' => $diasConfig,
        ];
    }

    /**
     * Formatear bytes a formato legible.
     */
    public function formatBytes(int $bytes): string
    {
        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        }
        if ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        }
        return $bytes . ' B';
    }
}
