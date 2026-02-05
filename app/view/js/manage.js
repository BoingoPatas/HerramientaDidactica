/**
 * manage.js - Interacciones AJAX para gestionar usuarios, docentes y temas
 */

// Se utiliza una IIFE para encapsular el código y exponer solo las funciones necesarias globalmente.
window.manageJS = (function(){
    console.log('✅ manage.js cargado correctamente');

    const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    console.debug('manage.js inicializado', { csrfExists: Boolean(csrf) });

    let selectedDocente = null;
    let selectedUser = null;

    // Función para ordenar secciones numéricamente
    function sortSections(sectionsStr) {
        if (!sectionsStr) return '';
        return sectionsStr.split(',')
            .map(s => s.trim())
            .filter(s => s !== '')
            .map(s => parseInt(s, 10))
            .filter(n => !isNaN(n))
            .sort((a, b) => a - b)
            .join(', ');
    }

    // Selección de filas: usuarios
    function selectUser(id) {
        selectedUser = id;
        const container = document.getElementById('users-list');
        if (!container) return;
        
        Array.from(container.querySelectorAll('tr[data-user-id]')).forEach(r => r.classList.remove('selected'));
        const row = container.querySelector('tr[data-user-id="' + id + '"]');
        if (row) row.classList.add('selected');

        const name = row ? (row.querySelector('td strong')?.textContent || ('ID ' + id)) : '';
        const selSpan = document.getElementById('selected-user');
        if (selSpan) {
            selSpan.textContent = name ? name : '';
            selSpan.style.display = name ? 'flex' : 'none';
        }

        const btnEdit = document.getElementById('btn-edit-user');
        const btnToggle = document.getElementById('btn-toggle-user');
        if (btnEdit) btnEdit.style.display = '';
        if (btnToggle) {
            btnToggle.style.display = '';
            const isActive = row && row.querySelector('.user-status') && row.querySelector('.user-status').classList.contains('active');
            btnToggle.textContent = isActive ? '🚫 Inhabilitar usuario' : '✅ Habilitar usuario';
            btnToggle.className = isActive ? 'btn btn-danger' : 'btn btn-success';
        }
    }

    // Selección de filas: docentes
    function selectDocente(id) {
        selectedDocente = id;
        const container = document.getElementById('docentes-list');
        if (!container) return;
        
        Array.from(container.querySelectorAll('tr[data-docente-id]')).forEach(r => r.classList.remove('selected'));
        const row = container.querySelector('tr[data-docente-id="' + id + '"]');
        if (row) row.classList.add('selected');

        const name = row ? (row.querySelector('td strong')?.textContent || ('ID ' + id)) : '';
        const selSpan = document.getElementById('selected-docente');
        if (selSpan) {
            selSpan.textContent = name ? name : '';
            selSpan.style.display = name ? 'flex' : 'none';
        }

        const btnEdit = document.getElementById('btn-edit-docente');
        const btnToggle = document.getElementById('btn-toggle-docente');
        if (btnEdit) btnEdit.style.display = '';
        if (btnToggle) {
            btnToggle.style.display = '';
            const isActive = row && row.querySelector('.user-status') && row.querySelector('.user-status').classList.contains('active');
            btnToggle.textContent = isActive ? '🚫 Inhabilitar docente' : '✅ Habilitar docente';
            btnToggle.className = isActive ? 'btn btn-danger' : 'btn btn-success';
        }
    }

    // Función API mejorada para manejar respuestas no-JSON (errores de PHP/HTML)
    async function api(action, payload = {}){
        console.log(`🔄 Haciendo llamada API: ${action}`, payload);
        const body = Object.assign({}, payload, { action, csrf_token: csrf });
        const url = (window.APP_ROUTES && window.APP_ROUTES.action_manage) ? window.APP_ROUTES.action_manage : 'index.php?action=manage';
        const res = await fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(body)
        });

        const text = await res.text();

        try {
            const data = JSON.parse(text);
            console.log(`✅ Respuesta API ${action}:`, data);
            return data;
        } catch (e) {
            console.error(`❌ Error de JSON al procesar la respuesta de ${action}. La respuesta no era JSON.`, text);
            return {
                success: false,
                error: `Error de servidor. La respuesta no fue JSON. Detalle: ${text.substring(0, 200)}...`
            };
        }
    }

    // Funciones utilitarias
    function showNotification(message, type = 'info') {
        console.log(`📢 Notificación: ${message} (${type})`);
        const notification = document.createElement('div');
        notification.className = `notification ${type}`;
        notification.textContent = message;
        document.body.appendChild(notification);
        setTimeout(() => { if (notification.parentNode) notification.parentNode.removeChild(notification); }, 3000);
    }

    function escapeHtml(s){
        return String(s).replace(/[&<>"']/g, function(c){
            return {'&':'&','<':'<','>':'>','"':'"','\'':'&#39;'}[c];
        });
    }

    // Funciones para gestión de temas
    async function createTopic(data) {
        try {
            const res = await api('create_topic', data);
            if (res.success) {
                showNotification('Tema creado correctamente', 'success');
                return { success: true };
            } else {
                showNotification('Error: ' + (res.error || 'Error desconocido'), 'error');
                return { success: false, error: res.error };
            }
        } catch (err) {
            showNotification('Error de conexión: ' + err.message, 'error');
            return { success: false, error: err.message };
        }
    }

    async function updateTopic(data) {
        try {
            const res = await api('update_topic', data);
            if (res.success) {
                showNotification('Tema actualizado correctamente', 'success');
                return { success: true };
            } else {
                showNotification('Error: ' + (res.error || 'Error desconocido'), 'error');
                return { success: false, error: res.error };
            }
        } catch (err) {
            showNotification('Error de conexión: ' + err.message, 'error');
            return { success: false, error: err.message };
        }
    }

    async function deleteTopic(data) {
        try {
            const res = await api('delete_topic', data);
            if (res.success) {
                showNotification('Tema eliminado correctamente', 'success');
                return { success: true };
            } else {
                showNotification('Error: ' + (res.error || 'Error desconocido'), 'error');
                return { success: false, error: res.error };
            }
        } catch (err) {
            showNotification('Error de conexión: ' + err.message, 'error');
            return { success: false, error: err.message };
        }
    }

    // Funciones para gestión de usuarios
    async function renderUsers() {
        console.log('🔍 renderUsers() - Inicio de función');
        try {
            const res = await api('list_users', { role: 'Usuario' });
            if (res.success) {
                const container = document.getElementById('users-list');
                if (!container) return;
                
                let html = '';
                if (res.users && res.users.length > 0) {
                    html += '<table class="mg-table users-table" role="table" style="width:100%;border-collapse:collapse;">';
                    html += '<thead><tr>' +
                        '<th>Nombre</th>' +
                        '<th>Correo</th>' +
                        '<th>Sección</th>' +
                        '<th>Estado</th>' +
                        '</tr></thead><tbody>';

                    res.users.forEach(user => {
                        const estado = user.activo ? 'Activo' : 'Inactivo';
                        const seccion = user.seccion ? escapeHtml(user.seccion) : '<span class="status-badge inactive" style="font-size: 0.75em; padding: 4px 8px; border-radius: 12px;">NO ASIGNADA</span>';
                        html += '<tr data-user-id="' + user.id + '" class="user-row">';
                        html += '<td><strong>' + escapeHtml(user.nombre_usuario) + '</strong></td>';
                        html += '<td>' + escapeHtml(user.correo_electronico) + '</td>';
                        html += '<td>' + seccion + '</td>';
                        html += '<td><span class="user-status ' + (user.activo ? 'active' : 'inactive') + '">' + estado + '</span></td>';
                        html += '</tr>';
                    });
                    html += '</tbody></table>';
                } else {
                    html = '<div class="no-data">No hay usuarios registrados</div>';
                }
                container.innerHTML = html;

                Array.from(container.querySelectorAll('tr[data-user-id]')).forEach(tr => {
                    tr.addEventListener('click', function() {
                        const id = parseInt(this.getAttribute('data-user-id'), 10);
                        selectUser(id);
                    });
                });
            } else {
                showNotification('Error al cargar usuarios: ' + (res.error || 'Error desconocido'), 'error');
            }
        } catch (err) {
            showNotification('Error de conexión al cargar usuarios: ' + err.message, 'error');
        }
    }

    async function setupUserButtonListeners() {
        createUserModals();
        const addUserBtn = document.getElementById('btn-new-user') || document.getElementById('btn-add-user');
        if (addUserBtn) {
            addUserBtn.onclick = () => showAddUserModal();
        }
        const btnEditUser = document.getElementById('btn-edit-user');
        const btnToggleUser = document.getElementById('btn-toggle-user');
        if (btnEditUser) {
            btnEditUser.onclick = function() {
                if (selectedUser) showEditUserModal(selectedUser);
            };
        }
        if (btnToggleUser) {
            btnToggleUser.onclick = function() {
                if (!selectedUser) return;
                const row = document.querySelector('tr[data-user-id="' + selectedUser + '"]');
                const isActive = row && row.querySelector('.user-status') && row.querySelector('.user-status').classList.contains('active');
                const wantActive = isActive ? 0 : 1;
                showConfirmDisableModal(selectedUser, wantActive);
            };
        }
    }

    // Funciones para gestión de docentes (Administrador)
    async function renderDocentes() {
        try {
            const res = await api('list_docentes');
            if (res.success) {
                const container = document.getElementById('docentes-list');
                if (!container) return;

                let html = '';
                if (res.docentes && res.docentes.length > 0) {
                    html += '<table class="mg-table docentes-table" style="width:100%;border-collapse:collapse;">';
                    html += '<thead><tr><th>Nombre</th><th>Correo</th><th>Sección</th><th>Estado</th></tr></thead><tbody>';
                    res.docentes.forEach(d => {
                        const estado = d.activo ? 'Activo' : 'Inactivo';
                        const seccion = d.seccion ? escapeHtml(d.seccion) : '<span class="status-badge inactive" style="font-size: 0.75em; padding: 4px 8px; border-radius: 12px;">NO ASIGNADA</span>';
                        html += '<tr data-docente-id="' + d.id + '" class="docente-row">';
                        html += '<td><strong>' + escapeHtml(d.nombre_usuario || '') + '</strong></td>';
                        html += '<td>' + escapeHtml(d.correo_electronico || '') + '</td>';
                        html += '<td>' + seccion + '</td>';
                        html += '<td><span class="user-status ' + (d.activo ? 'active' : 'inactive') + '">' + estado + '</span></td>';
                        html += '</tr>';
                    });
                    html += '</tbody></table>';
                } else {
                    html = '<div class="no-data">No hay docentes registrados</div>';
                }
                container.innerHTML = html;
                Array.from(container.querySelectorAll('tr[data-docente-id]')).forEach(tr => {
                    tr.addEventListener('click', function() {
                        const id = parseInt(this.getAttribute('data-docente-id'), 10);
                        selectDocente(id);
                    });
                });
            } else {
                showNotification('Error al cargar docentes: ' + (res.error || 'Error desconocido'), 'error');
            }
        } catch (err) {
            showNotification('Error de conexión al cargar docentes: ' + err.message, 'error');
        }
    }

    function setupDocenteButtonListeners() {
        createDocenteModals();
        const addBtn = document.getElementById('btn-new-docente') || document.getElementById('btn-add-docente');
        if (addBtn) {
            addBtn.onclick = () => showAddDocenteModal();
        }
        const btnEdit = document.getElementById('btn-edit-docente');
        const btnToggle = document.getElementById('btn-toggle-docente');
        if (btnEdit) {
            btnEdit.onclick = function() {
                if (selectedDocente) showEditDocenteModal(selectedDocente);
            };
        }
        if (btnToggle) {
            btnToggle.onclick = function() {
                if (!selectedDocente) return;
                const row = document.querySelector('tr[data-docente-id="' + selectedDocente + '"]');
                const isActive = row && row.querySelector('.user-status') && row.querySelector('.user-status').classList.contains('active');
                const wantActive = isActive ? 0 : 1;
                showConfirmDisableDocenteModal(selectedDocente, wantActive);
            };
        }
    }

    // Modales de Usuario
    function createUserModals() {
        if (document.getElementById('add-user-modal')) return;

        const overlay = document.createElement('div');
        overlay.id = 'user-modals-overlay';
        overlay.className = 'mg-modal-overlay';
        overlay.style.display = 'none';
        document.body.appendChild(overlay);

        const addModal = document.createElement('div');
        addModal.id = 'add-user-modal';
        addModal.className = 'mg-modal hidden';
        addModal.innerHTML = `
            <div class="mg-modal-header">
                <h3 class="mg-modal-title">Añadir Usuario 👥</h3>
                <button class="modal-close" onclick="manageJS.hideUserForm()">&times;</button>
            </div>
            <form id="add-user-modal-form">
                <div class="mg-modal-body">
                    <div class="form-group">
                        <label>Cédula:</label>
                        <input type="text" name="cedula" id="add-user-cedula" placeholder="Ej: 12345678">
                    </div>
                    <div class="form-group">
                        <label>Correo electrónico:</label>
                        <input type="email" name="correo_electronico" id="add-user-email" placeholder="usuario@ejemplo.com">
                        <small style="color: var(--muted); font-size: 0.8em; margin-top: 4px; display: block;">Ejemplo: ejemplo@ejemplo.com</small>
                    </div>
                    <div class="form-group">
                        <label>Contraseña:</label>
                        <input type="password" name="contrasena" id="add-user-pass" placeholder="Mínimo 8 caracteres">
                        <small style="color: var(--muted); font-size: 0.8em; margin-top: 4px; display: block;">Debe llevar: Al menos 8 caracteres, una mayúscula, una minúscula, un número y un carácter especial.</small>
                    </div>
                </div>
                <div class="mg-modal-actions">
                    <button type="submit" class="btn btn-primary">Crear Usuario</button>
                    <button type="button" class="btn btn-secondary" onclick="manageJS.hideUserForm()">Cancelar</button>
                </div>
            </form>
        `;
        document.body.appendChild(addModal);

        const editModal = document.createElement('div');
        editModal.id = 'edit-user-modal';
        editModal.className = 'mg-modal hidden';
        editModal.innerHTML = `
            <div class="mg-modal-header">
                <h3 class="mg-modal-title">Editar Usuario ✏️</h3>
                <button class="modal-close" onclick="manageJS.hideUserForm()">&times;</button>
            </div>
            <form id="edit-user-modal-form">
                <input type="hidden" name="id">
                <div class="mg-modal-body">
                    <div class="form-group">
                        <label>Cédula:</label>
                        <input type="text" name="nombre_usuario" id="edit-user-cedula">
                    </div>
                    <div class="form-group">
                        <label>Correo electrónico:</label>
                        <input type="email" name="correo_electronico" id="edit-user-email">
                        <small style="color: var(--muted); font-size: 0.8em; margin-top: 4px; display: block;">Ejemplo: ejemplo@ejemplo.com</small>
                    </div>
                    <div class="form-group">
                        <label>Sección:</label>
                        <input type="text" name="seccion" id="edit-user-seccion" placeholder="Ej: 20">
                    </div>
                    <div class="form-group">
                        <label>Contraseña (dejar en blanco para no cambiar):</label>
                        <input type="password" name="contrasena" id="edit-user-pass">
                        <small style="color: var(--muted); font-size: 0.8em; margin-top: 4px; display: block;">Debe llevar: Al menos 8 caracteres, una mayúscula, una minúscula, un número y un carácter especial.</small>
                    </div>
                </div>
                <div class="mg-modal-actions">
                    <button type="submit" class="btn btn-primary">Guardar cambios</button>
                    <button type="button" class="btn btn-secondary" onclick="manageJS.hideUserForm()">Cancelar</button>
                </div>
            </form>
        `;
        document.body.appendChild(editModal);

        const confirmModal = document.createElement('div');
        confirmModal.id = 'confirm-disable-modal';
        confirmModal.className = 'mg-modal hidden';
        confirmModal.innerHTML = `
            <div class="mg-modal-header">
                <h3 class="mg-modal-title">Confirmar acción ⚠️</h3>
                <button class="modal-close" onclick="manageJS.hideUserForm()">&times;</button>
            </div>
            <div class="mg-modal-body" style="text-align: center; padding: 1rem 0;">
                <p id="confirm-disable-text" style="font-size: 1.1rem; color: var(--text);">¿Está seguro?</p>
            </div>
            <div class="mg-modal-actions" style="justify-content: center;">
                <button id="confirm-disable-yes" class="btn btn-primary">Sí, confirmar</button>
                <button type="button" class="btn btn-secondary" onclick="manageJS.hideUserForm()">Cancelar</button>
            </div>
        `;
        document.body.appendChild(confirmModal);

        setupUserModalEvents();
    }

    function setupUserModalEvents() {
        const addForm = document.getElementById('add-user-modal-form');
        if (addForm) {
            const cedulaInput = addForm.elements['cedula'];
            cedulaInput.addEventListener('keypress', (e) => { if (!/[0-9]/.test(e.key)) e.preventDefault(); });
            
            if (window.Validation) {
                Validation.applyRealTimeValidation(cedulaInput, 'numeric', { required: true });
                Validation.applyRealTimeValidation(addForm.elements['correo_electronico'], 'email', { required: true });
            }
            addForm.onsubmit = async (e) => {
                e.preventDefault();
                if (window.Validation) {
                    const rules = {
                        cedula: [{ type: 'required' }, { type: 'cedula' }],
                        correo_electronico: [{ type: 'required' }, { type: 'email' }]
                    };
                    const result = Validation.validateForm(addForm, rules);
                    if (!result.valid) return;
                }
                const fd = new FormData(addForm);
                const data = {
                    cedula: fd.get('cedula'),
                    correo_electronico: fd.get('correo_electronico'),
                    contrasena: fd.get('contrasena') || ''
                };
                const res = await manageJS.createUser(data);
                if (res && res.success) hideUserForm();
            };
        }

        const editForm = document.getElementById('edit-user-modal-form');
        if (editForm) {
            const cedulaInput = editForm.elements['nombre_usuario'];
            cedulaInput.addEventListener('keypress', (e) => { if (!/[0-9]/.test(e.key)) e.preventDefault(); });

            if (window.Validation) {
                Validation.applyRealTimeValidation(cedulaInput, 'numeric', { required: true });
                Validation.applyRealTimeValidation(editForm.elements['correo_electronico'], 'email', { required: true });
                Validation.applyRealTimeValidation(editForm.elements['seccion'], 'section');
            }
            editForm.onsubmit = async (e) => {
                e.preventDefault();
                if (window.Validation) {
                    const rules = {
                        nombre_usuario: [{ type: 'required' }, { type: 'cedula' }],
                        correo_electronico: [{ type: 'required' }, { type: 'email' }],
                        seccion: [{ type: 'section' }]
                    };
                    const result = Validation.validateForm(editForm, rules);
                    if (!result.valid) return;
                }
                const fd = new FormData(editForm);
                const id = parseInt(fd.get('id'), 10);
                const fields = {
                    nombre_usuario: fd.get('nombre_usuario'),
                    correo_electronico: fd.get('correo_electronico'),
                    seccion: fd.get('seccion') || ''
                };
                const pw = fd.get('contrasena');
                if (pw && pw.length > 0) fields.contrasena = pw;
                const res = await manageJS.updateUser({ id, fields });
                if (res && res.success) hideUserForm();
            };
        }

        const yesBtn = document.getElementById('confirm-disable-yes');
        if (yesBtn) {
            yesBtn.onclick = async () => {
                const modal = document.getElementById('confirm-disable-modal');
                const userId = parseInt(modal.getAttribute('data-user-id') || '0', 10);
                const active = parseInt(modal.getAttribute('data-active') || '0', 10);
                if (userId > 0) {
                    const res = await manageJS.toggleUser(userId, active);
                    if (res && res.success) hideUserForm();
                }
            };
        }
    }

    function showAddUserModal() {
        const modal = document.getElementById('add-user-modal');
        const overlay = document.getElementById('user-modals-overlay');
        if (!modal || !overlay) return;
        const form = document.getElementById('add-user-modal-form');
        if (form) form.reset();
        modal.classList.remove('hidden');
        overlay.style.display = 'block';
    }

    async function showEditUserModal(userId) {
        const modal = document.getElementById('edit-user-modal');
        const overlay = document.getElementById('user-modals-overlay');
        const form = document.getElementById('edit-user-modal-form');
        if (!modal || !overlay || !form) return;
        const res = await api('get_user', { id: userId });
        if (!res || !res.success) {
            showNotification('No se pudo cargar datos del usuario: ' + (res.error || 'Error desconocido'), 'error');
            return;
        }
        const user = res.user;
        form.elements['id'].value = user.id;
        form.elements['nombre_usuario'].value = user.nombre_usuario || '';
        form.elements['correo_electronico'].value = user.correo_electronico || '';
        form.elements['seccion'].value = user.seccion || '';
        modal.classList.remove('hidden');
        overlay.style.display = 'block';
    }

    function showConfirmDisableModal(userId, active) {
        const modal = document.getElementById('confirm-disable-modal');
        const overlay = document.getElementById('user-modals-overlay');
        const text = document.getElementById('confirm-disable-text');
        if (!modal || !overlay || !text) return;
        const actionText = active ? 'habilitar' : 'inhabilitar';
        text.textContent = `¿Confirma que desea ${actionText} al usuario ID ${userId}?`;
        modal.setAttribute('data-user-id', userId);
        modal.setAttribute('data-active', active ? '1' : '0');
        modal.classList.remove('hidden');
        overlay.style.display = 'block';
    }

    function hideUserForm() {
        const add = document.getElementById('add-user-modal'); if (add) add.classList.add('hidden');
        const edit = document.getElementById('edit-user-modal'); if (edit) edit.classList.add('hidden');
        const confirm = document.getElementById('confirm-disable-modal'); if (confirm) confirm.classList.add('hidden');
        const overlay = document.getElementById('user-modals-overlay'); if (overlay) overlay.style.display = 'none';
        manageJS.renderUsers();
    }

    // Modales de Docente
    function createDocenteModals() {
        if (document.getElementById('add-docente-modal')) return;

        const overlay = document.createElement('div');
        overlay.id = 'docente-modals-overlay';
        overlay.className = 'mg-modal-overlay';
        overlay.style.display = 'none';
        document.body.appendChild(overlay);

        const addModal = document.createElement('div');
        addModal.id = 'add-docente-modal';
        addModal.className = 'mg-modal hidden';
        addModal.innerHTML = `
            <div class="mg-modal-header">
                <h3 class="mg-modal-title">Añadir Docente 🏫</h3>
                <button class="modal-close" onclick="manageJS.hideDocenteForm()">&times;</button>
            </div>
            <form id="add-docente-modal-form">
                <div class="mg-modal-body">
                    <div class="form-group">
                        <label>Cédula:</label>
                        <input type="text" name="cedula" id="add-docente-cedula" placeholder="Ej: 12345678">
                    </div>
                    <div class="form-group">
                        <label>Correo electrónico:</label>
                        <input type="email" name="correo_electronico" id="add-docente-email" placeholder="docente@ejemplo.com">
                        <small style="color: var(--muted); font-size: 0.8em; margin-top: 4px; display: block;">Ejemplo: ejemplo@ejemplo.com</small>
                    </div>
                    <div class="form-group">
                        <label>Contraseña:</label>
                        <input type="password" name="contrasena" id="add-docente-pass" placeholder="Mínimo 8 caracteres">
                        <small style="color: var(--muted); font-size: 0.8em; margin-top: 4px; display: block;">Debe llevar: Al menos 8 caracteres, una mayúscula, una minúscula, un número y un carácter especial.</small>
                    </div>
                </div>
                <div class="mg-modal-actions">
                    <button type="submit" class="btn btn-primary">Crear Docente</button>
                    <button type="button" class="btn btn-secondary" onclick="manageJS.hideDocenteForm()">Cancelar</button>
                </div>
            </form>
        `;
        document.body.appendChild(addModal);

        const editModal = document.createElement('div');
        editModal.id = 'edit-docente-modal';
        editModal.className = 'mg-modal hidden';
        editModal.innerHTML = `
            <div class="mg-modal-header">
                <h3 class="mg-modal-title">Editar Docente ✏️</h3>
                <button class="modal-close" onclick="manageJS.hideDocenteForm()">&times;</button>
            </div>
            <form id="edit-docente-modal-form">
                <input type="hidden" name="id">
                <div class="mg-modal-body">
                    <div class="form-group">
                        <label>Cédula:</label>
                        <input type="text" name="nombre_usuario" id="edit-docente-cedula">
                    </div>
                    <div class="form-group">
                        <label>Correo electrónico:</label>
                        <input type="email" name="correo_electronico" id="edit-docente-email">
                        <small style="color: var(--muted); font-size: 0.8em; margin-top: 4px; display: block;">Ejemplo: ejemplo@ejemplo.com</small>
                    </div>
                    <div class="form-group">
                        <label>Sección:</label>
                        <input type="text" name="seccion" id="edit-docente-seccion" placeholder="Ej: 20, 21, 22...">
                        <small style="color: var(--muted); font-size: 0.8em; margin-top: 4px; display: block;">Puedes ingresar varias separadas por comas (ej: 20, 21).</small>
                    </div>
                    <div class="form-group">
                        <label>Contraseña (dejar en blanco para no cambiar):</label>
                        <input type="password" name="contrasena" id="edit-docente-pass">
                        <small style="color: var(--muted); font-size: 0.8em; margin-top: 4px; display: block;">Debe llevar: Al menos 8 caracteres, una mayúscula, una minúscula, un número y un carácter especial. Dejar en blanco para no cambiar.</small>
                    </div>
                </div>
                <div class="mg-modal-actions">
                    <button type="submit" class="btn btn-primary">Guardar cambios</button>
                    <button type="button" class="btn btn-secondary" onclick="manageJS.hideDocenteForm()">Cancelar</button>
                </div>
            </form>
        `;
        document.body.appendChild(editModal);

        const confirmModal = document.createElement('div');
        confirmModal.id = 'confirm-disable-docente-modal';
        confirmModal.className = 'mg-modal hidden';
        confirmModal.innerHTML = `
            <div class="mg-modal-header">
                <h3 class="mg-modal-title">Confirmar acción ⚠️</h3>
                <button class="modal-close" onclick="manageJS.hideDocenteForm()">&times;</button>
            </div>
            <div class="mg-modal-body" style="text-align: center; padding: 1rem 0;">
                <p id="confirm-disable-docente-text" style="font-size: 1.1rem; color: var(--text);">¿Está seguro?</p>
            </div>
            <div class="mg-modal-actions" style="justify-content: center;">
                <button id="confirm-disable-docente-yes" class="btn btn-primary">Sí, confirmar</button>
                <button type="button" class="btn btn-secondary" onclick="manageJS.hideDocenteForm()">Cancelar</button>
            </div>
        `;
        document.body.appendChild(confirmModal);

        setupDocenteModalEvents();
    }

    function setupDocenteModalEvents() {
        const addForm = document.getElementById('add-docente-modal-form');
        if (addForm) {
            const cedulaInput = addForm.elements['cedula'];
            cedulaInput.addEventListener('keypress', (e) => { if (!/[0-9]/.test(e.key)) e.preventDefault(); });

            if (window.Validation) {
                Validation.applyRealTimeValidation(cedulaInput, 'numeric', { required: true });
                Validation.applyRealTimeValidation(addForm.elements['correo_electronico'], 'email', { required: true });
            }
            addForm.onsubmit = async (e) => {
                e.preventDefault();
                if (window.Validation) {
                    const rules = {
                        cedula: [{ type: 'required' }, { type: 'cedula' }],
                        correo_electronico: [{ type: 'required' }, { type: 'email' }]
                    };
                    const result = Validation.validateForm(addForm, rules);
                    if (!result.valid) return;
                }
                const fd = new FormData(addForm);
                const data = {
                    cedula: fd.get('cedula'),
                    correo_electronico: fd.get('correo_electronico'),
                    contrasena: fd.get('contrasena') || '',
                    rol: 'Docente'
                };
                const res = await manageJS.createUser(data);
                if (res && res.success) hideDocenteForm();
            };
        }

        const editForm = document.getElementById('edit-docente-modal-form');
        if (editForm) {
            const cedulaInput = editForm.elements['nombre_usuario'];
            cedulaInput.addEventListener('keypress', (e) => { if (!/[0-9]/.test(e.key)) e.preventDefault(); });

            if (window.Validation) {
                Validation.applyRealTimeValidation(cedulaInput, 'numeric', { required: true });
                Validation.applyRealTimeValidation(editForm.elements['correo_electronico'], 'email', { required: true });
                Validation.applyRealTimeValidation(editForm.elements['seccion'], 'section');
            }
            editForm.onsubmit = async (e) => {
                e.preventDefault();
                if (window.Validation) {
                    const rules = {
                        nombre_usuario: [{ type: 'required' }, { type: 'cedula' }],
                        correo_electronico: [{ type: 'required' }, { type: 'email' }],
                        seccion: [{ type: 'section' }]
                    };
                    const result = Validation.validateForm(editForm, rules);
                    if (!result.valid) return;
                }
                const fd = new FormData(editForm);
                const id = parseInt(fd.get('id'), 10);
                const fields = {
                    nombre_usuario: fd.get('nombre_usuario'),
                    correo_electronico: fd.get('correo_electronico'),
                    seccion: sortSections(fd.get('seccion') || '')
                };
                const pw = fd.get('contrasena');
                if (pw && pw.length > 0) fields.contrasena = pw;
                const res = await manageJS.updateUser({ id, fields });
                if (res && res.success) hideDocenteForm();
            };
        }

        const yesBtn = document.getElementById('confirm-disable-docente-yes');
        if (yesBtn) {
            yesBtn.onclick = async () => {
                const modal = document.getElementById('confirm-disable-docente-modal');
                const userId = parseInt(modal.getAttribute('data-user-id') || '0', 10);
                const active = parseInt(modal.getAttribute('data-active') || '0', 10);
                if (userId > 0) {
                    const res = await manageJS.toggleUser(userId, active);
                    if (res && res.success) hideDocenteForm();
                }
            };
        }
    }

    function showAddDocenteModal() {
        const modal = document.getElementById('add-docente-modal');
        const overlay = document.getElementById('docente-modals-overlay');
        if (!modal || !overlay) return;
        const form = document.getElementById('add-docente-modal-form');
        if (form) form.reset();
        modal.classList.remove('hidden');
        overlay.style.display = 'block';
    }

    async function showEditDocenteModal(docenteId) {
        const modal = document.getElementById('edit-docente-modal');
        const overlay = document.getElementById('docente-modals-overlay');
        const form = document.getElementById('edit-docente-modal-form');
        if (!modal || !overlay || !form) return;
        const res = await api('get_user', { id: docenteId });
        if (!res || !res.success) {
            showNotification('No se pudo cargar datos del docente: ' + (res.error || 'Error desconocido'), 'error');
            return;
        }
        const docente = res.user;
        form.elements['id'].value = docente.id;
        form.elements['nombre_usuario'].value = docente.nombre_usuario || '';
        form.elements['correo_electronico'].value = docente.correo_electronico || '';
        form.elements['seccion'].value = docente.seccion || '';
        modal.classList.remove('hidden');
        overlay.style.display = 'block';
    }

    function showConfirmDisableDocenteModal(docenteId, active) {
        const modal = document.getElementById('confirm-disable-docente-modal');
        const overlay = document.getElementById('docente-modals-overlay');
        const text = document.getElementById('confirm-disable-docente-text');
        if (!modal || !overlay || !text) return;
        const actionText = active ? 'habilitar' : 'inhabilitar';
        text.textContent = `¿Confirma que desea ${actionText} al docente ID ${docenteId}?`;
        modal.setAttribute('data-user-id', docenteId);
        modal.setAttribute('data-active', active ? '1' : '0');
        modal.classList.remove('hidden');
        overlay.style.display = 'block';
    }

    function hideDocenteForm() {
        const add = document.getElementById('add-docente-modal'); if (add) add.classList.add('hidden');
        const edit = document.getElementById('edit-docente-modal'); if (edit) edit.classList.add('hidden');
        const confirm = document.getElementById('confirm-disable-docente-modal'); if (confirm) confirm.classList.add('hidden');
        const overlay = document.getElementById('docente-modals-overlay'); if (overlay) overlay.style.display = 'none';
        manageJS.renderDocentes();
    }

    // Función para renderizar secciones (Administrador)
    async function renderSections() {
        try {
            const res = await api('list_sections');
            if (res.success) {
                const container = document.getElementById('sections-grid');
                if (!container) return;
                let html = '';
                if (res.sections && res.sections.length > 0) {
                    res.sections.forEach(s => {
                        html += '<div class="card section-card">';
                        html += '<h4>' + escapeHtml(s.nombre || s.nombre_seccion || ('Sección ' + (s.id || ''))) + '</h4>';
                        html += '<p>Docentes: ' + escapeHtml((s.docentes || []).length ? String((s.docentes || []).length) : '0') + '</p>';
                        html += '<p>Alumnos: ' + escapeHtml(String(s.alumnos_count || 0)) + '</p>';
                        html += '</div>';
                    });
                } else {
                    html = '<div class="no-data">No hay secciones registradas</div>';
                }
                container.innerHTML = html;
            } else {
                showNotification('Error al cargar secciones: ' + (res.error || 'Error desconocido'), 'error');
            }
        } catch (err) {
            showNotification('Error de conexión al cargar secciones: ' + err.message, 'error');
        }
    }

    // Función para mostrar modal de ordenamiento de unidades
    function showOrderModal(unitId) {
        const overlay = document.getElementById('unit-modals-overlay') || createUnitModals();
        const modal = document.getElementById('order-unit-modal');
        const form = document.getElementById('order-unit-modal-form');
        
        if (!modal || !form) return;
        
        // Cargar datos de la unidad
        api('get_unit', { id: unitId }).then(res => {
            if (res && res.success) {
                const unit = res.unit;
                form.elements['id'].value = unit.id;
                form.elements['orden'].value = unit.orden || '';
                form.elements['nombre'].value = unit.nombre || '';
                
                modal.classList.remove('hidden');
                overlay.style.display = 'block';
            } else {
                showNotification('No se pudo cargar datos de la unidad: ' + (res.error || 'Error desconocido'), 'error');
            }
        }).catch(err => {
            showNotification('Error de conexión: ' + err.message, 'error');
        });
    }

    // Función para alternar estado activo/inactivo de unidad
    async function toggleUnitActive(unitId) {
        try {
            const res = await api('toggle_unit_active', { id: unitId });
            if (res.success) {
                showNotification('Estado de unidad actualizado correctamente', 'success');
                
                // Actualizar visualmente el botón sin recargar
                const btn = document.querySelector(`button[data-id="${unitId}"][data-action="toggle"]`);
                if (btn) {
                    const isActive = res.active === 1;
                    btn.innerHTML = isActive ? '🔒 Desactivar' : '🔓 Activar';
                    btn.className = isActive ? 'btn btn-danger' : 'btn btn-success';
                }
                
                return { success: true };
            } else {
                showNotification('Error: ' + (res.error || 'Error desconocido'), 'error');
                return { success: false, error: res.error };
            }
        } catch (err) {
            showNotification('Error de conexión: ' + err.message, 'error');
            return { success: false, error: err.message };
        }
    }

    // Crear modales para unidades
    function createUnitModals() {
        if (document.getElementById('unit-modals-overlay')) return document.getElementById('unit-modals-overlay');

        const overlay = document.createElement('div');
        overlay.id = 'unit-modals-overlay';
        overlay.className = 'mg-modal-overlay';
        overlay.style.display = 'none';
        document.body.appendChild(overlay);

        // Modal de ordenamiento
        const orderModal = document.createElement('div');
        orderModal.id = 'order-unit-modal';
        orderModal.className = 'mg-modal hidden';
        orderModal.innerHTML = `
            <div class="mg-modal-header">
                <h3 class="mg-modal-title">Cambiar Orden de Unidad 📋</h3>
                <button class="modal-close" onclick="manageJS.hideUnitForm()">&times;</button>
            </div>
            <form id="order-unit-modal-form">
                <input type="hidden" name="id">
                <div class="mg-modal-body">
                    <div class="form-group">
                        <label>Nombre de la unidad:</label>
                        <input type="text" name="nombre" id="order-unit-nombre" readonly style="background: #f5f5f5; cursor: not-allowed;">
                    </div>
                    <div class="form-group">
                        <label>Orden actual:</label>
                        <input type="number" name="orden" id="order-unit-orden" min="1" placeholder="Ej: 1, 2, 3...">
                        <small style="color: var(--muted); font-size: 0.8em; margin-top: 4px; display: block;">Ingresa un número para cambiar el orden de la unidad.</small>
                    </div>
                </div>
                <div class="mg-modal-actions">
                    <button type="submit" class="btn btn-primary">Guardar Orden</button>
                    <button type="button" class="btn btn-secondary" onclick="manageJS.hideUnitForm()">Cancelar</button>
                </div>
            </form>
        `;
        document.body.appendChild(orderModal);

        setupUnitModalEvents();
        return overlay;
    }

    function setupUnitModalEvents() {
        const orderForm = document.getElementById('order-unit-modal-form');
        if (orderForm) {
            if (window.Validation) {
                Validation.applyRealTimeValidation(orderForm.elements['orden'], 'numeric', { required: true, min: 1 });
            }
            orderForm.onsubmit = async (e) => {
                e.preventDefault();
                if (window.Validation) {
                    const rules = {
                        orden: [{ type: 'required' }, { type: 'numeric', min: 1 }]
                    };
                    const result = Validation.validateForm(orderForm, rules);
                    if (!result.valid) return;
                }
                const fd = new FormData(orderForm);
                const data = {
                    id: parseInt(fd.get('id'), 10),
                    orden: parseInt(fd.get('orden'), 10)
                };
                const res = await manageJS.updateUnitOrder(data);
                if (res && res.success) hideUnitForm();
            };
        }
    }

    function hideUnitForm() {
        const order = document.getElementById('order-unit-modal'); if (order) order.classList.add('hidden');
        const overlay = document.getElementById('unit-modals-overlay'); if (overlay) overlay.style.display = 'none';
    }

    async function createUser(data) {
        try {
            const res = await api('create_user', data);
            if (res.success) {
                showNotification('Usuario creado correctamente', 'success');
                return { success: true };
            } else {
                showNotification('Error: ' + (res.error || 'Error desconocido'), 'error');
                return { success: false, error: res.error };
            }
        } catch (err) {
            showNotification('Error de conexión: ' + err.message, 'error');
            return { success: false, error: err.message };
        }
    }

    async function updateUser(data) {
        try {
            const res = await api('update_user', data);
            if (res.success) {
                showNotification('Usuario actualizado correctamente', 'success');
                return { success: true };
            } else {
                showNotification('Error: ' + (res.error || 'Error desconocido'), 'error');
                return { success: false, error: res.error };
            }
        } catch (err) {
            showNotification('Error de conexión: ' + err.message, 'error');
            return { success: false, error: err.message };
        }
    }

    async function toggleUser(userId, active) {
        try {
            const res = await api('set_active', { id: userId, active: active });
            if (res.success) {
                showNotification('Estado actualizado correctamente', 'success');
                return { success: true };
            } else {
                showNotification('Error: ' + (res.error || 'Error desconocido'), 'error');
                return { success: false, error: res.error };
            }
        } catch (err) {
            showNotification('Error de conexión: ' + err.message, 'error');
            return { success: false, error: err.message };
        }
    }

    // ✅ Exponer las funciones necesarias
    return {
        createTopic,
        updateTopic,
        deleteTopic,
        renderUsers,
        setupUserButtonListeners,
        createUserModals,
        showAddUserModal,
        showEditUserModal,
        showConfirmDisableModal,
        hideUserForm,
        renderDocentes,
        setupDocenteButtonListeners,
        renderSections,
        createUser,
        updateUser,
        toggleUser,
        createDocenteModals,
        showAddDocenteModal,
        showEditDocenteModal,
        showConfirmDisableDocenteModal,
        hideDocenteForm,
        showOrderModal,
        toggleUnitActive,
        createUnitModals,
        hideUnitForm
    };

})();

// ✅ Asignaciones globales para compatibilidad
window.renderUsers = manageJS.renderUsers;
window.setupUserButtonListeners = manageJS.setupUserButtonListeners;
window.renderDocentes = manageJS.renderDocentes;
window.setupDocenteButtonListeners = manageJS.setupDocenteButtonListeners;
window.renderSections = manageJS.renderSections;
window.createUser = manageJS.createUser;
window.updateUser = manageJS.updateUser;
window.toggleUser = manageJS.toggleUser;
window.showAddUserModal = manageJS.showAddUserModal;
window.hideUserForm = manageJS.hideUserForm;
window.createDocenteModals = manageJS.createDocenteModals;
window.showAddDocenteModal = manageJS.showAddDocenteModal;
window.showEditDocenteModal = manageJS.showEditDocenteModal;
window.showConfirmDisableDocenteModal = manageJS.showConfirmDisableDocenteModal;
window.hideDocenteForm = manageJS.hideDocenteForm;
window.showOrderModal = manageJS.showOrderModal;
window.toggleUnitActive = manageJS.toggleUnitActive;
window.createUnitModals = manageJS.createUnitModals;
window.hideUnitForm = manageJS.hideUnitForm;

// Inicialización automática de listeners
setTimeout(function() {
    try { if (window.setupUserButtonListeners) window.setupUserButtonListeners(); } catch (e) {}
    try { if (window.setupDocenteButtonListeners) window.setupDocenteButtonListeners(); } catch (e) {}
}, 50);
