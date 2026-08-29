<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    public const ROLE_ADMIN = 'administrador';
    public const ROLE_ENCARGADO = 'encargado';
    public const ROLE_VENDEDOR = 'vendedor';

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'permissions',
        'sucursal_id',
        'is_active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'permissions' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function sucursal()
    {
        return $this->belongsTo(Sucursal::class, 'sucursal_id');
    }

    public function isAdministrator(): bool
    {
        return strtolower($this->email) === 'admin@gmail.com' || $this->role === self::ROLE_ADMIN;
    }

    public function isEncargado(): bool
    {
        return $this->role === self::ROLE_ENCARGADO;
    }

    public function isVendedor(): bool
    {
        return $this->role === self::ROLE_VENDEDOR || empty($this->role);
    }

    public function getRoleName(): string
    {
        return match ($this->role) {
            self::ROLE_ADMIN => 'Administrador',
            self::ROLE_ENCARGADO => 'Encargado',
            self::ROLE_VENDEDOR => 'Vendedor',
            default => 'Vendedor',
        };
    }

    public function getRoleBadgeStyle(): array
    {
        return match ($this->role) {
            self::ROLE_ADMIN => [
                'bg' => '#f3e8ff',
                'color' => '#7e22ce',
                'border' => '#d8b4fe',
                'icon' => 'fa-solid fa-crown',
            ],
            self::ROLE_ENCARGADO => [
                'bg' => '#eff6ff',
                'color' => '#1d4ed8',
                'border' => '#bfdbfe',
                'icon' => 'fa-solid fa-user-tie',
            ],
            self::ROLE_VENDEDOR => [
                'bg' => '#f0fdf4',
                'color' => '#15803d',
                'border' => '#bbf7d0',
                'icon' => 'fa-solid fa-user-tag',
            ],
            default => [
                'bg' => '#f1f5f9',
                'color' => '#475569',
                'border' => '#cbd5e1',
                'icon' => 'fa-solid fa-user',
            ],
        };
    }

    /**
     * Comprobar si el usuario tiene un permiso específico.
     */
    public function hasPermission(string $permission): bool
    {
        // El administrador siempre tiene acceso total a todo
        if ($this->isAdministrator()) {
            return true;
        }

        $perms = $this->permissions;
        if (is_array($perms)) {
            return in_array($permission, $perms, true);
        }

        // Si no tiene permisos personalizados asignados, usar los predeterminados de su rol
        $defaults = self::getDefaultPermissionsForRole($this->role ?? self::ROLE_VENDEDOR);
        return in_array($permission, $defaults, true);
    }

    /**
     * Comprobar si tiene al menos uno de los permisos dados.
     */
    public function hasAnyPermission(array $permissions): bool
    {
        if ($this->isAdministrator()) {
            return true;
        }

        foreach ($permissions as $perm) {
            if ($this->hasPermission($perm)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Comprobar si tiene acceso a un módulo (al menos un permiso del módulo).
     */
    public function hasModuleAccess(string $moduleKey): bool
    {
        if ($this->isAdministrator()) {
            return true;
        }

        $allModules = self::getAllModulesAndPermissions();
        if (!isset($allModules[$moduleKey])) {
            return false;
        }

        $modulePermissions = array_keys($allModules[$moduleKey]['permissions']);
        return $this->hasAnyPermission($modulePermissions);
    }

    /**
     * Obtener el listado efectivo de claves de permisos del usuario.
     */
    public function getEffectivePermissions(): array
    {
        if ($this->isAdministrator()) {
            $all = [];
            foreach (self::getAllModulesAndPermissions() as $module) {
                $all = array_merge($all, array_keys($module['permissions']));
            }
            return $all;
        }

        if (is_array($this->permissions)) {
            return $this->permissions;
        }

        return self::getDefaultPermissionsForRole($this->role ?? self::ROLE_VENDEDOR);
    }

    /**
     * Catálogo completo de módulos y permisos del sistema.
     */
    public static function getAllModulesAndPermissions(): array
    {
        return [
            'dashboard' => [
                'name' => 'Dashboard y Métricas',
                'icon' => 'fa-solid fa-chart-line',
                'color' => '#2563eb',
                'permissions' => [
                    'dashboard.ver' => 'Ver Dashboard y Métricas Principales',
                ],
            ],
            'reportes' => [
                'name' => 'Reportes y Estadísticas',
                'icon' => 'fa-solid fa-chart-pie',
                'color' => '#0891b2',
                'permissions' => [
                    'reportes.ventas' => 'Reporte General de Ventas',
                    'reportes.compras' => 'Reporte General de Compras',
                    'reportes.caja' => 'Reporte de Cortes de Caja (Corte Z)',
                    'reportes.kardex' => 'Reporte de Rotación y Kardex',
                    'reportes.transferencias' => 'Reporte de Transferencias',
                ],
            ],
            'articulos' => [
                'name' => 'Artículos e Inventario',
                'icon' => 'fa-solid fa-boxes-stacked',
                'color' => '#ea580c',
                'permissions' => [
                    'articulos.ver' => 'Ver Catálogo y Existencias',
                    'articulos.crear' => 'Crear Nuevos Artículos y Lotes',
                    'articulos.editar' => 'Editar Precios, Nombres y Costos',
                    'articulos.eliminar' => 'Eliminar Artículos del Sistema',
                    'articulos.ajustes' => 'Ajustes de Inventario y Handheld',
                    'articulos.familias' => 'Gestionar Familias y Categorías',
                ],
            ],
            'ventas' => [
                'name' => 'Ventas y TPV',
                'icon' => 'fa-solid fa-cash-register',
                'color' => '#16a34a',
                'permissions' => [
                    'ventas.ver' => 'Ver Historial de Ventas',
                    'ventas.crear_normal' => 'Realizar Venta en Modo Normal',
                    'ventas.crear_tactil' => 'Realizar Venta en TPV Táctil',
                    'ventas.descuentos' => 'Aplicar Descuentos en Venta',
                    'ventas.reimprimir' => 'Reimprimir Tickets de Venta',
                    'ventas.devolucion' => 'Procesar Devoluciones / Cancelaciones',
                ],
            ],
            'caja' => [
                'name' => 'Control de Caja',
                'icon' => 'fa-solid fa-vault',
                'color' => '#d97706',
                'permissions' => [
                    'caja.ver' => 'Ver Sesión y Arqueo de Caja',
                    'caja.aperturar' => 'Aperturar Caja con Monto Inicial',
                    'caja.movimientos' => 'Ingresar / Retirar Efectivo Manual',
                    'caja.cerrar' => 'Cerrar Caja y Generar Corte Z',
                    'caja.historial' => 'Ver Historial de Cierres Anteriores',
                    'caja.ticket_cierre' => 'Reimprimir Ticket de Cierre',
                ],
            ],
            'compras' => [
                'name' => 'Compras y Proveedores',
                'icon' => 'fa-solid fa-truck-ramp-box',
                'color' => '#7c3aed',
                'permissions' => [
                    'compras.ver' => 'Ver Historial de Compras',
                    'compras.crear' => 'Registrar Compras y Nuevos Lotes',
                    'compras.eliminar' => 'Anular Facturas de Compra',
                    'proveedores.ver' => 'Ver Directorio de Proveedores',
                    'proveedores.crear' => 'Crear Nuevos Proveedores',
                    'proveedores.editar' => 'Editar Datos de Proveedores',
                    'proveedores.eliminar' => 'Eliminar Proveedores',
                ],
            ],
            'transferencias' => [
                'name' => 'Transferencias Sucursales',
                'icon' => 'fa-solid fa-dolly',
                'color' => '#0284c7',
                'permissions' => [
                    'transferencias.ver' => 'Ver Transferencias Locales',
                    'transferencias.crear' => 'Crear y Enviar Transferencias',
                    'transferencias.recibir' => 'Recepcionar Transferencias Entrantes',
                    'transferencias.nube' => 'Consultar Transferencias en la Nube',
                    'transferencias.reimprimir' => 'Reimprimir Guía de Transferencia',
                ],
            ],
            'clientes' => [
                'name' => 'Clientes y Créditos',
                'icon' => 'fa-solid fa-users',
                'color' => '#059669',
                'permissions' => [
                    'clientes.ver' => 'Ver Directorio y Saldos de Clientes',
                    'clientes.crear' => 'Registrar Nuevos Clientes',
                    'clientes.editar' => 'Editar Datos y Límites de Crédito',
                    'clientes.eliminar' => 'Eliminar Clientes',
                    'clientes.abonos' => 'Registrar Abonos a Cuentas Corrientes',
                    'clientes.historial_abonos' => 'Ver Historial de Abonos y Tickets',
                ],
            ],
            'configuracion' => [
                'name' => 'Configuración y Sistema',
                'icon' => 'fa-solid fa-gear',
                'color' => '#475569',
                'permissions' => [
                    'configuracion.general' => 'Parámetros Generales e Impuestos',
                    'configuracion.empresa' => 'Datos de Empresa y Logotipo',
                    'configuracion.sucursales' => 'Gestión de Sucursales y Nube',
                    'configuracion.usuarios' => 'Gestión de Usuarios y Permisos',
                    'configuracion.base_datos' => 'Respaldos, Restauración y Reseteo',
                ],
            ],
        ];
    }

    /**
     * Plantillas predeterminadas de permisos según el rol.
     */
    public static function getDefaultPermissionsForRole(string $role): array
    {
        if ($role === self::ROLE_ADMIN) {
            $all = [];
            foreach (self::getAllModulesAndPermissions() as $module) {
                $all = array_merge($all, array_keys($module['permissions']));
            }
            return $all;
        }

        if ($role === self::ROLE_ENCARGADO) {
            return [
                'dashboard.ver',
                'reportes.ventas',
                'reportes.compras',
                'reportes.caja',
                'reportes.kardex',
                'reportes.transferencias',
                'articulos.ver',
                'articulos.crear',
                'articulos.editar',
                'articulos.ajustes',
                'articulos.familias',
                'ventas.ver',
                'ventas.crear_normal',
                'ventas.crear_tactil',
                'ventas.descuentos',
                'ventas.reimprimir',
                'ventas.devolucion',
                'caja.ver',
                'caja.aperturar',
                'caja.movimientos',
                'caja.cerrar',
                'caja.historial',
                'caja.ticket_cierre',
                'compras.ver',
                'compras.crear',
                'proveedores.ver',
                'proveedores.crear',
                'proveedores.editar',
                'transferencias.ver',
                'transferencias.crear',
                'transferencias.recibir',
                'transferencias.nube',
                'transferencias.reimprimir',
                'clientes.ver',
                'clientes.crear',
                'clientes.editar',
                'clientes.abonos',
                'clientes.historial_abonos',
                'configuracion.general',
                'configuracion.empresa',
                'configuracion.sucursales',
            ];
        }

        // Vendedor por defecto
        return [
            'dashboard.ver',
            'articulos.ver',
            'ventas.ver',
            'ventas.crear_normal',
            'ventas.crear_tactil',
            'ventas.reimprimir',
            'caja.ver',
            'caja.aperturar',
            'caja.movimientos',
            'caja.cerrar',
            'caja.ticket_cierre',
            'transferencias.ver',
            'transferencias.crear',
            'transferencias.recibir',
            'transferencias.reimprimir',
            'clientes.ver',
            'clientes.crear',
            'clientes.abonos',
            'clientes.historial_abonos',
        ];
    }
}
