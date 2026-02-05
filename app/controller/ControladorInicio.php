<?php
class HomeController {
    private $db;
    private $dbAvailable = false;
    private $model;

    public function __construct() {
        require_once __DIR__ . '/../config/BaseDatos.php';
        require_once __DIR__ . '/../model/ModeloUsuario.php';
        // Proteger la conexión a la base de datos: si falla, marcamos como no disponible
        try {
            $database = new Database();
            $this->db = $database->getConnection();
            $this->model = new ModeloUsuario($this->db);
            $this->dbAvailable = true;
        } catch (Exception $e) {
            error_log('ControladorInicio: No se pudo conectar a la base de datos: ' . $e->getMessage());
            // No abortamos; continuamos con $dbAvailable = false para que la app sea más tolerante
            $this->db = null;
            $this->model = null;
            $this->dbAvailable = false;
        }
    }

        public function showHomePage() {
    // Verificar sesión (redundante pero segura)
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    if (!isset($_SESSION['usuario'])) {
        header('Location: index.php');
        exit();
    }

    // ✅ NUEVO: Verificar que el usuario sigue activo en la base de datos
    // Si la DB no está disponible (ej. reinicio del servidor), no destruimos la sesión
    $primera_vez = false; // Por defecto
    if ($this->dbAvailable && $this->model) {
        try {
            $usuario_actual = $this->model->obtenerUsuarioPorNombre($_SESSION['usuario']);
            if (!$usuario_actual || !$usuario_actual['activo']) {
                // Usuario no existe o está inactivo - cerrar sesión
                session_destroy();
                header('Location: index.php');
                exit();
            }
            $primera_vez = $usuario_actual['primera_vez'] == 1;
        } catch (Exception $e) {
            // En caso de error inesperado consultando la DB, registrarlo y permitir la sesión
            error_log('HomeController: Error verificando usuario en DB: ' . $e->getMessage());
        }
    } else {
        error_log('HomeController: DB no disponible; se omite verificación de usuario (manteniendo sesión).');
    }

    $nombre_usuario = $_SESSION['usuario'];
    $rol = $_SESSION['rol'] ?? 'Usuario';

    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    // Obtener datos reales para el dashboard
    $dashboardData = $this->getDashboardData();

    // Obtener datos de secciones para administradores
    $sectionsData = [];
    if ($rol === 'Administrador' && $this->dbAvailable && $this->model) {
        try {
            $sectionsData = $this->model->getSectionsData();
        } catch (Exception $e) {
            error_log('HomeController: Error obteniendo datos de secciones: ' . $e->getMessage());
            $sectionsData = [];
        }
    }

    // Obtener datos de bitacora para administradores
    $bitacoraData = [];
    if ($rol === 'Administrador' && $this->dbAvailable) {
        try {
            $bitacoraData = $this->getBitacoraData();
        } catch (Exception $e) {
            error_log('HomeController: Error obteniendo datos de bitacora: ' . $e->getMessage());
            $bitacoraData = [];
        }
    }

    $pageTitle = "🏠 ¡Revisa tu progreso y comienza a aprender! 📊";

    // Pasar las variables a la vista
    require 'app/view/Inicio.php';
    }

    private function getDashboardData() {
        $userId = $_SESSION['user_id'] ?? 0;
        $data = [
            'unidades_completadas' => 0,
            'ultima_unidad' => 'Ninguna',
            'progreso_total' => 0,
            'racha_dias' => 0
        ];

        if (!$userId) {
            return $data;
        }

        try {
            // 1. Obtener progreso de todas las unidades
            $progressData = $this->getUserProgress($userId);
            
            // 2. Calcular unidades completadas (100% de progreso)
            $data['unidades_completadas'] = $this->countCompletedUnits($progressData);
            
            // 3. Obtener última unidad activa
            $data['ultima_unidad'] = $this->getLastActiveUnit($progressData);
            
            // 4. Calcular progreso total general
            $data['progreso_total'] = $this->calculateOverallProgress($progressData);
            
            // 5. Obtener racha (placeholder por ahora)
            $data['racha_dias'] = $this->getCurrentStreak($userId);
            
        } catch (Exception $e) {
            error_log("Error obteniendo datos del dashboard: " . $e->getMessage());
        }

        return $data;
    }

    private function getUserProgress($userId) {
        $progress = [];

        // Si la DB no está disponible, devolvemos progreso vacío
        if (!$this->db) {
            error_log('HomeController::getUserProgress - DB no disponible, devolviendo progreso vacío.');
            return $progress;
        }

        // Obtener ejercicios completados
        $stmt = $this->db->prepare('SELECT u.slug as unit, e.slug as exercise FROM progreso_usuario p JOIN ejercicios e ON p.ejercicio_id = e.id JOIN unidades u ON e.unidad_id = u.id WHERE p.usuario_id = ? AND p.completado = 1');
        if ($stmt) {
            $stmt->bind_param('i', $userId);
            $stmt->execute();

            // Inicializar variables para bind_result
            $unit = '';
            $exercise = '';
            $stmt->bind_result($unit, $exercise);

            while ($stmt->fetch()) {
                if (!isset($progress[$unit])) {
                    $progress[$unit] = ['exercises' => []];
                }
                $progress[$unit]['exercises'][] = $exercise;
            }
            $stmt->close();
        }

        // Obtener evaluaciones completadas
        $evalStmt = $this->db->prepare('SELECT u.slug as evaluation_key FROM intentos_evaluacion i JOIN ejercicios e ON i.ejercicio_id = e.id JOIN unidades u ON e.unidad_id = u.id WHERE i.usuario_id = ? AND i.intento_usado = 1');
        if ($evalStmt) {
            $evalStmt->bind_param('i', $userId);
            $evalStmt->execute();

            // Inicializar variable para bind_result
            $evalKey = '';
            $evalStmt->bind_result($evalKey);

            while ($evalStmt->fetch()) {
                if (isset($progress[$evalKey])) {
                    $progress[$evalKey]['evaluation_completed'] = true;
                } else {
                    $progress[$evalKey] = ['evaluation_completed' => true, 'exercises' => []];
                }
            }
            $evalStmt->close();
        }

        return $progress;
    }

    private function countCompletedUnits($progressData) {
        $completed = 0;
        $unitRequirements = [
            'variables' => ['exercises_count' => 2, 'requires_evaluation' => true],
            'operadores' => ['exercises_count' => 1, 'requires_evaluation' => true],
            'condicionales' => ['exercises_count' => 1, 'requires_evaluation' => true],
            'bucles' => ['exercises_count' => 1, 'requires_evaluation' => true]
        ];

        foreach ($unitRequirements as $unit => $requirements) {
            if (isset($progressData[$unit])) {
                $unitData = $progressData[$unit];
                $exercisesCompleted = count($unitData['exercises'] ?? []);
                $evaluationCompleted = $unitData['evaluation_completed'] ?? false;
                
                if ($exercisesCompleted >= $requirements['exercises_count'] && 
                    (!$requirements['requires_evaluation'] || $evaluationCompleted)) {
                    $completed++;
                }
            }
        }

        return $completed;
    }

    private function getLastActiveUnit($progressData) {
        $units = ['variables', 'operadores', 'condicionales', 'bucles'];
        $lastUnit = 'Ninguna';
        
        // Buscar la última unidad con actividad
        foreach ($units as $unit) {
            if (isset($progressData[$unit]) && 
                (count($progressData[$unit]['exercises'] ?? []) > 0 || 
                ($progressData[$unit]['evaluation_completed'] ?? false))) {
                $lastUnit = $this->getUnitDisplayName($unit);
            }
        }
        
        return $lastUnit;
    }

    private function calculateOverallProgress($progressData) {
        $totalItems = 0;
        $completedItems = 0;
        
        $unitStructure = [
            'variables' => ['exercises' => 2, 'evaluation' => 1],
            'operadores' => ['exercises' => 1, 'evaluation' => 1],
            'condicionales' => ['exercises' => 1, 'evaluation' => 1],
            'bucles' => ['exercises' => 1, 'evaluation' => 1]
        ];

        foreach ($unitStructure as $unit => $items) {
            $totalItems += $items['exercises'] + $items['evaluation'];
            
            if (isset($progressData[$unit])) {
                $unitData = $progressData[$unit];
                $completedItems += count($unitData['exercises'] ?? []);
                if ($unitData['evaluation_completed'] ?? false) {
                    $completedItems += 1;
                }
            }
        }

        return $totalItems > 0 ? round(($completedItems / $totalItems) * 100) : 0;
    }

    private function getCurrentStreak($userId) {
        // Por ahora es placeholder - se implementará más adelante
        // Podemos usar la tabla de logs para calcular la racha
        return 0;
    }

    private function getUnitDisplayName($unitKey) {
        $names = [
            'variables' => 'Variables y Tipos',
            'operadores' => 'Operadores Aritméticos',
            'condicionales' => 'Estructuras Condicionales',
            'bucles' => 'Bucles y Repeticiones'
        ];
        return $names[$unitKey] ?? $unitKey;
    }

    private function getBitacoraData() {
        $bitacoraData = [];

        if (!$this->db) {
            error_log('HomeController::getBitacoraData - DB no disponible, devolviendo datos vacíos.');
            return $bitacoraData;
        }

        // Query actualizada para usar la estructura correcta de la tabla
        $sql = "SELECT b.id, b.user_id, b.accion, b.timestamp as fecha, u.nombre_usuario
                FROM bitacora b
                LEFT JOIN usuarios u ON b.user_id = u.id
                ORDER BY b.id DESC LIMIT 100";

        $stmt = $this->db->prepare($sql);
        if ($stmt) {
            $stmt->execute();
            $result = $stmt->get_result();

            while ($row = $result->fetch_assoc()) {
                $bitacoraData[] = [
                    'user_id' => $row['user_id'],
                    'usuario' => $row['nombre_usuario'] ?? 'Usuario desconocido',
                    'accion' => $row['accion'],
                    'fecha' => $row['fecha'] ?? date('Y-m-d H:i:s')
                ];
            }
            $stmt->close();
        } else {
            error_log('HomeController::getBitacoraData - Error preparando query: ' . $this->db->error);
        }

        return $bitacoraData;
    }
}
