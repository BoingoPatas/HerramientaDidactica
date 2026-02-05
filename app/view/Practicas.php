<!DOCTYPE html>
<?php
require_once __DIR__ . '/../config/Rutas.php';
require_once __DIR__ . '/../lib/Titulos.php';
?>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo generarTituloPagina($rol ?? 'Usuario', 'practices'); ?></title>
    <meta name="csrf-token" content="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>">
    <link rel="stylesheet" href="app/view/css/home.css?v=1.1">
</head>
<script src="app/view/js/navigation.js?v=1.0"></script>
    <script src="app/view/js/manage.js?v=1.0"></script>
<body>
    <div class="dashboard">
        <?php
        // Configurar variables para el sidebar y header
        $activePage = 'practices';
        $pageTitle = generarSaludoBienvenida($rol ?? 'Usuario', $nombre_usuario);
        $headerMessage = generarMensajeHeader($rol ?? 'Usuario', 'practices');
        include __DIR__ . '/includes/sidebar.php';
        include __DIR__ . '/includes/header.php';
        ?>

        <main class="content">
            <section class="content-main">
                <div class="page-header">
                    <div>
                        <h1><?php echo generarTituloEncabezado($rol ?? 'Usuario', 'practices'); ?></h1>
                        <p class="page-sub"><?php echo generarSubtituloPagina($rol ?? 'Usuario', 'practices'); ?></p>
                    </div>
                </div>

                <div class="units-and-tips-container">
                    <div class="units-grid">
                        <?php foreach ($practiceData as $data): ?>
                            <?php $unit = $data['unit']; $exercises = $data['exercises']; ?>
                            <div class="unit-card" data-id="<?php echo htmlspecialchars($unit['id']); ?>" data-unit="<?php echo htmlspecialchars($unit['slug']); ?>" data-slug="<?php echo htmlspecialchars($unit['slug']); ?>" data-orden="<?php echo htmlspecialchars($unit['orden']); ?>">
                                <div class="unit-header">
                                    <div class="unit-icon"><?php echo htmlspecialchars($unit['icono']); ?></div>
                                    <div class="unit-info">
                                        <h3><?php echo htmlspecialchars($unit['titulo']); ?></h3>
                                        <p class="unit-description"><?php echo htmlspecialchars($unit['descripcion']); ?></p>
                                    </div>
                                </div>
                                <div class="unit-progress">
                                    <div class="progress-bar">
                                        <div class="progress-fill" style="width: <?php echo htmlspecialchars($data['progress']); ?>%"></div>
                                    </div>
                                    <span class="progress-text"><?php echo htmlspecialchars($data['progress']); ?>% completado</span>
                                </div>
                                <div class="unit-exercises">
                                    <h4>Ejercicios disponibles:</h4>
                                    <div class="exercise-list">
                                        <?php foreach ($exercises as $exercise): ?>
                                            <a href="<?php echo htmlspecialchars(route('exercise', ['unit' => $unit['slug'], 'exercise' => $exercise['slug']])); ?>" class="exercise-item">
                                                <span class="exercise-number"><?php echo htmlspecialchars($exercise['orden']); ?></span>
                                                <span class="exercise-title"><?php echo htmlspecialchars($exercise['title'] ?? 'Ejercicio sin título'); ?></span>
                                                <span class="exercise-status"><?php echo $exercise['completed'] ? '✅' : '🔒'; ?></span>
                                            </a>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="content-info">
                        <div class="info-card">
                            <h3>💡 Consejos para aprender</h3>
                            <ul>
                                <li>📖 Lee las instrucciones cuidadosamente antes de escribir código</li>
                                <li>👀 Observa los ejemplos proporcionados</li>
                                <li>😌 No te preocupes por los errores, son parte del aprendizaje</li>
                                <li>🎯 Practica regularmente para mejorar tus habilidades</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </section>
        </main>
    </div>

    <?php include __DIR__ . '/includes/lsv_window.php'; ?>

    <!-- Modal del tour (solo para usuarios en tour) -->
    <div id="tour-modal" style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); display: none; z-index: 9999; align-items: center; justify-content: center;">
        <div style="background: white; border-radius: 12px; width: 90%; max-width: 600px; box-shadow: 0 20px 40px rgba(0,0,0,0.15); padding: 24px;">
            <div style="margin-bottom: 20px; display: flex; align-items: center; justify-content: space-between;">
                <h3 id="tour-title" style="margin: 0; color: #1f2937;">Tour guiado</h3>
                <button class="modal-close" id="close-tour" style="background: none; border: none; font-size: 24px; color: #6b7280; cursor: pointer;">&times;</button>
            </div>
            <div style="margin-bottom: 20px;">
                <div id="tour-content" aria-live="polite" style="line-height: 1.5;">
                    <!-- Contenido del tour se carga dinámicamente -->
                </div>
                <div id="tour-progress" style="margin: 15px 0;">
                    <div style="display: flex; justify-content: center; gap: 5px;">
                        <!-- Puntos de progreso -->
                    </div>
                </div>
            </div>
            <div style="display: flex; gap: 12px; justify-content: flex-end;">
                <button style="display: none; background: #6b7280; color: white; border: none; padding: 8px 16px; border-radius: 6px; cursor: pointer; font-size: 14px;" id="prev-tour-step">⬅️ Anterior</button>
                <button style="background: #059669; color: white; border: none; padding: 8px 16px; border-radius: 6px; cursor: pointer; font-size: 14px;" id="next-tour-step">Siguiente ➡️</button>
                <button style="display: none; background: #059669; color: white; border: none; padding: 8px 16px; border-radius: 6px; cursor: pointer; font-size: 14px;" id="finish-tour">🎉 ¡Comenzar!</button>
            </div>
        </div>
    </div>

    <!-- Overlay para highlights del tour -->
    <div id="tour-overlay" class="hidden" style="position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; background: rgba(0,0,0,0.8); z-index: 10000; display: none;">
        <div id="tour-highlight" style="position: absolute; background: rgba(255,200,0,0.3); border: 3px solid #ffa500; border-radius: 8px; pointer-events: none;"></div>
    </div>

    <!-- Modal para añadir unidad de práctica -->
    <div id="add-practice-unit-modal" style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); display: none; z-index: 10001; align-items: center; justify-content: center;">
        <div style="background: white; border-radius: 12px; width: 90%; max-width: 500px; box-shadow: 0 20px 40px rgba(0,0,0,0.15); padding: 24px;">
            <div style="margin-bottom: 20px; display: flex; align-items: center; justify-content: space-between;">
                <h3 style="margin: 0; color: #1f2937;">Añadir Unidad de Práctica</h3>
                <button class="modal-close" id="close-practice-unit-modal" style="background: none; border: none; font-size: 24px; color: #6b7280; cursor: pointer;">&times;</button>
            </div>

            <form id="add-practice-unit-form">
                <div style="margin-bottom: 16px;">
                    <label for="unit-title" style="display: block; margin-bottom: 4px; font-weight: 500; color: #374151;">Título *</label>
                    <input type="text" id="unit-title" name="title" required style="width: 100%; padding: 8px 12px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 14px;" placeholder="Ej: Variables y Tipos de Datos">
                </div>

                <div style="margin-bottom: 16px;">
                    <label for="unit-slug" style="display: block; margin-bottom: 4px; font-weight: 500; color: #374151;">Slug (opcional)</label>
                    <input type="text" id="unit-slug" name="slug" style="width: 100%; padding: 8px 12px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 14px;" placeholder="Se generará automáticamente si está vacío">
                    <small style="color: #6b7280; font-size: 12px;">Identificador único para URLs (solo letras, números y guiones)</small>
                </div>

                <div style="margin-bottom: 16px;">
                    <label for="unit-description" style="display: block; margin-bottom: 4px; font-weight: 500; color: #374151;">Descripción</label>
                    <textarea id="unit-description" name="description" rows="3" style="width: 100%; padding: 8px 12px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 14px; resize: vertical;" placeholder="Descripción breve de la unidad"></textarea>
                </div>

                <div style="margin-bottom: 16px;">
                    <label for="unit-icon" style="display: block; margin-bottom: 4px; font-weight: 500; color: #374151;">Icono (opcional)</label>
                    <select id="unit-icon" name="icon" style="width: 100%; padding: 8px 12px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 14px;">
                        <option value="">Sin icono</option>
                        <option value="📚">📚 Libro</option>
                        <option value="🔢">🔢 Números</option>
                        <option value="📝">📝 Escritura</option>
                        <option value="🔀">🔀 Lógica</option>
                        <option value="🔄">🔄 Bucles</option>
                        <option value="📊">📊 Datos</option>
                        <option value="⚡">⚡ Avanzado</option>
                        <option value="🎯">🎯 Proyecto</option>
                    </select>
                </div>

                <div style="margin-bottom: 24px;">
                    <label for="unit-orden" style="display: block; margin-bottom: 4px; font-weight: 500; color: #374151;">Orden</label>
                    <input type="number" id="unit-orden" name="orden" min="0" value="0" style="width: 100%; padding: 8px 12px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 14px;">
                    <small style="color: #6b7280; font-size: 12px;">Orden de aparición (0 = al final)</small>
                </div>

                <div id="practice-unit-error" style="display: none; background: #fef2f2; color: #dc2626; padding: 12px; border-radius: 6px; margin-bottom: 16px; font-size: 14px;"></div>

                <div style="display: flex; gap: 12px; justify-content: flex-end;">
                    <button type="button" id="cancel-practice-unit" style="background: #6b7280; color: white; border: none; padding: 8px 16px; border-radius: 6px; cursor: pointer; font-size: 14px;">Cancelar</button>
                    <button type="submit" id="submit-practice-unit" style="background: #059669; color: white; border: none; padding: 8px 16px; border-radius: 6px; cursor: pointer; font-size: 14px;">Crear Unidad</button>
                </div>
            </form>
        </div>
    </div>

    <script src="app/view/js/routes_dynamic.php?v=1.0"></script>
    <script src="app/view/js/lsv.js?v=1.0"></script>
    <script src="app/view/js/content.js?v=1.0"></script>
    <script src="app/view/js/tour.js?v=1.0"></script>

    <script>
        // Funcionalidad para añadir unidades de práctica
        document.addEventListener('DOMContentLoaded', function() {
            const addBtn = document.getElementById('add-practice-unit-btn');
            const modal = document.getElementById('add-practice-unit-modal');
            const closeBtn = document.getElementById('close-practice-unit-modal');
            const cancelBtn = document.getElementById('cancel-practice-unit');
            const form = document.getElementById('add-practice-unit-form');
            const errorDiv = document.getElementById('practice-unit-error');
            const submitBtn = document.getElementById('submit-practice-unit');
            const titleInput = document.getElementById('unit-title');
            const slugInput = document.getElementById('unit-slug');

            // Mostrar modal
            if (addBtn) {
                addBtn.addEventListener('click', function() {
                    modal.style.display = 'flex';
                    titleInput.focus();
                });
            }

            // Cerrar modal
            function closeModal() {
                modal.style.display = 'none';
                form.reset();
                errorDiv.style.display = 'none';
                submitBtn.disabled = false;
                submitBtn.textContent = 'Crear Unidad';
            }

            if (closeBtn) closeBtn.addEventListener('click', closeModal);
            if (cancelBtn) cancelBtn.addEventListener('click', closeModal);

            // Cerrar modal al hacer clic fuera
            modal.addEventListener('click', function(e) {
                if (e.target === modal) {
                    closeModal();
                }
            });

            // Generar slug automáticamente desde el título
            titleInput.addEventListener('input', function() {
                if (slugInput.value === '') {
                    const slug = titleInput.value
                        .toLowerCase()
                        .replace(/[^a-z0-9\s-]/g, '')
                        .replace(/\s+/g, '-')
                        .replace(/-+/g, '-')
                        .trim();
                    slugInput.value = slug;
                }
            });

            // Enviar formulario
            form.addEventListener('submit', async function(e) {
                e.preventDefault();

                const formData = new FormData(form);
                const data = {
                    action: 'create_practice_unit',
                    csrf_token: document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    title: formData.get('title').trim(),
                    slug: formData.get('slug').trim(),
                    description: formData.get('description').trim(),
                    icon: formData.get('icon'),
                    orden: parseInt(formData.get('orden')) || 0
                };

                // Validación básica
                if (!data.title) {
                    showError('El título es obligatorio');
                    return;
                }

                submitBtn.disabled = true;
                submitBtn.textContent = 'Creando...';
                errorDiv.style.display = 'none';

                try {
                    const response = await fetch('index.php?action=manage', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify(data)
                    });

                    const result = await response.json();

                    if (result.success) {
                        // Recargar la página para mostrar la nueva unidad
                        location.reload();
                    } else {
                        showError(result.error || 'Error al crear la unidad');
                    }
                } catch (error) {
                    console.error('Error:', error);
                    showError('Error de conexión. Inténtalo de nuevo.');
                } finally {
                    submitBtn.disabled = false;
                    submitBtn.textContent = 'Crear Unidad';
                }
            });

            function showError(message) {
                errorDiv.textContent = message;
                errorDiv.style.display = 'block';
                errorDiv.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            }
        });
    </script>
</body>
</html>
