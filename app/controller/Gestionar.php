<?php
// Configuración de errores: logging habilitado, display deshabilitado por seguridad
error_reporting(E_ALL);
ini_set('display_errors', 0); // Deshabilitado por seguridad
ini_set('log_errors', 1); // Logging habilitado

// Endpoint AJAX para gestión (usuarios, docentes, unidades, evaluaciones, reportes)
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/BaseDatos.php';
require_once __DIR__ . '/../model/ModeloUsuario.php';
require_once __DIR__ . '/../model/ModeloUnidad.php';
require_once __DIR__ . '/../model/Registrador.php';
require_once __DIR__ . '/../model/ModeloEvaluacion.php';
require_once __DIR__ . '/../model/ModeloPractica.php';
require_once __DIR__ . '/../model/ModeloTema.php';
require_once __DIR__ . '/../lib/Validacion.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['usuario'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'No autenticado']);
    exit;
}

// Manejar solicitudes GET y POST
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Para solicitudes GET, no hay cuerpo JSON, solo parámetros en la URL
    $action = $_GET['action'] ?? '';
    $payload = []; // No hay payload para GET
} else {
    // Para solicitudes POST, obtener el payload del cuerpo JSON
    $raw = file_get_contents('php://input');
    $payload = json_decode($raw, true);
    if (!is_array($payload)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'JSON inválido']);
        exit;
    }

    $csrf = $payload['csrf_token'] ?? '';
    if (empty($csrf) || !hash_equals($_SESSION['csrf_token'] ?? '', $csrf)) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'CSRF inválido']);
        exit;
    }

    $action = $payload['action'] ?? '';
}
$db = new Database();
$conn = $db->getConnection();
error_log("DEBUG: Database connection established: " . ($conn ? 'SUCCESS' : 'FAILED'));
if (!$conn) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database connection failed']);
    exit;
}
$userModel = new ModeloUsuario($db);
$unitModel = new ModeloUnidad($db);
$evalModel = new EvaluationModel($db);
$practiceModel = new PracticeModel($db);
$topicModel = new ModeloTema($db);
$role = $_SESSION['rol'] ?? 'Usuario';
error_log("DEBUG: All models instantiated successfully, role: {$role}");

try {
    switch ($action) {
        case 'list_users':
            if (!in_array($role, ['Docente','Administrador'], true)) {
                http_response_code(403);
                echo json_encode(['success' => false, 'error' => 'Acceso denegado']);
                exit;
            }
            $users = $userModel->getUsersByRole('Usuario');
            echo json_encode(['success' => true, 'users' => $users]);
            break;

        case 'list_docentes':
            if ($role !== 'Administrador') {
                http_response_code(403);
                echo json_encode(['success' => false, 'error' => 'Acceso denegado']);
                exit;
            }
            $docentes = $userModel->getUsersByRoles(['Docente']);
            echo json_encode(['success' => true, 'docentes' => $docentes]);
            break;

        case 'get_user':
            // Obtener un solo usuario por ID (para prefilling en modal de edición)
            if (!in_array($role, ['Docente','Administrador'], true)) {
                http_response_code(403);
                echo json_encode(['success' => false, 'error' => 'Acceso denegado']);
                exit;
            }
            $id = (int)($payload['id'] ?? 0);
            if ($id <= 0) { http_response_code(422); echo json_encode(['success'=>false,'error'=>'ID inválido']); exit; }
            $userData = $userModel->getUserById($id);
            if (!$userData) { http_response_code(404); echo json_encode(['success'=>false,'error'=>'Usuario no encontrado']); exit; }
            echo json_encode(['success' => true, 'user' => $userData]);
            break;

        case 'list_sections':
            if ($role !== 'Administrador') {
                http_response_code(403);
                echo json_encode(['success' => false, 'error' => 'Acceso denegado']);
                exit;
            }
            $sections = $userModel->getSectionsData();
            echo json_encode(['success' => true, 'sections' => $sections]);
            break;

        case 'create_user':
            // Lógica para crear un nuevo usuario/docente
            if (!in_array($role, ['Docente','Administrador'], true)) {
                http_response_code(403);
                echo json_encode(['success' => false, 'error' => 'Acceso denegado']);
                exit;
            }
            $r = $payload['rol'] ?? 'Usuario';

            // Ambos usan cédula como identificador
            $cedula = trim((string)($payload['cedula'] ?? ''));
            $correo = trim((string)($payload['correo_electronico'] ?? ''));
            $pwd = $payload['contrasena'] ?? '';

            // Validación usando el sistema centralizado
            $validation = Validation::validateUserData($cedula, $correo, $pwd, $r);

            if ($validation !== true) {
                http_response_code(422);
                // Devolver el primer error encontrado
                $firstError = reset($validation);
                echo json_encode(['success' => false, 'error' => $firstError]);
                exit;
            }

            // Si no se proporciona contraseña, usar la cédula
            if ($pwd === '') {
                $pwd = $cedula;
            }

            $nombre = $cedula; // Usar cédula como nombre de usuario para ambos

            $ok = $userModel->createUser($nombre, $correo, $pwd, $r);
            if ($ok) {
                Registrador::log($_SESSION['usuario'], $role, 'usuario_crear', $nombre);
                echo json_encode(['success' => true]);
            } else {
                http_response_code(500);
                echo json_encode(['success' => false, 'error' => 'No se pudo crear usuario. Correo/Usuario duplicado.']);
            }
            break;

        case 'update_user':
            if (!in_array($role, ['Docente','Administrador'], true)) {
                http_response_code(403);
                echo json_encode(['success' => false, 'error' => 'Acceso denegado']);
                exit;
            }
            $id = (int)($payload['id'] ?? 0);
            $fields = $payload['fields'] ?? [];
            if ($id <= 0 || !is_array($fields)) {
                http_response_code(422);
                echo json_encode(['success' => false, 'error' => 'Parámetros inválidos']);
                exit;
            }

            // Extraer valores para validación
            $cedula = $fields['nombre_usuario'] ?? '';
            $correo = $fields['correo_electronico'] ?? '';
            $password = $fields['contrasena'] ?? '';
            $seccion = $fields['seccion'] ?? '';

            // Determinar el rol del usuario que se está actualizando
            $userData = $userModel->getUserById($id);
            $userRole = $userData ? $userData['rol'] : 'Usuario';

            // Validación usando el sistema centralizado
            $validation = Validation::validateUserData($cedula, $correo, $password, $userRole, $seccion);
            if ($validation !== true) {
                http_response_code(422);
                // Devolver el primer error encontrado
                $firstError = reset($validation);
                echo json_encode(['success' => false, 'error' => $firstError]);
                exit;
            }

            $ok = $userModel->updateUser($id, $fields);
            if ($ok) {
                Registrador::log($_SESSION['usuario'], $role, 'usuario_actualizar', "id:$id");
                echo json_encode(['success' => true]);
            } else {
                http_response_code(500);
                echo json_encode(['success' => false, 'error' => 'Error actualizando usuario']);
            }
            break;

        case 'set_active':
            // Lógica para habilitar/inhabilitar usuarios/docentes
            if (!in_array($role, ['Docente','Administrador'], true)) {
                http_response_code(403);
                echo json_encode(['success' => false, 'error' => 'Acceso denegado']);
                exit;
            }
            $id = (int)($payload['id'] ?? 0);
            $active = (int)($payload['active'] ?? 0); // 1 para habilitar, 0 para inhabilitar
            
            // Validación: ID válido y estado es 0 o 1
            if ($id <= 0 || ($active !== 0 && $active !== 1)) {
                http_response_code(422);
                echo json_encode(['success' => false, 'error' => 'ID o estado de activación inválido']);
                exit;
            }
            
            // Ejecución: Se usa setUserActive del modelo
            $ok = $userModel->setUserActive($id, $active);
            
            if ($ok) {
                // Loggear acción correcta
                $action_log = $active ? 'usuario_habilitar' : 'usuario_inhabilitar';
                Registrador::log($_SESSION['usuario'], $role, $action_log, "id:$id active:$active");
                echo json_encode(['success' => true]);
            } else {
                http_response_code(500);
                echo json_encode(['success' => false, 'error' => 'Error actualizando estado. El ID podría no existir.']);
            }
            break;

        case 'read_log':
            if (!in_array($role, ['Docente','Administrador'], true)) {
                http_response_code(403);
                echo json_encode(['success' => false, 'error' => 'Acceso denegado']);
                exit;
            }
            $logFile = __DIR__ . '/../logs/bitacora.log';
            if (!file_exists($logFile)) {
                echo json_encode(['success' => true, 'lines' => []]);
                exit;
            }
            $lines = file($logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            $lines = array_slice($lines, -200);
            $entries = [];
            foreach ($lines as $ln) {
                $decoded = json_decode($ln, true);
                if (is_array($decoded)) $entries[] = $decoded; else $entries[] = ['raw' => $ln];
            }
            echo json_encode(['success' => true, 'entries' => $entries]);
            break;

        // Unidades (Contenido)
        case 'list_units':
            if (!in_array($role, ['Docente','Administrador'], true)) { http_response_code(403); echo json_encode(['success'=>false,'error'=>'Acceso denegado']); exit; }
            $userId = $_SESSION['user_id'] ?? null;
            $units = $unitModel->listUnits($userId, $role); echo json_encode(['success'=>true,'units'=>$units]); break;

        case 'get_unit':
            if (!in_array($role, ['Docente','Administrador'], true)) { http_response_code(403); echo json_encode(['success'=>false,'error'=>'Acceso denegado']); exit; }
            $id = (int)($payload['id'] ?? 0);
            if ($id <= 0) { http_response_code(422); echo json_encode(['success'=>false,'error'=>'ID inválido']); exit; }
            $unit = $unitModel->getUnit($id);
            if (!$unit) { http_response_code(404); echo json_encode(['success'=>false,'error'=>'No encontrado']); exit; }
            echo json_encode(['success'=>true,'unit'=>$unit]); break;

        case 'create_unit':
            if (!in_array($role, ['Docente','Administrador'], true)) { http_response_code(403); echo json_encode(['success'=>false,'error'=>'Acceso denegado']); exit; }
            $slug = trim((string)($payload['slug'] ?? '')); $titulo = trim((string)($payload['titulo'] ?? ''));
            $descripcion = $payload['descripcion'] ?? ''; $orden = (int)($payload['orden'] ?? 0);

            // Validación usando el sistema centralizado
            $validation = Validation::validateUnitData($slug, $titulo);
            if ($validation !== true) {
                http_response_code(422);
                $firstError = reset($validation);
                echo json_encode(['success'=>false,'error'=>$firstError]);
                exit;
            }
            // Si el slug está vacío, generar un slug simple en el lado del servidor también (como respaldo)
            if ($slug === '') {
                $slug = preg_replace('/[^a-z0-9\-]/', '', strtolower(trim(preg_replace('/\s+/', '-', $titulo))));
            } else {
                // Normalizar el slug proporcionado
                $slug = preg_replace('/[^a-z0-9\-]/', '', strtolower(trim(preg_replace('/\s+/', '-', $slug))));
            }

            // Si el slug ya existe, intentar añadir sufijos (-1, -2, ...) hasta un límite para encontrar un slug único
            $base = $slug;
            $try = 0; $maxTries = 8;
            while ($unitModel->slugExists($slug) && $try < $maxTries) {
                $try++;
                $slug = $base . '-' . $try;
            }
            if ($unitModel->slugExists($slug)) {
                http_response_code(409);
                echo json_encode(['success'=>false,'error'=>'No se pudo generar un slug único. Intente con otro título o slug.']);
                exit;
            }

            $docenteId = ($role === 'Docente') ? ($_SESSION['user_id'] ?? null) : null;
            $insertId = $unitModel->createUnit($slug,$titulo,$descripcion,$orden, $docenteId);
            if ($insertId) {
                Registrador::log($_SESSION['usuario'],$role,'unidad_crear',$slug);
                // Obtener la unidad creada para devolver el objeto completo
                $created = $unitModel->getUnit((int)$insertId);
                echo json_encode(['success'=>true,'unit'=>$created]);
            } else {
                http_response_code(500);
                echo json_encode(['success'=>false,'error'=>'Error creando unidad (slug duplicado o error de BD)']);
            }
            break;

        case 'update_unit':
            if (!in_array($role, ['Docente','Administrador'], true)) { http_response_code(403); echo json_encode(['success'=>false,'error'=>'Acceso denegado']); exit; }
            $id = (int)($payload['id'] ?? 0); $fields = $payload['fields'] ?? [];
            if ($id<=0||!is_array($fields)) { http_response_code(422); echo json_encode(['success'=>false,'error'=>'Parámetros inválidos']); exit; }
            $ok = $unitModel->updateUnit($id,$fields);
            if ($ok) { Registrador::log($_SESSION['usuario'],$role,'unidad_actualizar','id:'.$id); echo json_encode(['success'=>true]); } else { http_response_code(500); echo json_encode(['success'=>false,'error'=>'Error actualizando unidad']); }
            break;

        case 'delete_unit':
            if (!in_array($role, ['Docente','Administrador'], true)) { http_response_code(403); echo json_encode(['success'=>false,'error'=>'Acceso denegado']); exit; }
            $id = (int)($payload['id'] ?? 0); if ($id<=0) { http_response_code(422); echo json_encode(['success'=>false,'error'=>'ID inválido']); exit; }
            $ok = $unitModel->deleteUnit($id);
            if ($ok) { Registrador::log($_SESSION['usuario'],$role,'unidad_eliminar','id:'.$id); echo json_encode(['success'=>true]); } else { http_response_code(500); echo json_encode(['success'=>false,'error'=>'Error eliminando unidad']); }
            break;

        // Evaluaciones (metadata persisted)
        case 'list_evaluations':
            // Solo docentes/administradores pueden listar evaluaciones editables a través de este endpoint
            if (!in_array($role, ['Docente','Administrador'], true)) { http_response_code(403); echo json_encode(['success'=>false,'error'=>'Acceso denegado']); exit; }
            $evals = $evalModel->listEvaluations(); echo json_encode(['success'=>true,'evaluations'=>$evals]); break;

        case 'get_evaluation':
            if (!in_array($role, ['Docente','Administrador'], true)) { http_response_code(403); echo json_encode(['success'=>false,'error'=>'Acceso denegado']); exit; }
            $key = trim((string)($payload['key'] ?? ''));
            if ($key === '') { http_response_code(422); echo json_encode(['success'=>false,'error'=>'Key inválida']); exit; }
            $ev = $evalModel->getEvaluation($key);
            if ($ev === null) { http_response_code(404); echo json_encode(['success'=>false,'error'=>'No encontrado']); exit; }
            echo json_encode(['success'=>true,'evaluation'=>$ev]); break;

        case 'create_evaluation':
            if (!in_array($role, ['Docente','Administrador'], true)) { http_response_code(403); echo json_encode(['success'=>false,'error'=>'Acceso denegado']); exit; }
            $key = trim((string)($payload['key'] ?? ''));
            $data = $payload['data'] ?? [];
            $title = trim((string)($data['title'] ?? ''));

            // Validación usando el sistema centralizado
            $validation = Validation::validateEvaluationData($key, $title);
            if ($validation !== true) {
                http_response_code(422);
                $firstError = reset($validation);
                echo json_encode(['success'=>false,'error'=>$firstError]);
                exit;
            }

            if (!is_array($data)) { http_response_code(422); echo json_encode(['success'=>false,'error'=>'Parámetros inválidos']); exit; }
            $ok = $evalModel->createEvaluation($key, $data);
            if ($ok) { Registrador::log($_SESSION['usuario'],$role,'evaluacion_crear',$key); echo json_encode(['success'=>true]); } else { http_response_code(500); echo json_encode(['success'=>false,'error'=>'No se pudo crear evaluación (¿ya existe?)']); }
            break;

        case 'update_evaluation':
            if (!in_array($role, ['Docente','Administrador'], true)) { http_response_code(403); echo json_encode(['success'=>false,'error'=>'Acceso denegado']); exit; }
            $key = trim((string)($payload['key'] ?? ''));
            $fields = $payload['fields'] ?? [];
            if ($key === '' || !is_array($fields)) { http_response_code(422); echo json_encode(['success'=>false,'error'=>'Parámetros inválidos']); exit; }
            $ok = $evalModel->updateEvaluation($key, $fields);
            if ($ok) { Registrador::log($_SESSION['usuario'],$role,'evaluacion_actualizar',$key); echo json_encode(['success'=>true]); } else { http_response_code(500); echo json_encode(['success'=>false,'error'=>'Error actualizando evaluación']); }
            break;

        case 'delete_evaluation':
            if (!in_array($role, ['Docente','Administrador'], true)) { http_response_code(403); echo json_encode(['success'=>false,'error'=>'Acceso denegado']); exit; }
            $key = trim((string)($payload['key'] ?? ''));
            if ($key === '') { http_response_code(422); echo json_encode(['success'=>false,'error'=>'Key inválida']); exit; }
            $ok = $evalModel->deleteEvaluation($key);
            if ($ok) { Registrador::log($_SESSION['usuario'],$role,'evaluacion_eliminar',$key); echo json_encode(['success'=>true]); } else { http_response_code(500); echo json_encode(['success'=>false,'error'=>'Error eliminando evaluación']); }
            break;

        // Unidades de Práctica
        case 'create_practice_unit':
            if (!in_array($role, ['Docente','Administrador'], true)) { http_response_code(403); echo json_encode(['success'=>false,'error'=>'Acceso denegado']); exit; }
            $slug = trim((string)($payload['slug'] ?? '')); $title = trim((string)($payload['title'] ?? ''));
            $description = $payload['description'] ?? ''; $icon = trim((string)($payload['icon'] ?? '')); $orden = (int)($payload['orden'] ?? 0);
            if ($title === '') { http_response_code(422); echo json_encode(['success'=>false,'error'=>'El título de la unidad es obligatorio']); exit; }
            // Si el slug está vacío, generar un slug simple en el lado del servidor también (como respaldo)
            if ($slug === '') {
                $slug = preg_replace('/[^a-z0-9\-]/', '', strtolower(trim(preg_replace('/\s+/', '-', $title))));
            } else {
                // Normalizar el slug proporcionado
                $slug = preg_replace('/[^a-z0-9\-]/', '', strtolower(trim(preg_replace('/\s+/', '-', $slug))));
            }

            // Si el slug ya existe, intentar añadir sufijos (-1, -2, ...) hasta un límite para encontrar un slug único
            $base = $slug;
            $try = 0; $maxTries = 8;
            while ($practiceModel->practiceUnitSlugExists($slug) && $try < $maxTries) {
                $try++;
                $slug = $base . '-' . $try;
            }
            if ($practiceModel->practiceUnitSlugExists($slug)) {
                http_response_code(409);
                echo json_encode(['success'=>false,'error'=>'No se pudo generar un slug único. Intente con otro título o slug.']);
                exit;
            }

            $insertId = $practiceModel->createPracticeUnit($slug, $title, $description, $icon, $orden);
            if ($insertId) {
                Registrador::log($_SESSION['usuario'],$role,'unidad_practica_crear',$slug);
                // Obtener la unidad creada para devolver el objeto completo
                $created = $practiceModel->getPracticeUnit($slug);
                echo json_encode(['success'=>true,'unit'=>$created]);
            } else {
                http_response_code(500);
                echo json_encode(['success'=>false,'error'=>'Error creando unidad de práctica (slug duplicado o error de BD)']);
            }
            break;

        // Ejercicios
        case 'update_exercise':
            if (!in_array($role, ['Docente','Administrador'], true)) { http_response_code(403); echo json_encode(['success'=>false,'error'=>'Acceso denegado']); exit; }
            $unit_slug = trim((string)($payload['unit'] ?? '')); $exercise_slug = trim((string)($payload['exercise'] ?? '')); $fields = $payload['fields'] ?? [];
            if ($unit_slug === '' || $exercise_slug === '' || !is_array($fields)) { http_response_code(422); echo json_encode(['success'=>false,'error'=>'Parámetros inválidos']); exit; }
            $ok = $practiceModel->updateExercise($unit_slug, $exercise_slug, $fields);
            if ($ok) { Registrador::log($_SESSION['usuario'],$role,'ejercicio_actualizar',$unit_slug.'/'.$exercise_slug); echo json_encode(['success'=>true]); } else { http_response_code(500); echo json_encode(['success'=>false,'error'=>'Error actualizando ejercicio']); }
            break;

        // Temas
        case 'create_topic':
            if (!in_array($role, ['Docente','Administrador'], true)) { http_response_code(403); echo json_encode(['success'=>false,'error'=>'Acceso denegado']); exit; }
            $unit_id = (int)($payload['unit_id'] ?? 0);
            $name = trim((string)($payload['name'] ?? ''));
            $description = trim((string)($payload['description'] ?? ''));
            if ($unit_id <= 0 || $name === '') { http_response_code(422); echo json_encode(['success'=>false,'error'=>'Parámetros inválidos']); exit; }
            $insertId = $topicModel->crearTema($unit_id, $name, $description);
            if ($insertId) {
                Registrador::log($_SESSION['usuario'],$role,'tema_crear','id:'.$insertId);
                echo json_encode(['success'=>true,'id'=>$insertId,'message'=>'Tema creado correctamente']);
            } else {
                http_response_code(500);
                echo json_encode(['success'=>false,'error'=>'Error creando tema']);
            }
            break;

        case 'update_topic':
            if (!in_array($role, ['Docente','Administrador'], true)) { http_response_code(403); echo json_encode(['success'=>false,'error'=>'Acceso denegado']); exit; }
            $id = (int)($payload['id'] ?? 0);
            $fields = $payload['fields'] ?? [];
            if ($id <= 0 || !is_array($fields)) { http_response_code(422); echo json_encode(['success'=>false,'error'=>'Parámetros inválidos']); exit; }
            $ok = $topicModel->actualizarTema($id, $fields);
            if ($ok) {
                Registrador::log($_SESSION['usuario'],$role,'tema_actualizar','id:'.$id);
                echo json_encode(['success'=>true,'message'=>'Tema actualizado correctamente']);
            } else {
                http_response_code(500);
                echo json_encode(['success'=>false,'error'=>'Error actualizando tema']);
            }
            break;

        case 'delete_topic':
            if (!in_array($role, ['Docente','Administrador'], true)) { http_response_code(403); echo json_encode(['success'=>false,'error'=>'Acceso denegado']); exit; }
            $id = (int)($payload['id'] ?? 0);
            if ($id <= 0) { http_response_code(422); echo json_encode(['success'=>false,'error'=>'ID inválido']); exit; }
            $ok = $topicModel->eliminarTema($id);
            if ($ok) {
                Registrador::log($_SESSION['usuario'],$role,'tema_eliminar','id:'.$id);
                echo json_encode(['success'=>true,'message'=>'Tema eliminado correctamente']);
            } else {
                http_response_code(500);
                echo json_encode(['success'=>false,'error'=>'Error eliminando tema']);
            }
            break;

        case 'delete_exercise':
            if (!in_array($role, ['Docente','Administrador'], true)) { http_response_code(403); echo json_encode(['success'=>false,'error'=>'Acceso denegado']); exit; }
            $unit_slug = trim((string)($payload['unit_slug'] ?? ''));
            $exercise_slug = trim((string)($payload['exercise_slug'] ?? ''));
            if ($unit_slug === '' || $exercise_slug === '') { http_response_code(422); echo json_encode(['success'=>false,'error'=>'Parámetros inválidos']); exit; }
            $ok = $practiceModel->deleteExercise($unit_slug, $exercise_slug);
            if ($ok) {
                Registrador::log($_SESSION['usuario'],$role,'ejercicio_eliminar',$unit_slug.'/'.$exercise_slug);
                echo json_encode(['success'=>true,'message'=>'Ejercicio eliminado correctamente']);
            } else {
                http_response_code(500);
                echo json_encode(['success'=>false,'error'=>'Error eliminando ejercicio']);
            }
            break;

        case 'delete_evaluation':
            if (!in_array($role, ['Docente','Administrador'], true)) { http_response_code(403); echo json_encode(['success'=>false,'error'=>'Acceso denegado']); exit; }
            $unit_slug = trim((string)($payload['unit_slug'] ?? ''));
            $exercise_slug = trim((string)($payload['exercise_slug'] ?? ''));
            if ($unit_slug === '' || $exercise_slug === '') { http_response_code(422); echo json_encode(['success'=>false,'error'=>'Parámetros inválidos']); exit; }
            $ok = $practiceModel->deleteEvaluation($unit_slug, $exercise_slug);
            if ($ok) {
                Registrador::log($_SESSION['usuario'],$role,'evaluacion_eliminar',$unit_slug.'/'.$exercise_slug);
                echo json_encode(['success'=>true,'message'=>'Evaluación eliminada correctamente']);
            } else {
                http_response_code(500);
                echo json_encode(['success'=>false,'error'=>'Error eliminando evaluación']);
            }
            break;

        case 'set_unit_active':
            if (!in_array($role, ['Docente','Administrador'], true)) { http_response_code(403); echo json_encode(['success'=>false,'error'=>'Acceso denegado']); exit; }
            $id = (int)($payload['id'] ?? 0);
            $active = (int)($payload['active'] ?? 0);
            if ($id <= 0 || ($active !== 0 && $active !== 1)) {
                http_response_code(422);
                echo json_encode(['success'=>false,'error'=>'Parámetros inválidos']);
                exit;
            }
            $ok = $unitModel->setUnitActive($id, $active === 1);
            if ($ok) {
                Registrador::log($_SESSION['usuario'],$role,'unidad_'.($active?'habilitar':'inhabilitar'),'id:'.$id);
                echo json_encode(['success'=>true]);
            } else {
                http_response_code(500);
                echo json_encode(['success'=>false,'error'=>'Error actualizando estado']);
            }
            break;

        case 'set_topic_active':
            error_log("DEBUG: set_topic_active action called with id=" . ($payload['id'] ?? 'null') . ", active=" . ($payload['active'] ?? 'null'));
            if (!in_array($role, ['Docente','Administrador'], true)) { http_response_code(403); echo json_encode(['success'=>false,'error'=>'Acceso denegado']); exit; }
            $id = (int)($payload['id'] ?? 0);
            $active = (int)($payload['active'] ?? 0);
            error_log("DEBUG: set_topic_active parsed id={$id}, active={$active}");
            if ($id <= 0 || ($active !== 0 && $active !== 1)) {
                http_response_code(422);
                echo json_encode(['success'=>false,'error'=>'Parámetros inválidos']);
                exit;
            }
            error_log("DEBUG: Calling topicModel->setTopicActive({$id}, " . ($active === 1 ? 'true' : 'false') . ")");
            $ok = $topicModel->setTopicActive($id, $active === 1);
            error_log("DEBUG: setTopicActive returned: " . ($ok ? 'true' : 'false'));
            if ($ok) {
                Registrador::log($_SESSION['usuario'],$role,'tema_'.($active?'habilitar':'inhabilitar'),'id:'.$id);
                echo json_encode(['success'=>true]);
            } else {
                http_response_code(500);
                echo json_encode(['success'=>false,'error'=>'Error actualizando estado']);
            }
            break;

        case 'set_exercise_active':
            if (!in_array($role, ['Docente','Administrador'], true)) { http_response_code(403); echo json_encode(['success'=>false,'error'=>'Acceso denegado']); exit; }
            $unit_slug = trim((string)($payload['unit_slug'] ?? ''));
            $exercise_slug = trim((string)($payload['exercise_slug'] ?? ''));
            $active = (int)($payload['active'] ?? 0);
            if ($unit_slug === '' || $exercise_slug === '' || ($active !== 0 && $active !== 1)) {
                http_response_code(422);
                echo json_encode(['success'=>false,'error'=>'Parámetros inválidos']);
                exit;
            }
            $ok = $practiceModel->setExerciseActive($unit_slug, $exercise_slug, $active === 1);
            if ($ok) {
                Registrador::log($_SESSION['usuario'],$role,'ejercicio_'.($active?'habilitar':'inhabilitar'),$unit_slug.'/'.$exercise_slug);
                echo json_encode(['success'=>true]);
            } else {
                http_response_code(500);
                echo json_encode(['success'=>false,'error'=>'Error actualizando estado']);
            }
            break;

        case 'set_evaluation_active':
            if (!in_array($role, ['Docente','Administrador'], true)) { http_response_code(403); echo json_encode(['success'=>false,'error'=>'Acceso denegado']); exit; }
            $unit_slug = trim((string)($payload['unit_slug'] ?? ''));
            $exercise_slug = trim((string)($payload['exercise_slug'] ?? ''));
            $active = (int)($payload['active'] ?? 0);
            if ($unit_slug === '' || $exercise_slug === '' || ($active !== 0 && $active !== 1)) {
                http_response_code(422);
                echo json_encode(['success'=>false,'error'=>'Parámetros inválidos']);
                exit;
            }
            $ok = $practiceModel->setEvaluationActive($unit_slug, $exercise_slug, $active === 1);
            if ($ok) {
                Registrador::log($_SESSION['usuario'],$role,'evaluacion_'.($active?'habilitar':'inhabilitar'),$unit_slug.'/'.$exercise_slug);
                echo json_encode(['success'=>true]);
            } else {
                http_response_code(500);
                echo json_encode(['success'=>false,'error'=>'Error actualizando estado']);
            }
            break;

        case 'set_evaluation_unit_active':
            if (!in_array($role, ['Docente','Administrador'], true)) { http_response_code(403); echo json_encode(['success'=>false,'error'=>'Acceso denegado']); exit; }
            $slug = trim((string)($payload['slug'] ?? ''));
            $active = (int)($payload['active'] ?? 0);
            if ($slug === '' || ($active !== 0 && $active !== 1)) {
                http_response_code(422);
                echo json_encode(['success'=>false,'error'=>'Parámetros inválidos']);
                exit;
            }
            $ok = $evalModel->setEvaluationUnitActive($slug, $active === 1);
            if ($ok) {
                Registrador::log($_SESSION['usuario'],$role,'evaluacion_unidad_'.($active?'habilitar':'inhabilitar'),$slug);
                echo json_encode(['success'=>true]);
            } else {
                http_response_code(500);
                echo json_encode(['success'=>false,'error'=>'Error actualizando estado']);
            }
            break;

        case 'set_evaluation_exercise_active':
            if (!in_array($role, ['Docente','Administrador'], true)) { http_response_code(403); echo json_encode(['success'=>false,'error'=>'Acceso denegado']); exit; }
            $unit_slug = trim((string)($payload['unit_slug'] ?? ''));
            $exercise_slug = trim((string)($payload['exercise_slug'] ?? ''));
            $active = (int)($payload['active'] ?? 0);
            if ($unit_slug === '' || $exercise_slug === '' || ($active !== 0 && $active !== 1)) {
                http_response_code(422);
                echo json_encode(['success'=>false,'error'=>'Parámetros inválidos']);
                exit;
            }
            $ok = $evalModel->setEvaluationExerciseActive($unit_slug, $exercise_slug, $active === 1);
            if ($ok) {
                Registrador::log($_SESSION['usuario'],$role,'evaluacion_ejercicio_'.($active?'habilitar':'inhabilitar'),$unit_slug.'/'.$exercise_slug);
                echo json_encode(['success'=>true]);
            } else {
                http_response_code(500);
                echo json_encode(['success'=>false,'error'=>'Error actualizando estado']);
            }
            break;

        // Ejercicios por tema
        case 'create_exercise_for_topic':
            if (!in_array($role, ['Docente','Administrador'], true)) { http_response_code(403); echo json_encode(['success'=>false,'error'=>'Acceso denegado']); exit; }
            $unit_slug = trim((string)($payload['unit_slug'] ?? ''));
            $topic_id = (int)($payload['topic_id'] ?? 0);
            $slug = trim((string)($payload['slug'] ?? ''));
            $title = trim((string)($payload['title'] ?? ''));
            if ($unit_slug === '' || $topic_id <= 0 || $title === '') { http_response_code(422); echo json_encode(['success'=>false,'error'=>'Parámetros inválidos']); exit; }
            require_once __DIR__ . '/../model/ModeloPractica.php';
            $practiceModel = new PracticeModel($db);
            $data = [
                'title' => $title,
                'instructions' => $payload['instructions'] ?? '',
                'example' => $payload['example'] ?? '',
                'expected_output' => $payload['expected_output'] ?? '',
                'solution' => $payload['solution'] ?? '',
                'orden' => (int)($payload['orden'] ?? 0)
            ];
            $ok = $practiceModel->createExerciseForTopic($unit_slug, $topic_id, $slug, $data);
            if ($ok) {
                Registrador::log($_SESSION['usuario'],$role,'ejercicio_crear','topic:'.$topic_id.' slug:'.$slug);
                echo json_encode(['success'=>true,'message'=>'Ejercicio creado correctamente']);
            } else {
                http_response_code(500);
                echo json_encode(['success'=>false,'error'=>'Error creando ejercicio']);
            }
            break;

        case 'create_evaluation_for_topic':
            if (!in_array($role, ['Docente','Administrador'], true)) { 
                http_response_code(403); 
                echo json_encode(['success' => false, 'error' => 'Acceso denegado']); 
                exit; 
            }
            
            // Depuración: Registrar los datos recibidos
            error_log("DEBUG: create_evaluation_for_topic - payload: " . print_r($payload, true));
            
            $unit_slug = trim((string)($payload['unit_slug'] ?? ''));
            $topic_id = (int)($payload['topic_id'] ?? 0);
            $slug = trim((string)($payload['slug'] ?? ''));
            $title = trim((string)($payload['title'] ?? ''));
            $instructions = trim((string)($payload['instructions'] ?? ''));
            $example = trim((string)($payload['example'] ?? ''));
            $rubric = trim((string)($payload['rubric'] ?? ''));
            $expected_code = trim((string)($payload['expected_code'] ?? ''));
            $orden = (int)($payload['orden'] ?? 0);
            
            // Depuración: Registrar valores procesados
            error_log("DEBUG: create_evaluation_for_topic - unit_slug: $unit_slug, topic_id: $topic_id, title: $title");
            
            // Validaciones detalladas con mensajes más específicos
            if ($unit_slug === '') { 
                http_response_code(422); 
                echo json_encode(['success' => false, 'error' => 'Slug de unidad requerido']); 
                exit; 
            }
            
            if ($topic_id <= 0) { 
                http_response_code(422); 
                echo json_encode(['success' => false, 'error' => 'ID de tema inválido']); 
                exit; 
            }
            
            if ($title === '') { 
                http_response_code(422); 
                echo json_encode(['success' => false, 'error' => 'Título de evaluación requerido']); 
                exit; 
            }
            
            if ($instructions === '') { 
                http_response_code(422); 
                echo json_encode(['success' => false, 'error' => 'Instrucciones de evaluación requeridas']); 
                exit; 
            }
            
            if ($example === '') { 
                http_response_code(422); 
                echo json_encode(['success' => false, 'error' => 'Ejemplo de evaluación requerido']); 
                exit; 
            }
            
            if ($expected_code === '') { 
                http_response_code(422); 
                echo json_encode(['success' => false, 'error' => 'Código esperado requerido']); 
                exit; 
            }
            
            // Validar que la rúbrica sea un JSON válido
            if (!empty($rubric)) {
                if (!is_string($rubric)) {
                    http_response_code(422);
                    echo json_encode(['success' => false, 'error' => 'La rúbrica debe ser una cadena JSON válida']);
                    exit;
                }
                
                // Intentar decodificar el JSON para validar que sea válido
                $decoded = json_decode($rubric, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    http_response_code(422);
                    echo json_encode(['success' => false, 'error' => 'La rúbrica no es un JSON válido: ' . json_last_error_msg()]);
                    exit;
                }
                
                // Validar que la rúbrica tenga el formato esperado
                if (!is_array($decoded)) {
                    http_response_code(422);
                    echo json_encode(['success' => false, 'error' => 'La rúbrica debe ser un array JSON']);
                    exit;
                }
            }
            
            require_once __DIR__ . '/../model/ModeloPractica.php';
            $practiceModel = new PracticeModel($db);
            
            $data = [
                'title' => $title,
                'instructions' => $instructions,
                'example' => $example,
                'rubric' => $rubric,
                'expected_code' => $expected_code,
                'orden' => $orden
            ];
            
            // Validar que el slug no esté vacío
            if (empty($slug)) {
                $slug = $title;
                // Generar slug automáticamente
                $slug = preg_replace('/[^a-z0-9\s-]/', '', strtolower(trim(preg_replace('/\s+/', '-', $slug))));
                if (empty($slug)) {
                    http_response_code(422);
                    echo json_encode(['success' => false, 'error' => 'No se pudo generar un slug válido desde el título']);
                    exit;
                }
            }
            
            // Depuración: Registrar datos antes de llamar al modelo
            error_log("DEBUG: create_evaluation_for_topic - llamando a createEvaluationForTopic con unit_slug: $unit_slug, topic_id: $topic_id, slug: $slug");
            
            $ok = $practiceModel->createEvaluationForTopic($unit_slug, $topic_id, $slug, $data);
            
            // Depuración: Registrar resultado del modelo
            error_log("DEBUG: create_evaluation_for_topic - resultado del modelo: " . ($ok ? 'true' : 'false'));
            
            if ($ok) {
                Registrador::log($_SESSION['usuario'],$role,'evaluacion_crear','topic:'.$topic_id.' slug:'.$slug);
                echo json_encode(['success'=>true,'message'=>'Evaluación creada correctamente']);
            } else {
                http_response_code(500);
                echo json_encode(['success'=>false,'error'=>'Error creando evaluación. Por favor, verifica los datos ingresados y vuelve a intentarlo.']);
            }
            break;

        case 'update_exercise_topic':
            if (!in_array($role, ['Docente','Administrador'], true)) { http_response_code(403); echo json_encode(['success'=>false,'error'=>'Acceso denegado']); exit; }
            $unit_slug = trim((string)($payload['unit_slug'] ?? ''));
            $exercise_slug = trim((string)($payload['exercise_slug'] ?? ''));
            $topic_id = (int)($payload['topic_id'] ?? 0);
            if ($unit_slug === '' || $exercise_slug === '' || $topic_id <= 0) { http_response_code(422); echo json_encode(['success'=>false,'error'=>'Parámetros inválidos']); exit; }
            require_once __DIR__ . '/../model/ModeloPractica.php';
            $practiceModel = new PracticeModel($db);
            $ok = $practiceModel->updateExerciseTopic($unit_slug, $exercise_slug, $topic_id);
            if ($ok) {
                Registrador::log($_SESSION['usuario'],$role,'ejercicio_asociar_tema','exercise:'.$exercise_slug.' topic:'.$topic_id);
                echo json_encode(['success'=>true,'message'=>'Ejercicio asociado a tema correctamente']);
            } else {
                http_response_code(500);
                echo json_encode(['success'=>false,'error'=>'Error asociando ejercicio a tema']);
            }
            break;

        case 'get_exercise':
            if (!in_array($role, ['Docente','Administrador'], true)) { http_response_code(403); echo json_encode(['success'=>false,'error'=>'Acceso denegado']); exit; }
            $id = (int)($payload['id'] ?? 0);
            if ($id <= 0) { http_response_code(422); echo json_encode(['success'=>false,'error'=>'ID inválido']); exit; }
            require_once __DIR__ . '/../model/ModeloPractica.php';
            $practiceModel = new PracticeModel($db);
            $ex = $practiceModel->getExerciseById($id);
            if (!$ex) {
                // Intentar buscar como práctica si no es evaluación
                $conn = $db->getConnection();
                $stmt = $conn->prepare('SELECT id, slug, titulo, orden, instrucciones, ejemplo, salida_esperada, solucion, tema_id FROM ejercicios WHERE id = ? LIMIT 1');
                $stmt->bind_param('i', $id);
                $stmt->execute();
                $res = $stmt->get_result();
                $ex = $res->fetch_assoc();
                $stmt->close();
            }
            if (!$ex) { http_response_code(404); echo json_encode(['success'=>false,'error'=>'No encontrado']); exit; }
            echo json_encode(['success'=>true,'exercise'=>$ex]);
            break;

        case 'get_topic_exercises':
            // Esta acción no requiere autenticación estricta para permitir acceso a estudiantes
            // Manejar tanto GET (sin cuerpo JSON) como POST (con cuerpo JSON)
            if ($_SERVER['REQUEST_METHOD'] === 'GET') {
                // Para solicitudes GET, obtener el topic_id y type de los parámetros de la URL
                $topic_id = isset($_GET['topic_id']) ? (int)$_GET['topic_id'] : 0;
                $type = isset($_GET['type']) ? trim($_GET['type']) : '';
            } else {
                // Para solicitudes POST, obtener el topic_id y type del cuerpo JSON
                $topic_id = (int)($payload['topic_id'] ?? 0);
                $type = trim($payload['type'] ?? '');
            }
            
            if ($topic_id <= 0) { 
                http_response_code(422); 
                echo json_encode(['success'=>false,'error'=>'ID de tema inválido']); 
                exit; 
            }
            
            require_once __DIR__ . '/../model/ModeloPractica.php';
            $practiceModel = new PracticeModel($db);
            error_log("DEBUG: get_topic_exercises - topic_id: {$topic_id}, type: {$type}");
            
            // Si se especifica un tipo, filtrar por ese tipo
            if ($type === 'practica' || $type === 'evaluacion') {
                $exercises = $practiceModel->getExercisesByTopic($topic_id, $type);
                error_log("DEBUG: get_topic_exercises - filtrando por tipo: {$type}");
            } else {
                // Si no se especifica tipo, devolver todos los ejercicios
                $exercises = $practiceModel->getExercisesByTopic($topic_id);
                error_log("DEBUG: get_topic_exercises - sin filtro de tipo");
            }
            
            error_log("DEBUG: get_topic_exercises - exercises count: " . count($exercises));
            error_log("DEBUG: get_topic_exercises - exercises data: " . print_r($exercises, true));
            echo json_encode(['success'=>true,'exercises'=>$exercises]);
            break;

        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Acción desconocida']);
            break;
    }
} catch (Throwable $e) {
    http_response_code(500);
    // Loguear el error interno para propósitos de debugging
    error_log("Error en manage.php: " . $e->getMessage()); 
    echo json_encode(['success' => false, 'error' => 'Error interno. Consulte los logs del servidor.']);
}
