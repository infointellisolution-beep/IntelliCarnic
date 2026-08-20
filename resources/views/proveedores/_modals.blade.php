<!-- MODAL CREAR PROVEEDOR -->
<div id="modalCrearProveedor" style="display: none; position: fixed; inset: 0; background: rgba(15, 23, 42, 0.7); backdrop-filter: blur(4px); z-index: 1000; align-items: center; justify-content: center; padding: 1rem;">
    <div style="background: white; border-radius: 12px; width: 620px; max-width: 95%; display: flex; flex-direction: column; max-height: 90vh; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.3); overflow: hidden;">
        <!-- Header -->
        <div style="padding: 1rem 1.5rem; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center; background: #f8fafc;">
            <h3 style="margin: 0; font-size: 1.1rem; font-weight: 700; color: var(--text-main);">
                <i class="fa-solid fa-truck-field" style="color: var(--accent);"></i> Nuevo Proveedor
            </h3>
            <button type="button" onclick="closeModal('modalCrearProveedor')" style="background: none; border: none; font-size: 1.3rem; cursor: pointer; color: var(--text-muted);">&times;</button>
        </div>

        <!-- Formulario -->
        <form action="{{ route('proveedores.store') }}" method="POST" style="margin: 0; display: flex; flex-direction: column; flex: 1; overflow: hidden;">
            @csrf
            <div style="padding: 1.5rem; overflow-y: auto; display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                <div style="grid-column: span 2;">
                    <label style="font-size: 0.85rem; font-weight: 700; color: #475569; margin-bottom: 0.35rem; display: block;">
                        Nombre o Razón Social <span style="color: #ef4444;">*</span>
                    </label>
                    <input type="text" name="nombre" class="input-modern" placeholder="Ej. Distribuidora Carnes del Norte S.A." required>
                </div>

                <div>
                    <label style="font-size: 0.85rem; font-weight: 700; color: #475569; margin-bottom: 0.35rem; display: block;">
                        Persona de Contacto
                    </label>
                    <input type="text" name="contacto_nombre" class="input-modern" placeholder="Ej. Lic. Carlos Mendoza">
                </div>

                <div>
                    <label style="font-size: 0.85rem; font-weight: 700; color: #475569; margin-bottom: 0.35rem; display: block;">
                        Identificación / RUC / RTN
                    </label>
                    <input type="text" name="identificacion" class="input-modern" placeholder="0801199512345">
                </div>

                <div>
                    <label style="font-size: 0.85rem; font-weight: 700; color: #475569; margin-bottom: 0.35rem; display: block;">
                        Teléfono / Celular
                    </label>
                    <input type="text" name="telefono" class="input-modern" placeholder="9988-7766">
                </div>

                <div>
                    <label style="font-size: 0.85rem; font-weight: 700; color: #475569; margin-bottom: 0.35rem; display: block;">
                        Correo Electrónico
                    </label>
                    <input type="email" name="correo" class="input-modern" placeholder="contacto@proveedor.com">
                </div>

                <div>
                    <label style="font-size: 0.85rem; font-weight: 700; color: #475569; margin-bottom: 0.35rem; display: block;">
                        Estado
                    </label>
                    <select name="estado" class="input-modern" style="font-weight: 600;">
                        <option value="activo">Activo</option>
                        <option value="inactivo">Inactivo</option>
                    </select>
                </div>

                <div style="grid-column: span 2;">
                    <label style="font-size: 0.85rem; font-weight: 700; color: #475569; margin-bottom: 0.35rem; display: block;">
                        Dirección Completa
                    </label>
                    <input type="text" name="direccion" class="input-modern" placeholder="Col. Las Palmeras, Calle Principal #120">
                </div>

                <div style="grid-column: span 2;">
                    <label style="font-size: 0.85rem; font-weight: 700; color: #475569; margin-bottom: 0.35rem; display: block;">
                        Notas / Condiciones Comerciales
                    </label>
                    <textarea name="notas" class="input-modern" rows="2" placeholder="Días de entrega, crédito acordado, etc."></textarea>
                </div>
            </div>

            <!-- Footer -->
            <div style="padding: 0.85rem 1.5rem; border-top: 1px solid var(--border-color); background: #f8fafc; display: flex; justify-content: flex-end; gap: 0.75rem;">
                <button type="button" class="btn-modern btn-secondary" onclick="closeModal('modalCrearProveedor')">Cancelar</button>
                <button type="submit" class="btn-modern btn-primary" style="background: var(--accent); border-color: var(--accent);">
                    <i class="fa-solid fa-floppy-disk"></i> Guardar Proveedor
                </button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL EDITAR PROVEEDOR -->
<div id="modalEditarProveedor" style="display: none; position: fixed; inset: 0; background: rgba(15, 23, 42, 0.7); backdrop-filter: blur(4px); z-index: 1000; align-items: center; justify-content: center; padding: 1rem;">
    <div style="background: white; border-radius: 12px; width: 620px; max-width: 95%; display: flex; flex-direction: column; max-height: 90vh; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.3); overflow: hidden;">
        <!-- Header -->
        <div style="padding: 1rem 1.5rem; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center; background: #f8fafc;">
            <h3 style="margin: 0; font-size: 1.1rem; font-weight: 700; color: var(--text-main);">
                <i class="fa-solid fa-pen-to-square" style="color: var(--primary);"></i> Editar Proveedor
            </h3>
            <button type="button" onclick="closeModal('modalEditarProveedor')" style="background: none; border: none; font-size: 1.3rem; cursor: pointer; color: var(--text-muted);">&times;</button>
        </div>

        <!-- Formulario Editar -->
        <form id="formEditarProveedor" action="" method="POST" style="margin: 0; display: flex; flex-direction: column; flex: 1; overflow: hidden;">
            @csrf
            @method('PUT')
            <div style="padding: 1.5rem; overflow-y: auto; display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                <div style="grid-column: span 2;">
                    <label style="font-size: 0.85rem; font-weight: 700; color: #475569; margin-bottom: 0.35rem; display: block;">
                        Nombre o Razón Social <span style="color: #ef4444;">*</span>
                    </label>
                    <input type="text" id="edit_prov_nombre" name="nombre" class="input-modern" required>
                </div>

                <div>
                    <label style="font-size: 0.85rem; font-weight: 700; color: #475569; margin-bottom: 0.35rem; display: block;">
                        Persona de Contacto
                    </label>
                    <input type="text" id="edit_prov_contacto" name="contacto_nombre" class="input-modern">
                </div>

                <div>
                    <label style="font-size: 0.85rem; font-weight: 700; color: #475569; margin-bottom: 0.35rem; display: block;">
                        Identificación / RUC / RTN
                    </label>
                    <input type="text" id="edit_prov_identificacion" name="identificacion" class="input-modern">
                </div>

                <div>
                    <label style="font-size: 0.85rem; font-weight: 700; color: #475569; margin-bottom: 0.35rem; display: block;">
                        Teléfono / Celular
                    </label>
                    <input type="text" id="edit_prov_telefono" name="telefono" class="input-modern">
                </div>

                <div>
                    <label style="font-size: 0.85rem; font-weight: 700; color: #475569; margin-bottom: 0.35rem; display: block;">
                        Correo Electrónico
                    </label>
                    <input type="email" id="edit_prov_correo" name="correo" class="input-modern">
                </div>

                <div>
                    <label style="font-size: 0.85rem; font-weight: 700; color: #475569; margin-bottom: 0.35rem; display: block;">
                        Estado
                    </label>
                    <select id="edit_prov_estado" name="estado" class="input-modern" style="font-weight: 600;">
                        <option value="activo">Activo</option>
                        <option value="inactivo">Inactivo</option>
                    </select>
                </div>

                <div style="grid-column: span 2;">
                    <label style="font-size: 0.85rem; font-weight: 700; color: #475569; margin-bottom: 0.35rem; display: block;">
                        Dirección Completa
                    </label>
                    <input type="text" id="edit_prov_direccion" name="direccion" class="input-modern">
                </div>

                <div style="grid-column: span 2;">
                    <label style="font-size: 0.85rem; font-weight: 700; color: #475569; margin-bottom: 0.35rem; display: block;">
                        Notas / Observaciones
                    </label>
                    <textarea id="edit_prov_notas" name="notas" class="input-modern" rows="2"></textarea>
                </div>
            </div>

            <!-- Footer -->
            <div style="padding: 0.85rem 1.5rem; border-top: 1px solid var(--border-color); background: #f8fafc; display: flex; justify-content: flex-end; gap: 0.75rem;">
                <button type="button" class="btn-modern btn-secondary" onclick="closeModal('modalEditarProveedor')">Cancelar</button>
                <button type="submit" class="btn-modern btn-primary"><i class="fa-solid fa-floppy-disk"></i> Actualizar Cambios</button>
            </div>
        </form>
    </div>
</div>
