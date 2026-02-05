// app/view/js/practices-inline-edit.js - Edición inline para prácticas
// Wire docente add unit button with fallback
document.addEventListener('DOMContentLoaded', ()=>{
    const btn = document.getElementById('btn-add-unit');
    if (!btn) return;
    btn.addEventListener('click', ()=>{
        if (window.openUnitEditor) return window.openUnitEditor('create');
        alert('La funcionalidad de edición no está disponible. Recarga la página o contacta al administrador.');
    });
});

// Inline editable unit content (Wikipedia-style): click título/descripcion para editar in-place
document.addEventListener('DOMContentLoaded', ()=>{
    const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

    /** Muestra una notificación con el mensaje dado y tipo opcional (info, success, error) */
    function notify(msg, type='info'){
        // prefer global notification if available
        if (window.manageJS && typeof window.manageJS.showNotification === 'function') return window.manageJS.showNotification(msg, type);
        const n = document.createElement('div'); n.className = 'notification '+type; n.textContent = msg; document.body.appendChild(n);
        setTimeout(()=>{ if (n.parentNode) n.parentNode.removeChild(n); }, 3000);
    }

    function enableInlineEdit(card){
        const titleEl = card.querySelector('.unit-info h3');
        const descEl = card.querySelector('.unit-description');
        if (!titleEl || !descEl) return;

        // create edit handle visible on hover
        card.classList.add('editable-unit');

            /** Inicia el proceso de edición creando inputs y controles para modificar título y descripción */
        function startEdit(){
            // prevent multiple editors
            if (card.querySelector('.unit-editor')) return;

            const originalTitle = titleEl.textContent.trim();
            const originalDesc = descEl.textContent.trim();

            // replace with inputs
            const titleInput = document.createElement('input'); titleInput.type = 'text'; titleInput.className = 'unit-title-input'; titleInput.value = originalTitle;
            const descTextarea = document.createElement('textarea'); descTextarea.className = 'unit-desc-textarea'; descTextarea.value = originalDesc;

            // editor container
            const editor = document.createElement('div'); editor.className = 'unit-editor';
            editor.style.display = 'flex'; editor.style.flexDirection = 'column'; editor.style.gap = '8px'; editor.style.marginTop = '8px';
            editor.appendChild(titleInput); editor.appendChild(descTextarea);

            // actions
            const actions = document.createElement('div'); actions.style.display = 'flex'; actions.style.gap = '8px'; actions.style.justifyContent = 'flex-end';
            const btnCancel = document.createElement('button'); btnCancel.className = 'btn'; btnCancel.textContent = 'Cancelar';
            const btnSave = document.createElement('button'); btnSave.className = 'btn btn-primary'; btnSave.textContent = 'Guardar';
            actions.appendChild(btnCancel); actions.appendChild(btnSave); editor.appendChild(actions);

            // hide original elements
            titleEl.style.display = 'none'; descEl.style.display = 'none';
            // insert editor after unit-info
            const info = card.querySelector('.unit-info'); info.appendChild(editor);

            btnCancel.addEventListener('click', ()=>{
                // restore
                editor.remove(); titleEl.style.display = ''; descEl.style.display = '';
            });

            btnSave.addEventListener('click', async ()=>{
                const newTitle = titleInput.value.trim();
                const newDesc = descTextarea.value.trim();
                if (!newTitle) { alert('El título no puede quedar vacío'); return; }

                // optimistic update in DOM
                titleEl.textContent = newTitle; descEl.textContent = newDesc;

                // try to persist via manage endpoint if available
                const id = parseInt(card.getAttribute('data-id') || '0', 10) || 0;
                if (!id) {
                    notify('Cambios aplicados localmente (sin id de unidad).', 'info');
                    editor.remove(); titleEl.style.display = ''; descEl.style.display = ''; return;
                }

                try {
                    const manageUrl = (window.APP_ROUTES && window.APP_ROUTES.action_manage) ? window.APP_ROUTES.action_manage : 'index.php?action=manage';
                    const resRaw = await fetch(manageUrl, {
                        method: 'POST', headers: {'Content-Type':'application/json'},
                        body: JSON.stringify({ action:'update_unit', id: id, fields: { titulo: newTitle, descripcion: newDesc }, csrf_token: csrf })
                    });
                    const resText = await resRaw.text();
                    let res;
                    try { res = JSON.parse(resText); } catch(e){ res = { success:false, error: 'Respuesta no JSON: '+resText.substring(0,200) }; }
                    if (res && res.success) {
                        notify('Unidad actualizada', 'success');
                    } else {
                        notify('No se pudo guardar en servidor: '+(res.error||'error desconocido')+'. Cambios aplicados localmente.', 'error');
                    }
                } catch (err) {
                    notify('Error de conexión: '+err.message+'. Cambios aplicados localmente.', 'error');
                }

                editor.remove(); titleEl.style.display = ''; descEl.style.display = '';
            });
        }

        // Start editing when clicking the title or description
        titleEl.addEventListener('click', (e)=>{ e.stopPropagation(); startEdit(); });
        descEl.addEventListener('click', (e)=>{ e.stopPropagation(); startEdit(); });
    }

    // Enable inline edit for each unit-card (only for Docente/Admin based on server-rendered presence of add button)
    const canEdit = Boolean(document.getElementById('btn-add-unit'));
    if (!canEdit) return; // not a docente/admin

    document.querySelectorAll('.unit-card').forEach(card => {
        enableInlineEdit(card);
    });
});
