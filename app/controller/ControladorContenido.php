<?php
require_once 'app/model/Registrador.php';
require_once 'app/config/BaseDatos.php';

class ContentController {
    private $db;
    
    public function __construct() {
        $this->db = new Database();
        $this->db->getConnection(); // Esto me tomó una hora corregirlo caray
    }

    /**
 * Endpoint para obtener todas las unidades en formato JSON
 * Se usa para la modal de reordenación
 */
    public function getAllUnitsJSON() {
        // 1. Asegurar que solo se devuelva JSON
        header('Content-Type: application/json');

        try {
            require_once 'app/model/ModeloUnidad.php';
            $unitModel = new ModeloUnidad($this->db);
            
            // Obtenemos las unidades (puedes filtrar por docente si es necesario)
            $userId = $_SESSION['user_id'] ?? null;
            $units = $unitModel->obtenerUnidadesPorDocente($userId);

            echo json_encode([
                'success' => true,
                'units' => $units
            ]);
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => 'Error al obtener unidades: ' . $e->getMessage()
            ]);
        }
        exit(); // Importante para que no cargue el resto de la página
    }

    public function showContentPage() {
        // Verificar sesión
        if (!isset($_SESSION['usuario'])) {
            header('Location: index.php');
            exit();
        }

        $nombre_usuario = $_SESSION['usuario'] ?? 'Usuario';
        $rol = $_SESSION['rol'] ?? 'Usuario';

        // Generar token CSRF si no existe
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        // Registrar acceso
        Registrador::log($_SESSION['usuario'] ?? $nombre_usuario, $_SESSION['rol'] ?? $rol, 'acceso_contenido', 'Acceso a página de contenido teórico');

        // Obtener unidades desde la base de datos
        require_once 'app/model/ModeloUnidad.php';

        // CORRECCIÓN: Pasar el objeto Database, no la conexión
        $unitModel = new ModeloUnidad($this->db); // $this->db es el objeto Database
        $userId = $_SESSION['user_id'] ?? null;
        $seccion = $_SESSION['seccion'] ?? null;
        $units = $unitModel->listUnits($userId, $rol, $seccion);
        $pageTitle = "📚 Lee la teoría antes de practicar! 🤓";

        include 'app/view/Contenido.php';
    }

    public function handleContentApi() {
        header('Content-Type: application/json; charset=utf-8');

        if (!isset($_SESSION['usuario'])) {
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'No autenticado']);
            exit;
        }

        $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');

        try {
            if ($method === 'GET') {
                $this->handleContentGet();
            } elseif ($method === 'POST') {
                $this->handleContentPost();
            } elseif ($method === 'PUT') {
                $this->handleContentPut();
            } elseif ($method === 'DELETE') {
                $this->handleContentDelete();
            } else {
                http_response_code(405);
                echo json_encode(['success' => false, 'error' => 'Método no permitido']);
            }
        } catch (Exception $e) {
            error_log('ContentController::handleContentApi DB exception: ' . $e->getMessage());
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'Error de conexión. Por favor, intenta de nuevo.']);
        }
    }

    public function handleUnitApi() {
        header('Content-Type: application/json; charset=utf-8');

        if (!isset($_SESSION['usuario'])) {
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'No autenticado']);
            exit;
        }

        $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');

        try {
            if ($method === 'PUT') {
                $this->handleUnitPut();
            } else {
                http_response_code(405);
                echo json_encode(['success' => false, 'error' => 'Método no permitido']);
            }
        } catch (Exception $e) {
            error_log('ContentController::handleUnitApi DB exception: ' . $e->getMessage());
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'Error de conexión. Por favor, intenta de nuevo.']);
        }
    }

    public function handleUnitsList() {
        header('Content-Type: application/json; charset=utf-8');

        if (!isset($_SESSION['usuario'])) {
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'No autenticado']);
            exit;
        }

        // Verificar CSRF token
        if (!isset($_GET['csrf_token']) || !hash_equals($_SESSION['csrf_token'] ?? '', $_GET['csrf_token'])) {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'CSRF inválido']);
            exit;
        }

        try {
            require_once 'app/model/ModeloUnidad.php';
            $unitModel = new ModeloUnidad($this->db);
            
            // Obtener todas las unidades sin validaciones de rol
            $units = $unitModel->listUnits();
            
            echo json_encode(['success' => true, 'units' => $units]);
        } catch (Exception $e) {
            error_log('ContentController::handleUnitsList DB exception: ' . $e->getMessage());
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'Error de conexión. Por favor, intenta de nuevo.']);
        }
    }

    private function handleContentGet() {
        $unitId = $_GET['unit_id'] ?? null;
        $topicId = $_GET['topic_id'] ?? null;
        $contentId = $_GET['content_id'] ?? null;

        require_once 'app/model/ModeloContenido.php';
        $contentModel = new ContentModel($this->db);

        // Caso 1: Solicitar un ítem específico
        if ($contentId) {
            $content = $contentModel->getContent((int)$contentId);
            if ($content) {
                echo json_encode(['success' => true, 'content' => $content]);
            } else {
                http_response_code(404);
                echo json_encode(['success' => false, 'error' => 'Contenido no encontrado']);
            }
            return;
        }

        if (!$unitId && !$topicId) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'unit_id, topic_id o content_id requerido']);
            return;
        }
        
        $content = [];
        $rol = $_SESSION['rol'] ?? 'Usuario';
        $isTeacher = in_array($rol, ['Docente', 'Administrador']);

        if ($topicId) {
            // Obtener contenido asociado a un tema específico
            $content = $contentModel->listContentByTopic((int)$topicId, !$isTeacher);
        } else {
            // Obtener contenido general de una unidad (sin tema asociado)
            $content = $contentModel->listContentByUnit((int)$unitId, !$isTeacher);
        }

        echo json_encode(['success' => true, 'content' => $content]);
    }

    private function handleContentPost() {
        // Verificar permisos de docente/administrador
        $rol = $_SESSION['rol'] ?? '';
        if (!in_array($rol, ['Docente', 'Administrador'])) {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Permisos insuficientes']);
            return;
        }

        $input = json_decode(file_get_contents('php://input'), true);

        if (!isset($input['csrf_token']) || !hash_equals($_SESSION['csrf_token'] ?? '', $input['csrf_token'])) {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'CSRF inválido']);
            return;
        }

        $unidadId = $input['unidad_id'] ?? null;
        $tipo = $input['tipo'] ?? null;
        $titulo = $input['titulo'] ?? null;
        $contenido = $input['contenido'] ?? null;
        $url = $input['url'] ?? null;
        $orden = $input['orden'] ?? 0;
        $temaId = $input['tema_id'] ?? null; // Añadir el parámetro tema_id

        if (!$unidadId || !$tipo || !$titulo) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Campos requeridos faltantes']);
            return;
        }

        require_once 'app/model/ModeloContenido.php';
        $contentModel = new ContentModel($this->db);

        $result = $contentModel->createContent((int)$unidadId, $tipo, $titulo, $contenido, $url, (int)$orden, $temaId);

        if ($result) {
            Registrador::log($_SESSION['usuario'], $_SESSION['rol'], 'contenido_creado', "Tipo: $tipo, Título: $titulo, Tema: " . ($temaId ? $temaId : 'general'));
            echo json_encode(['success' => true, 'id' => $result]);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'Error creando contenido']);
        }
    }

    private function handleContentPut() {
        // Verificar permisos de docente/administrador
        $rol = $_SESSION['rol'] ?? '';
        if (!in_array($rol, ['Docente', 'Administrador'])) {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Permisos insuficientes']);
            return;
        }

        $input = json_decode(file_get_contents('php://input'), true);

        if (!isset($input['csrf_token']) || !hash_equals($_SESSION['csrf_token'] ?? '', $input['csrf_token'])) {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'CSRF inválido']);
            return;
        }

        $id = $input['id'] ?? null;
        $fields = $input['fields'] ?? [];

        if (!$id || empty($fields)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'ID y campos requeridos']);
            return;
        }

        require_once 'app/model/ModeloContenido.php';
        $contentModel = new ContentModel($this->db);

        if ($contentModel->updateContent((int)$id, $fields)) {
            Registrador::log($_SESSION['usuario'], $_SESSION['rol'], 'contenido_actualizado', "ID: $id");
            echo json_encode(['success' => true]);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'Error actualizando contenido']);
        }
    }

    private function handleContentDelete() {
        // Verificar permisos de docente/administrador
        $rol = $_SESSION['rol'] ?? '';
        if (!in_array($rol, ['Docente', 'Administrador'])) {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Permisos insuficientes']);
            return;
        }

        $input = json_decode(file_get_contents('php://input'), true);

        if (!isset($input['csrf_token']) || !hash_equals($_SESSION['csrf_token'] ?? '', $input['csrf_token'])) {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'CSRF inválido']);
            return;
        }

        $id = $input['id'] ?? null;

        if (!$id) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'ID requerido']);
            return;
        }

        require_once 'app/model/ModeloContenido.php';
        $contentModel = new ContentModel($this->db);

        if ($contentModel->deleteContent((int)$id)) {
            Registrador::log($_SESSION['usuario'], $_SESSION['rol'], 'contenido_eliminado', "ID: $id");
            echo json_encode(['success' => true]);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'Error eliminando contenido']);
        }
    }

    private function handleUnitPut() {
        // Verificar permisos de docente/administrador
        $rol = $_SESSION['rol'] ?? '';
        if (!in_array($rol, ['Docente', 'Administrador'])) {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Permisos insuficientes']);
            return;
        }

        $input = json_decode(file_get_contents('php://input'), true);

        if (!isset($input['csrf_token']) || !hash_equals($_SESSION['csrf_token'] ?? '', $input['csrf_token'])) {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'CSRF inválido']);
            return;
        }

        $id = $input['id'] ?? null;
        $orden = $input['orden'] ?? null;

        // Validar parámetros requeridos
        if (!$id || $orden === null) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'ID y orden requeridos']);
            return;
        }

        // Validar que el orden sea un número válido
        if (!is_numeric($orden) || $orden < 1) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Orden debe ser un número mayor o igual a 1']);
            return;
        }

        require_once 'app/model/ModeloUnidad.php';
        $unitModel = new ModeloUnidad($this->db);

        // Obtener el docente_id del usuario actual
        $userId = $this->getUserId();
        if (!$userId) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'No se pudo obtener el ID del usuario']);
            return;
        }

        // Realizar el reordenamiento con transacción - CAPTURA EL ERROR REAL
        try {
            $exito = $unitModel->changeUnitOrderToPosition((int)$id, (int)$orden, $userId);
            if ($exito) {
                Registrador::log($_SESSION['usuario'], $_SESSION['rol'], 'unidad_reordenada', "ID: $id, Nuevo orden: $orden");
                echo json_encode(['success' => true]);
            } else {
                // Si el modelo devolvió false, es que el rollback ya ocurrió
                echo json_encode(['success' => false, 'error' => 'El reordenamiento falló en la base de datos.']);
            }
        } catch (Throwable $t) {
            // Esto atrapará errores fatales y excepciones
            echo json_encode([
                'success' => false, 
                'error' => 'Error de PHP: ' . $t->getMessage(),
                'file' => $t->getFile(),
                'line' => $t->getLine()
            ]);
        }
    }

// Agregar este nuevo método para mostrar prácticas
    public function showPracticesPage() {
        // Verificar sesión
        if (!isset($_SESSION['usuario'])) {
            header('Location: index.php');
            exit();
        }

        $nombre_usuario = $_SESSION['usuario'] ?? 'Usuario';
        $rol = $_SESSION['rol'] ?? 'Usuario';

        // Generar token CSRF si no existe
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        // Registrar acceso
        Registrador::log($_SESSION['usuario'] ?? $nombre_usuario, $_SESSION['rol'] ?? $rol, 'acceso_practicas', 'Acceso a página de prácticas');

        // Obtener unidades de prácticas desde la base de datos
        require_once 'app/model/ModeloPractica.php';
        $practiceModel = new PracticeModel($this->db); // $this->db es el objeto Database
        
        $userId = $this->getUserId();
        $seccion = $_SESSION['seccion'] ?? null;
        $practiceUnits = $practiceModel->listPracticeUnits($userId, $rol, $seccion);

        // Para cada unidad, obtener sus ejercicios y progreso
        $practiceData = [];
        foreach ($practiceUnits as $unit) {
            // Obtener temas con ejercicios para esta unidad
            $topicsWithExercises = $practiceModel->getTopicsWithExercisesForUnit($unit['slug']);
            $completedIds = $practiceModel->getCompletedExercisesForUserInUnit($userId, $unit['slug']);

            // Contar ejercicios totales y completados
            $totalExercises = 0;
            $completedCount = 0;
            $exercisesWithStatus = [];

            // Procesar ejercicios por tema
            foreach ($topicsWithExercises as $topicData) {
                foreach ($topicData['exercises'] as $exercise) {
                    $isCompleted = in_array($exercise['id'], $completedIds);
                    if ($isCompleted) $completedCount++;
                    $totalExercises++;

                    // Añadir información del tema al ejercicio
                    $exerciseWithTopic = array_merge($exercise, [
                        'completed' => $isCompleted,
                        'topic_id' => $topicData['topic']['id'],
                        'topic_name' => $topicData['topic']['nombre'],
                        'topic_description' => $topicData['topic']['descripcion']
                    ]);
                    $exercisesWithStatus[] = $exerciseWithTopic;
                }
            }

            $progressPercent = $totalExercises > 0 ? round(($completedCount / $totalExercises) * 100) : 0;
            $practiceData[] = [
                'unit' => $unit,
                'exercises' => $exercisesWithStatus,
                'topics' => $topicsWithExercises,
                'progress' => $progressPercent
            ];
        }
        $pageTitle = "🛠️ Practica los ejercicios interactivos! ✏️";

        include 'app/view/Practicas.php';
    }

    public function showExercisePage() {
        // Verificar sesión
        if (!isset($_SESSION['usuario'])) {
            header('Location: index.php');
            exit();
        }

        $unit = $_GET['unit'] ?? 'variables';
        $exercise = $_GET['exercise'] ?? '1';

        $nombre_usuario = $_SESSION['usuario'] ?? 'Usuario';
        $rol = $_SESSION['rol'] ?? 'Usuario';

        // Generar token CSRF si no existe
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        // Obtener datos del ejercicio
        $exerciseData = $this->getExerciseData($unit, $exercise);

        // Registrar acceso al ejercicio
        Registrador::log($_SESSION['usuario'] ?? $nombre_usuario, $_SESSION['rol'] ?? $rol, 'acceso_ejercicio', "Acceso a ejercicio: $unit - $exercise");
        $pageTitle = "✏️ Resuelve el ejercicio paso a paso! 🧠";

        // Antes de incluir la vista:
        define('APP_LOADED', true);
        include 'app/view/Ejercicio.php';
    }

    public function showEvaluationPage() {
        if (!isset($_SESSION['usuario'])) {
            header('Location: index.php');
            exit();
        }

        $nombre_usuario = $_SESSION['usuario'] ?? 'Usuario';
        $rol = $_SESSION['rol'] ?? 'Usuario';

        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        // Verificar si se está accediendo a una evaluación específica por tema
        $topicId = $_GET['topic_id'] ?? null;
        $exerciseId = $_GET['exercise_id'] ?? null;

        if ($topicId && $exerciseId) {
            // Acceso directo a una evaluación específica de un tema
            $this->showSpecificEvaluation($topicId, $exerciseId);
        } else {
            // Vista general de evaluaciones
            $pageTitle = " Demuestra lo que has aprendido en la evaluación! 🎓";
            include 'app/view/Evaluaciones.php';
        }
    }

    private function showSpecificEvaluation($topicId, $exerciseId) {
        // Validar IDs - Asegurar que sean enteros y mayores a cero
        // Si viene 'undefined' como string, is_numeric devuelve false
        if (!is_numeric($topicId) || !is_numeric($exerciseId)) {
            header('Location: index.php?page=content');
            exit();
        }

        $topicId = (int)$topicId;
        $exerciseId = (int)$exerciseId;

        if ($topicId <= 0 || $exerciseId <= 0) {
            header('Location: index.php?page=content');
            exit();
        }

        // Obtener datos de la evaluación específica
        require_once 'app/model/ModeloPractica.php';
        $practiceModel = new PracticeModel($this->db);
        
        // Obtener el ejercicio por ID
        $exercise = $practiceModel->getExerciseById($exerciseId);
        
        if (!$exercise) {
            header('Location: index.php?page=content');
            exit();
        }

        // Obtener el tema para mostrar información
        require_once 'app/model/ModeloTema.php';
        $temaModel = new ModeloTema($this->db);
        $topic = $temaModel->obtenerTema($topicId);

        // Obtener datos del usuario
        $nombre_usuario = $_SESSION['usuario'] ?? 'Usuario';
        $rol = $_SESSION['rol'] ?? 'Usuario';

        $pageTitle = "📝 Evaluación: " . $exercise['titulo'];
        $evaluationData = [
            'exercise' => $exercise,
            'topic' => $topic,
            'topicId' => $topicId,
            'exerciseId' => $exerciseId
        ];

        // Incluir una vista específica para evaluaciones por tema
        include 'app/view/EvaluacionEspecifica.php';
    }

    public function handleEvaluationApi() {
        header('Content-Type: application/json; charset=utf-8');

        if (!isset($_SESSION['usuario'])) {
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'No autenticado']);
            exit;
        }

        $method = strtoupper($_SERVER['REQUEST_METHOD']);

        try {
            if ($method === 'GET') {
                $this->handleEvaluationGet();
            } elseif ($method === 'POST') {
                $this->handleEvaluationPost();
            } else {
                http_response_code(405);
                echo json_encode(['success' => false, 'error' => 'Método no permitido']);
            }
        } catch (Exception $e) {
            error_log('ContentController::handleEvaluationApi DB exception: ' . $e->getMessage());
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'Error de conexión. Por favor, intenta de nuevo.', 'feedback' => ['Error de conexión. Por favor, intenta de nuevo.']]);
        }
    }

    // API para gestionar temas
    public function handleTopicsApi() {
        header('Content-Type: application/json; charset=utf-8');

        if (!isset($_SESSION['usuario'])) {
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'No autenticado']);
            exit;
        }

        $method = strtoupper($_SERVER['REQUEST_METHOD']);

        try {
            if ($method === 'GET') {
                $this->handleTopicsGet();
            } else {
                http_response_code(405);
                echo json_encode(['success' => false, 'error' => 'Método no permitido']);
            }
        } catch (Exception $e) {
            error_log('ContentController::handleTopicsApi DB exception: ' . $e->getMessage());
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'Error de conexión. Por favor, intenta de nuevo.']);
        }
    }

    // API para ejercicios de un tema específico
    public function handleTopicExercisesApi() {
        header('Content-Type: application/json; charset=utf-8');

        if (!isset($_SESSION['usuario'])) {
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'No autenticado']);
            exit;
        }

        $method = strtoupper($_SERVER['REQUEST_METHOD']);

        try {
            if ($method === 'GET') {
                $this->handleTopicExercisesGet();
            } else {
                http_response_code(405);
                echo json_encode(['success' => false, 'error' => 'Método no permitido']);
            }
        } catch (Exception $e) {
            error_log('ContentController::handleTopicExercisesApi DB exception: ' . $e->getMessage());
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'Error de conexión. Por favor, intenta de nuevo.']);
        }
    }

    private function handleTopicsGet() {
        $unitId = $_GET['unit_id'] ?? null;

        if (!$unitId) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'unit_id requerido']);
            return;
        }

        require_once 'app/model/ModeloTema.php';
        $temaModel = new ModeloTema($this->db);
        
        // Verificar el rol del usuario para decidir qué temas mostrar
        $rol = $_SESSION['rol'] ?? 'Usuario';
        
        if ($rol === 'Docente' || $rol === 'Administrador') {
            // Docentes y administradores ven todos los temas
            $topics = $temaModel->listarTemasPorUnidad((int)$unitId);
        } else {
            // Usuarios normales solo ven temas activos
            $topics = $temaModel->listarTemasActivosPorUnidad((int)$unitId);
        }

        echo json_encode(['success' => true, 'topics' => $topics]);
    }

    private function handleTopicExercisesGet() {
        $topicId = $_GET['topic_id'] ?? null;
        $exerciseType = $_GET['type'] ?? 'all'; // 'practica', 'evaluacion', or 'all'

        if (!$topicId) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'topic_id requerido']);
            return;
        }

        // Obtener ejercicios asociados a un tema
        $exercises = [];
        $conn = $this->db->getConnection();

        // Verificar si la tabla ejercicios existe
        $tableExists = false;
        $result = $conn->query("SHOW TABLES LIKE 'ejercicios'");
        if ($result) {
            $tableExists = $result->num_rows > 0;
            $result->free();
        }

        if (!$tableExists) {
            // Tabla no existe, devolver array vacío
            echo json_encode(['success' => true, 'exercises' => []]);
            return;
        }

        // Verificar si la columna tema_id existe
        $columnExists = false;
        $result = $conn->query("SHOW COLUMNS FROM ejercicios LIKE 'tema_id'");
        if ($result) {
            $columnExists = $result->num_rows > 0;
            $result->free();
        }

        if (!$columnExists) {
            // Columna tema_id no existe, devolver array vacío
            echo json_encode(['success' => true, 'exercises' => []]);
            return;
        }

        // Verificar si la columna tipo existe
        $typeColumnExists = false;
        $result = $conn->query("SHOW COLUMNS FROM ejercicios LIKE 'tipo'");
        if ($result) {
            $typeColumnExists = $result->num_rows > 0;
            $result->free();
        }

        // Verificar si la columna activo existe
        $activoColumnExists = false;
        $result = $conn->query("SHOW COLUMNS FROM ejercicios LIKE 'activo'");
        if ($result) {
            $activoColumnExists = $result->num_rows > 0;
            $result->free();
        }

        // Construir consulta según la estructura de la tabla
        $sql = 'SELECT id, titulo, slug, tipo, tema_id FROM ejercicios WHERE tema_id = ?';
        
        // Añadir filtro por tipo si se especifica
        if ($exerciseType !== 'all' && $typeColumnExists) {
            $sql .= ' AND tipo = ?';
        }
        
        // Añadir condición de activo si existe la columna
        if ($activoColumnExists) {
            $sql .= ' AND activo = 1';
        }
        
        $sql .= ' ORDER BY orden ASC';

        try {
            if ($stmt = $conn->prepare($sql)) {
                if ($exerciseType !== 'all' && $typeColumnExists) {
                    $stmt->bind_param('is', $topicId, $exerciseType);
                } else {
                    $stmt->bind_param('i', $topicId);
                }
                
                if ($stmt->execute()) {
                    $result = $stmt->get_result();
                    while ($row = $result->fetch_assoc()) {
                        $exercises[] = $row;
                    }
                    $stmt->close();
                } else {
                    error_log("Error executing topic exercises query: " . $stmt->error);
                }
            } else {
                error_log("Error preparing topic exercises query: " . $conn->error);
            }
        } catch (Exception $e) {
            error_log("Exception in handleTopicExercisesGet: " . $e->getMessage());
        }

        echo json_encode(['success' => true, 'exercises' => $exercises]);
    }

    private function handleEvaluationGet() {
        $evalKey = $_GET['evaluation'] ?? '';
        
        if ($evalKey !== '') {
            $this->getEvaluationDetails($evalKey);
        } else {
            $this->getEvaluationList();
        }
    }

    private function handleEvaluationPost() {
        $input = json_decode(file_get_contents('php://input'), true);

        if (!isset($input['csrf_token']) || !hash_equals($_SESSION['csrf_token'] ?? '', $input['csrf_token'])) {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'CSRF inválido']);
            return;
        }

        $action = $input['action'] ?? '';
        $evaluation = $input['evaluation'] ?? '';

        if ($action === 'submit') {
            $this->submitEvaluation($evaluation, $input['code'] ?? '');
        } elseif ($action === 'reset') {
            $this->resetEvaluation($evaluation);
        } elseif ($action === 'update') {
            $this->updateEvaluation($evaluation, $input['fields'] ?? []);
        } else {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Acción no válida']);
        }
    }

    private function getEvaluationList() {
        $evaluations = $this->getEvaluationsDefinition();
        $userId = $this->getUserId();
        $list = [];

        foreach ($evaluations as $key => $def) {
            $state = $this->getAttemptState($userId, $key);
            $list[] = [
                'key' => $key,
                'title' => $def['title'],
                'description' => $def['description'],
                'unit' => $def['unit'],
                'state' => $state,
            ];
        }

        echo json_encode(['success' => true, 'evaluations' => $list]);
    }

    private function getEvaluationDetails($key) {
        $evaluations = $this->getEvaluationsDefinition();
        
        if (!isset($evaluations[$key])) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Evaluación no encontrada']);
            return;
        }

        $def = $evaluations[$key];
        $userId = $this->getUserId();
        $state = $this->getAttemptState($userId, $key);

        echo json_encode(['success' => true, 'evaluation' => [
            'key' => $key,
            'title' => $def['title'],
            'description' => $def['description'],
            'unit' => $def['unit'],
            'instructions' => $def['instructions'],
            'example' => $def['example']
        ], 'state' => $state]);
    }

    private function submitEvaluation($key, $code) {
        $evaluations = $this->getEvaluationsDefinition();
        
        if (!isset($evaluations[$key])) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Evaluación no encontrada']);
            return;
        }

        $userId = $this->getUserId();
        $state = $this->getAttemptState($userId, $key);

        if ($state['attempt_used'] === 1) {
            http_response_code(409);
            echo json_encode(['success' => false, 'error' => 'Ya has usado tu intento. Usa Reintentar para reiniciar.']);
            return;
        }

        if (empty($code)) {
            http_response_code(422);
            echo json_encode(['success' => false, 'error' => 'Falta el código de la evaluación.']);
            return;
        }

        $def = $evaluations[$key];
        $result = $this->evaluateCode($key, $code, $def);

        // Guardar resultado
        if ($this->saveEvaluationResult($userId, $key, $result['score'])) {
            Registrador::log($_SESSION['usuario'], $_SESSION['rol'] ?? 'Usuario', 'evaluacion_submit', $key . ':' . $result['score']);
            echo json_encode(['success' => true, 'score' => $result['score'], 'max' => $result['max'], 'details' => $result['details']]);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'Error guardando resultado']);
        }
    }

    private function resetEvaluation($key) {
        $userId = $this->getUserId();

        if ($this->resetEvaluationAttempt($userId, $key)) {
            Registrador::log($_SESSION['usuario'], $_SESSION['rol'] ?? 'Usuario', 'evaluacion_reset', $key);
            echo json_encode(['success' => true]);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'Error reiniciando evaluación']);
        }
    }

    private function updateEvaluation($key, $fields) {
        // Verificar permisos de docente/administrador
        $rol = $_SESSION['rol'] ?? '';
        if (!in_array($rol, ['Docente', 'Administrador'])) {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Permisos insuficientes']);
            return;
        }

        require_once 'app/model/ModeloEvaluacion.php';
        $evalModel = new EvaluationModel($this->db);

        if ($evalModel->updateEvaluation($key, $fields)) {
            Registrador::log($_SESSION['usuario'], $_SESSION['rol'], 'evaluacion_actualizada', $key);
            echo json_encode(['success' => true]);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'Error actualizando evaluación']);
        }
    }

    // Métodos auxiliares para evaluaciones
    private function getEvaluationsDefinition() {
        require_once 'app/model/ModeloEvaluacion.php';
        $evalModel = new EvaluationModel($this->db);
        $evaluations = $evalModel->listEvaluations();

        if (!empty($evaluations)) {
            // Convertir al formato esperado
            $result = [];
            foreach ($evaluations as $key => $eval) {
                $result[$key] = [
                    'title' => $eval['title'],
                    'description' => $eval['description'],
                    'unit' => $eval['unit'],
                    'instructions' => $eval['instructions'],
                    'example' => $eval['example'],
                    'rubric' => $eval['rubric']
                ];
            }
            return $result;
        }

        // Usar evaluaciones predefinidas si la base de datos está vacía
        return [
            'variables' => [
                'title' => 'Evaluación: Variables y Tipos',
                'description' => 'Declara variables básicas en C',
                'unit' => 'variables',
                'instructions' => 'Escribe código C que: 1) Declare una variable int llamada edad con valor 25; 2) Declare una variable float llamada precio con valor 15.99; 3) Declare una variable char llamada inicial con valor "A".',
                'example' => "int edad = 25;\nfloat precio = 15.99;\nchar inicial = 'A';",
                'rubric' => [
                    [ 'label' => 'Declarar int edad = 25;', 'regex' => '/int\s+edad\s*=\s*25\s*;/', 'points' => 40, 'feedback' => 'Debe existir una variable int llamada edad con valor 25.' ],
                    [ 'label' => 'Declarar float precio = 15.99;', 'regex' => '/float\s+precio\s*=\s*15\.99\s*;/', 'points' => 30, 'feedback' => 'Debe existir una variable float precio con valor 15.99.' ],
                    [ 'label' => 'Declarar char inicial = \'A\';', 'regex' => '/char\s+inicial\s*=\s*\'a\'\s*;/', 'points' => 30, 'feedback' => 'Debe existir una variable char inicial con valor "A".' ]
                ]
            ],
            'operadores' => [
                'title' => 'Evaluación: Operadores Aritméticos',
                'description' => 'Opera con variables enteras en C',
                'unit' => 'operadores',
                'instructions' => 'Escribe código C que: 1) Declare dos variables int a=10 y b=5; 2) Calcule su suma en int suma=a+b; 3) Calcule su producto en int producto=a*b;',
                'example' => "int x = 8; int y = 3; int r1 = x + y; int r2 = x * y;",
                'rubric' => [
                    [ 'label' => 'Declarar int a = 10;', 'regex' => '/int\s+a\s*=\s*10\s*;/', 'points' => 20, 'feedback' => 'Debe existir una variable a con valor 10.' ],
                    [ 'label' => 'Declarar int b = 5;', 'regex' => '/int\s+b\s*=\s*5\s*;/', 'points' => 20, 'feedback' => 'Debe existir una variable b con valor 5.' ],
                    [ 'label' => 'Calcular suma = a + b;', 'regex' => '/int\s+suma\s*=\s*a\s*\+\s*b\s*;/', 'points' => 30, 'feedback' => 'Debe existir la suma en una variable suma.' ],
                    [ 'label' => 'Calcular producto = a * b;', 'regex' => '/int\s+producto\s*=\s*a\s*\*\s*b\s*;/', 'points' => 30, 'feedback' => 'Debe existir el producto en una variable producto.' ]
                ]
            ],
            'condicionales' => [
                'title' => 'Evaluación: Estructuras Condicionales',
                'description' => 'Usa if-else con una condición',
                'unit' => 'condicionales',
                'instructions' => 'Escribe código C que: 1) Declare int edad=18; 2) Use if (edad >= 18) { } else { } con bloques válidos.',
                'example' => "int edad = 18; if (edad >= 18) { /* ... */ } else { /* ... */ }",
                'rubric' => [
                    [ 'label' => 'Declarar int edad = 18;', 'regex' => '/int\s+edad\s*=\s*18\s*;/', 'points' => 40, 'feedback' => 'Debe existir una variable edad con valor 18.' ],
                    [ 'label' => 'Condición if (edad >= 18)', 'regex' => '/if\s*\(\s*edad\s*>=\s*18\s*\)/', 'points' => 30, 'feedback' => 'Debe existir una condición if con edad >= 18.' ],
                    [ 'label' => 'Bloque else', 'regex' => '/else\s*\{/', 'points' => 30, 'feedback' => 'Debe existir un bloque else.' ]
                ]
            ],
            'bucles' => [
                'title' => 'Evaluación: Bucles',
                'description' => 'Implementa un for con acumulación',
                'unit' => 'bucles',
                'instructions' => 'Escribe código C que: 1) Declare int suma = 0; 2) Use un for que recorra i=1..10; 3) Acumule suma con i;',
                'example' => "int suma = 0; for (int i = 1; i <= 10; i++) { suma = suma + i; }",
                'rubric' => [
                    [ 'label' => 'Declarar int suma = 0;', 'regex' => '/int\s+suma\s*=\s*0\s*;/', 'points' => 30, 'feedback' => 'Debe existir una variable suma inicializada en 0.' ],
                    [ 'label' => 'Bucle for i = 1 .. 10', 'regex' => '/for\s*\(\s*int\s+i\s*=\s*1\s*;\s*i\s*<=\s*10\s*;\s*i\+\+\s*\)/', 'points' => 40, 'feedback' => 'Debe existir un bucle for con i=1..10.' ],
                    [ 'label' => 'Acumulación suma = suma + i;', 'regex' => '/suma\s*=\s*suma\s*\+\s*i\s*;/', 'points' => 30, 'feedback' => 'Debe acumular suma con i dentro del bucle.' ]
                ]
            ]
        ];
    }

    private function getUserId() {
    // 1. Intentar obtenerlo directamente de la sesión
    if (!empty($_SESSION['user_id'])) {
        return $_SESSION['user_id'];
    }

    // 2. Si no está, pero hay un usuario logueado, buscarlo en la BD (Plan B)
    if (isset($_SESSION['usuario'])) {
        $user = $_SESSION['usuario'];
        $conn = $this->db->getConnection(); // Asegúrate de que getConnection() esté disponible
        $userId = 0; 
        
        $sql = 'SELECT id FROM usuarios WHERE nombre_usuario = ? LIMIT 1';
        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param('s', $user);
            $stmt->execute();
            $stmt->bind_result($userId);
            $stmt->fetch();
            $stmt->close();
        }

        // 3. Guardar en sesión para no tener que consultar la BD de nuevo
        if ($userId > 0) {
            $_SESSION['user_id'] = $userId;
        }
        return $userId;
    }
    
    return 0; 
}

    private function getAttemptState(int $userId, string $key): array {
        $state = ['attempt_used' => 0, 'score' => 0];
        $conn = $this->db->getConnection();

        // Encontrar el ejercicio_id basado en el evaluation_key (slug de unidad)
        $stmt = $conn->prepare('SELECT e.id FROM ejercicios e JOIN unidades u ON e.unidad_id = u.id WHERE u.slug = ? AND e.tipo = "evaluacion" ORDER BY e.orden ASC LIMIT 1');
        $stmt->bind_param('s', $key);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            return $state; // No se encontró el ejercicio
        }

        $row = $result->fetch_assoc();
        $ejercicioId = $row['id'];
        $stmt->close();

        $attempt_used = 0; // Inicializar variables
        $score = 0;

        if ($stmt = $conn->prepare('SELECT intento_usado, puntuacion FROM intentos_evaluacion WHERE usuario_id = ? AND ejercicio_id = ?')) {
            $stmt->bind_param('ii', $userId, $ejercicioId);
            if ($stmt->execute()) {
                $stmt->bind_result($attempt_used, $score);
                if ($stmt->fetch()) {
                    $state = ['attempt_used' => (int)$attempt_used, 'score' => (int)$score];
                }
            }
            $stmt->close();
        }

        return $state;
    }

    private function evaluateCode($key, $code, $def) {
        $norm = $this->normalizeCode($code);
        $details = [];
        $score = 0; 
        $max = 0;
        
        foreach ($def['rubric'] as $rule) {
            $max += (int)$rule['points'];
            $ok = preg_match($rule['regex'], $norm) === 1;
            $awarded = $ok ? (int)$rule['points'] : 0;
            $score += $awarded;
            $details[] = [
                'label' => $rule['label'],
                'correct' => $ok,
                'points_awarded' => $awarded,
                'points_total' => (int)$rule['points'],
                'feedback' => $ok ? 'Correcto' : ($rule['feedback'] ?? '')
            ];
        }
        
        return [ 'score' => $score, 'max' => $max, 'details' => $details ];
    }

    private function normalizeCode($code) {
        // Eliminar comentarios de una sola línea
        $code = preg_replace('/\/\/.*$/m', '', $code);
        // Eliminar comentarios de múltiples líneas
        $code = preg_replace('/\/\*.*?\*\//s', '', $code);
        // Reemplazar múltiples espacios/saltos de línea por un solo espacio
        $code = preg_replace('/\s+/', ' ', $code);
        $code = trim($code);
        return strtolower($code);
    }

    private function saveEvaluationResult($userId, $key, $score) {
        $conn = $this->db->getConnection();

        // Encontrar el ejercicio_id basado en el evaluation_key (slug de unidad)
        $stmt = $conn->prepare('SELECT e.id FROM ejercicios e JOIN unidades u ON e.unidad_id = u.id WHERE u.slug = ? AND e.tipo = "evaluacion" ORDER BY e.orden ASC LIMIT 1');
        $stmt->bind_param('s', $key);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            return false; // No se encontró el ejercicio
        }

        $row = $result->fetch_assoc();
        $ejercicioId = $row['id'];
        $stmt->close();

        $stmt = $conn->prepare('INSERT INTO intentos_evaluacion (usuario_id, ejercicio_id, intento_usado, puntuacion) VALUES (?, ?, 1, ?) ON DUPLICATE KEY UPDATE intento_usado = 1, puntuacion = VALUES(puntuacion), actualizado_en = CURRENT_TIMESTAMP');
        $stmt->bind_param('iii', $userId, $ejercicioId, $score);
        $result = $stmt->execute();
        $stmt->close();

        return $result;
    }

    private function resetEvaluationAttempt($userId, $key) {
        $conn = $this->db->getConnection();

        // Encontrar el ejercicio_id basado en el evaluation_key (slug de unidad)
        $stmt = $conn->prepare('SELECT e.id FROM ejercicios e JOIN unidades u ON e.unidad_id = u.id WHERE u.slug = ? AND e.tipo = "evaluacion" ORDER BY e.orden ASC LIMIT 1');
        $stmt->bind_param('s', $key);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            return false; // No se encontró el ejercicio
        }

        $row = $result->fetch_assoc();
        $ejercicioId = $row['id'];
        $stmt->close();

        $stmt = $conn->prepare('INSERT INTO intentos_evaluacion (usuario_id, ejercicio_id, intento_usado, puntuacion) VALUES (?, ?, 0, 0) ON DUPLICATE KEY UPDATE intento_usado = 0, puntuacion = 0, actualizado_en = CURRENT_TIMESTAMP');
        $stmt->bind_param('ii', $userId, $ejercicioId);
        $result = $stmt->execute();
        $stmt->close();

        return $result;
    }



    // ==========================================================
    // MÉTODO NUEVO PARA GUARDAR EL PROGRESO DEL EJERCICIO
    // ==========================================================
    private function saveProgress(string $unit, string $exercise) {
        $userId = $this->getUserId();

        if (!$userId) {
            Registrador::log($_SESSION['usuario'] ?? 'anonimo', $_SESSION['rol'] ?? 'anonimo', 'error', 'Intento de guardar progreso sin user_id');
            return false;
        }

        $conn = $this->db->getConnection();

        // Encontrar el ejercicio_id basado en slugs
        $stmt = $conn->prepare('SELECT e.id FROM ejercicios e JOIN unidades u ON e.unidad_id = u.id WHERE u.slug = ? AND e.slug = ? AND e.tipo = "practica" LIMIT 1');
        if (!$stmt) {
            Registrador::log($_SESSION['usuario'], $_SESSION['rol'] ?? 'Usuario', 'error_db', "Error de preparación al buscar ejercicio: " . $conn->error);
            return false;
        }

        $stmt->bind_param('ss', $unit, $exercise);
        if (!$stmt->execute()) {
            Registrador::log($_SESSION['usuario'], $_SESSION['rol'] ?? 'Usuario', 'error_db', "Error al ejecutar búsqueda de ejercicio: " . $stmt->error);
            $stmt->close();
            return false;
        }

        $result = $stmt->get_result();
        if ($result->num_rows === 0) {
            Registrador::log($_SESSION['usuario'], $_SESSION['rol'] ?? 'Usuario', 'error', "Ejercicio no encontrado: $unit/$exercise");
            $stmt->close();
            return false;
        }

        $row = $result->fetch_assoc();
        $ejercicioId = $row['id'];
        $stmt->close();

        // Insertar o Actualizar: Marca el ejercicio como completado (completado = 1)
        $sql = 'INSERT INTO progreso_usuario (usuario_id, ejercicio_id, completado) VALUES (?, ?, 1) ON DUPLICATE KEY UPDATE completado = 1, actualizado_en = CURRENT_TIMESTAMP';

        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param('ii', $userId, $ejercicioId);
            if ($stmt->execute()) {
                Registrador::log($_SESSION['usuario'], $_SESSION['rol'] ?? 'Usuario', 'progreso_guardado_controller', "Unidad: $unit, Ejercicio: $exercise");
                $stmt->close();
                return true;
            } else {
                Registrador::log($_SESSION['usuario'], $_SESSION['rol'] ?? 'Usuario', 'error_db', "Error al guardar progreso: " . $stmt->error);
            }
            $stmt->close();
        } else {
            Registrador::log($_SESSION['usuario'], $_SESSION['rol'] ?? 'Usuario', 'error_db', "Error de preparación de consulta al guardar progreso: " . $conn->error);
        }
        return false;
    }
    // ==========================================================
    // FIN: MÉTODO NUEVO
    // ==========================================================

    // Métodos existentes para ejercicios
    public function checkCode() {
        header('Content-Type: application/json');

        // Verificar sesión y CSRF
        if (!isset($_SESSION['usuario'])) {
            echo json_encode(['success' => false, 'error' => 'Sesión no válida']);
            return;
        }

        $input = json_decode(file_get_contents('php://input'), true);

        if (!isset($input['csrf_token']) || $input['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
            echo json_encode(['success' => false, 'error' => 'Token CSRF inválido']);
            return;
        }

        $action = $input['action'] ?? 'check';
        $unit = $input['unit'] ?? '';
        $exercise = $input['exercise'] ?? '';

        if ($action === 'update') {
            // Verificar rol docente
            $rol = $_SESSION['rol'] ?? '';
            if (!in_array($rol, ['Docente', 'Administrador'])) {
                echo json_encode(['success' => false, 'error' => 'Permisos insuficientes']);
                return;
            }
            $fields = $input['fields'] ?? [];
            require_once 'app/model/ModeloPractica.php';
            $practiceModel = new PracticeModel($this->db);
            if ($practiceModel->updateExercise($unit, $exercise, $fields)) {
                require_once 'app/model/Registrador.php';
                Registrador::log($_SESSION['usuario'], $_SESSION['rol'] ?? 'Usuario', 'exercise_update', $unit . '/' . $exercise . ':' . implode(',', array_keys($fields)));
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'error' => 'Error al actualizar']);
            }
            return;
        }

        $code = $input['code'] ?? '';

        // Verificar el código
        $result = $this->verifyCode($code, $unit, $exercise);

        $isCorrect = $result['success'];

        // Registrar intento
        $nombre_usuario = $_SESSION['usuario'] ?? 'Usuario';
        $rol = $_SESSION['rol'] ?? 'Usuario';
        Registrador::log($nombre_usuario, $rol, 'verificacion_codigo', "Verificación de código: $unit - $exercise - " . ($isCorrect ? 'Correcto' : 'Incorrecto'));

        // ==========================================================
        // LLAMADA CLAVE: GUARDAR PROGRESO SI ES CORRECTO
        // ==========================================================
        if ($isCorrect) {
            $this->saveProgress($unit, $exercise); // <--- ESTA ES LA LÍNEA QUE FALTABA
        }
        // ==========================================================

        echo json_encode($result);
    }

    private function getExerciseData($unit, $exercise) {
        require_once 'app/model/ModeloPractica.php';
        $practiceModel = new PracticeModel($this->db);
        $exerciseData = $practiceModel->getExercise($unit, $exercise);

        if ($exerciseData) {
            // Adaptar nombres a los usados en verifyCode
            return [
                'title' => $exerciseData['title'],
                'description' => '', // No hay descripción, pero se puede agregar o dejar vacío
                'instructions' => $exerciseData['instructions'],
                'example' => $exerciseData['example'],
                'expected_output' => $exerciseData['expected_output'],
                'solution' => $exerciseData['solution']
            ];
        }

        return [
            'title' => 'Ejercicio no encontrado',
            'description' => 'El ejercicio solicitado no existe',
            'instructions' => 'Por favor, selecciona un ejercicio válido.',
            'example' => '',
            'expected_output' => '',
            'solution' => ''
        ];
    }

    private function verifyCode($code, $unit, $exercise) {
        // Obtener la solución esperada
        $exerciseData = $this->getExerciseData($unit, $exercise);
        $expectedSolution = $exerciseData['solution'];

        // Usar el nuevo sistema de feedback dinámico
        require_once 'app/lib/FeedbackDinamico.php';

        try {
            // Generar feedback completo usando el sistema dinámico
            $resultado = FeedbackDinamico::analizarEjercicioCompleto($code, $expectedSolution);

            // Adaptar el formato para compatibilidad con el frontend existente
            $response = [
                'success' => $resultado['success'],
                'feedback' => $resultado['feedback'],
                'expected' => $expectedSolution,
                'lenguaje' => $resultado['lenguaje'],
                'score' => $resultado['score'],
                'similaridad' => $resultado['similaridad'] ?? 0,
                'tipo_errores' => $resultado['tipo_errores'] ?? 'desconocido'
            ];

            // Añadir recomendaciones si existen
            if (isset($resultado['recomendaciones']) && !empty($resultado['recomendaciones'])) {
                $response['recomendaciones'] = $resultado['recomendaciones'];
            }

            return $response;

        } catch (Exception $e) {
            // Fallback en caso de error
            error_log('Error en FeedbackDinamico: ' . $e->getMessage());

            return [
                'success' => false,
                'feedback' => ['Error interno del sistema. Por favor, intenta de nuevo.'],
                'expected' => $expectedSolution,
                'lenguaje' => 'desconocido',
                'error_tecnico' => $e->getMessage()
            ];
        }
    }

    private function checkVariableExercise($code, $exercise, &$feedback) {
        switch ($exercise) {
            case '1':
                if (!preg_match('/int\s+edad\s*=\s*25\s*;/', $code)) {
                    if (!preg_match('/int\s+edad/', $code)) {
                        $feedback[] = 'Falta declarar la variable "edad" de tipo int.';
                    } else if (!preg_match('/edad\s*=\s*25/', $code)) {
                        $feedback[] = 'La variable "edad" debe tener el valor 25.';
                    } else if (!preg_match('/;/', $code)) {
                        $feedback[] = 'No olvides el punto y coma (;) al final de la declaración.';
                    }
                    return false;
                }
                return true;
            
            case '2':
                // Se ha asumido que el regex de la 'A' es case-insensitive para hacerlo más permisivo
                $hasFloat = preg_match('/float\s+precio\s*=\s*15\.99\s*;/', $code);
                $hasChar = preg_match('/char\s+inicial\s*=\s*\'a\'\s*;/', $code); 
                
                if (!$hasFloat) {
                    $feedback[] = 'Falta declarar la variable "precio" de tipo float con valor 15.99.';
                }
                
                if (!$hasChar) {
                    $feedback[] = 'Falta declarar la variable "inicial" de tipo char con valor \'A\'.';
                }
                
                return $hasFloat && $hasChar;
        }
        return false;
    }

    private function checkOperatorExercise($code, $exercise, &$feedback) {
        switch ($exercise) {
            case '1':
                $hasA = preg_match('/int\s+a\s*=\s*10\s*;/', $code);
                $hasB = preg_match('/int\s+b\s*=\s*5\s*;/', $code);
                $hasSum = preg_match('/int\s+suma\s*=\s*a\s*\+\s*b\s*;/', $code);
                
                if (!$hasA) {
                    $feedback[] = 'Falta declarar la variable "a" con valor 10.';
                }
                if (!$hasB) {
                    $feedback[] = 'Falta declarar la variable "b" con valor 5.';
                }
                if (!$hasSum) {
                    $feedback[] = 'Falta calcular la suma de a + b y guardarla en "suma".';
                }
                
                return $hasA && $hasB && $hasSum;
        }
        return false;
    }

    private function checkConditionalExercise($code, $exercise, &$feedback) {
        switch ($exercise) {
            case '1':
                $hasVar = preg_match('/int\s+edad\s*=\s*18\s*;/', $code);
                $hasIf = preg_match('/if\s*\(\s*edad\s*>=\s*18\s*\)/', $code);
                $hasElse = preg_match('/else/', $code);
                
                if (!$hasVar) {
                    $feedback[] = 'Falta declarar la variable "edad" con valor 18.';
                }
                if (!$hasIf) {
                    $feedback[] = 'Falta la estructura if para verificar si edad >= 18.';
                }
                if (!$hasElse) {
                    $feedback[] = 'Falta la parte else de la estructura condicional.';
                }
                
                return $hasVar && $hasIf && $hasElse;
        }
        return false;
    }

    private function checkLoopExercise($code, $exercise, &$feedback) {
        switch ($exercise) {
            case '1':
                $hasFor = preg_match('/for\s*\(\s*int\s+i\s*=\s*1\s*;\s*i\s*<=\s*5\s*;\s*i\+\+\s*\)/', $code);
                
                if (!$hasFor) {
                    if (!preg_match('/for/', $code)) {
                        $feedback[] = 'Falta el bucle for.';
                    } else if (!preg_match('/int\s+i\s*=\s*1/', $code)) {
                        $feedback[] = 'El bucle debe inicializar i = 1.';
                    } else if (!preg_match('/i\s*<=\s*5/', $code)) {
                        $feedback[] = 'La condición debe ser i <= 5.';
                    } else if (!preg_match('/i\+\+/', $code)) {
                        $feedback[] = 'Falta el incremento i++.';
                    }
                    return false;
                }
                
                return true;
        }
        return false;
    }

    public function handleUpdateUnitOrder() {
        header('Content-Type: application/json');
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Método no permitido']);
            exit;
        }

        $unitId = $_POST['unit_id'] ?? null;
        $newOrder = $_POST['new_order'] ?? null;

        if (!$unitId || !$newOrder) {
            echo json_encode(['success' => false, 'message' => 'Faltan parámetros']);
            exit;
        }

        require_once 'app/model/ModeloUnidad.php';
        $unitModel = new ModeloUnidad($this->db);
        
        $success = $unitModel->reordenarUnidad($unitId, $newOrder);

        echo json_encode(['success' => $success]);
        exit;
    }

    public function handleCreateUnit() {
        header('Content-Type: application/json');
        
        $json = file_get_contents('php://input');
        $data = json_decode($json, true);

        $titulo = $data['titulo'] ?? null;
        $descripcion = $data['descripcion'] ?? null;
        $orden = $data['orden'] ?? 1;
        // Capturamos el trimestre (por defecto 1 si no viene)
        $trimestre = $data['trimestre'] ?? 1; 
        $docenteId = $_SESSION['user_id'] ?? null;

        if (!$titulo || !$docenteId) {
            echo json_encode(['success' => false, 'error' => 'Faltan datos obligatorios']);
            exit;
        }

        require_once 'app/model/ModeloUnidad.php';
        $unitModel = new ModeloUnidad($this->db);
        
        // PASAMOS LOS 5 ARGUMENTOS (añadimos $trimestre al final)
        if ($unitModel->crearUnidad($titulo, $descripcion, $docenteId, $orden, $trimestre)) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => 'No se pudo guardar en la base de datos']);
        }
        exit;
    }

    public function getUnitsByTrimestre() {
        header('Content-Type: application/json');
        
        $trimestre = $_GET['trimestre'] ?? 1;
        $userId = $_SESSION['user_id'] ?? null;
        $rol = $_SESSION['rol'] ?? 'Usuario'; // Obtenemos el rol de la sesión

        require_once 'app/model/ModeloUnidad.php';
        $unitModel = new ModeloUnidad($this->db);
        
        // Ahora enviamos el rol como tercer parámetro
        $units = $unitModel->obtenerUnidadesPorTrimestre($userId, $trimestre, $rol);
        $isVisible = $unitModel->esTrimestreVisible($userId, $trimestre);

        echo json_encode([
            'success' => true,
            'units' => $units,
            'isVisible' => $isVisible
        ]);
        exit;
    }

    public function toggleTrimestreVisibilidad() {
        header('Content-Type: application/json');
        $json = file_get_contents('php://input');
        $data = json_decode($json, true);

        $trimestre = $data['trimestre'] ?? null;
        $activo = $data['activo'] ? 1 : 0;
        $docenteId = $_SESSION['user_id'] ?? null;

        require_once 'app/model/ModeloUnidad.php';
        $unitModel = new ModeloUnidad($this->db);
        $success = $unitModel->actualizarVisibilidadTrimestre($docenteId, $trimestre, $activo);

        echo json_encode(['success' => $success]);
        exit;
    }
}
