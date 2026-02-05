<?php
// Configuración de sesión PRIMERO
$secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443);
ini_set('session.use_strict_mode', '1');
// Parámetros de cookies de sesión: usar formato de array de opciones si está disponible (PHP 7.3+), de lo contrario usar firma heredada
$cookieParams = [
    'lifetime' => 0,
    'path' => '/',
    'domain' => '',
    'secure' => $secure,
    'httponly' => true,
    'samesite' => 'Lax'
];
if (defined('PHP_VERSION_ID') && PHP_VERSION_ID >= 70300) {
    session_set_cookie_params($cookieParams);
} else {
    // Firma heredada: lifetime, path, domain, secure, httponly
    session_set_cookie_params($cookieParams['lifetime'], $cookieParams['path'], $cookieParams['domain'], $cookieParams['secure'], $cookieParams['httponly']);
}
error_log('Session cookie params applied: ' . print_r($cookieParams, true));

// LUEGO iniciar sesión
session_start();

// --- Endpoint temporal de depuración ---
if (isset($_GET['debug']) && $_GET['debug'] === 'session') {
    header('Content-Type: text/plain; charset=utf-8');
    echo "=== DEBUG SESSION DUMP ===\n";
    echo "Session ID: " . session_id() . "\n";
    echo "SESSION VARS:\n";
    var_export($_SESSION);
    echo "\n\nHEADERS LIST:\n";
    var_export(headers_list());
    echo "\n\nLAST PHP ERROR (error_get_last):\n";
    var_export(error_get_last());
    echo "\n\nPHP INFO (partial):\n";
    echo 'PHP version: ' . PHP_VERSION . "\n";
    exit();
}

// DEBUG EXTENDIDO
error_log("=== DEBUG INDEX.PH START ===");
error_log("SESSION ID: " . session_id());
error_log("Usuario en sesión: " . ($_SESSION['usuario'] ?? 'NO HAY USUARIO'));
error_log("Rol en sesión: " . ($_SESSION['rol'] ?? 'NO HAY ROL'));
error_log("REQUEST METHOD: " . $_SERVER['REQUEST_METHOD']);
error_log("REQUEST URI: " . $_SERVER['REQUEST_URI']);
error_log("GET params: " . print_r($_GET, true));

// Manejar logout
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    error_log("=== LOGOUT DETECTADO ===");
    $usuario = $_SESSION['usuario'] ?? 'desconocido';
    $rol = $_SESSION['rol'] ?? 'desconocido';
    require_once 'app/model/Registrador.php';
    Registrador::log($usuario, $rol, 'logout', 'Cierre de sesión');
    
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        error_log('Logout: session cookie params: ' . print_r($params, true));
        // Usar formato de array de opciones de PHP 7.3+ si está disponible para manejo confiable de SameSite
        if (defined('PHP_VERSION_ID') && PHP_VERSION_ID >= 70300) {
            $options = [
                'expires' => time() - 42000,
                'path' => $params['path'] ?? '/',
                'domain' => $params['domain'] ?? '',
                'secure' => $params['secure'] ?? false,
                'httponly' => $params['httponly'] ?? true,
                'samesite' => $params['samesite'] ?? 'Lax'
            ];
            setcookie(session_name(), '', $options);
            error_log('Logout: setcookie with options array executed');
        } else {
            // Respaldo para versiones antiguas de PHP
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
            error_log('Logout: setcookie fallback executed');
        }
    }
    session_destroy();
    
    header('Location: index.php');
    exit();
}

// ----------------------------------------------------------------------
// MANEJO DE RUTAS Y CONTROLADORES
// ----------------------------------------------------------------------

// PRIMERO verificar si el usuario está autenticado
if (isset($_SESSION['usuario'])) {
    error_log("=== USUARIO AUTENTICADO - MANEJANDO RUTA ===");
    
    // Si está autenticado y no hay página específica, redirigir a home
    if (!isset($_GET['page']) && !isset($_GET['action'])) {
        error_log("Redirigiendo a home por defecto");
        header('Location: index.php?page=home');
        exit();
    }
    
    // --- MANEJO DE ACCIONES AJAX ---
    if (isset($_GET['action'])) {
        $action = $_GET['action'];
        error_log("Acción detectada: " . $action);
        
        switch ($action) {
            case 'log':
                require 'app/controller/Registro.php';
                exit();
                
            case 'progress':
                require 'app/controller/Progreso.php';
                exit();
                
            case 'evaluation_api':
                require_once 'app/controller/ControladorContenido.php';
                $controller = new ContentController();
                $controller->handleEvaluationApi();
                exit();

            case 'check_code':
                require 'app/controller/VerificarCodigo.php';
                exit();
            case 'manage':
                require 'app/controller/Gestionar.php';
                exit();

            case 'content_api':
                require_once 'app/controller/ControladorContenido.php';
                $controller = new ContentController();
                $controller->handleContentApi();
                exit();

            case 'unit_api':
                require_once 'app/controller/ControladorContenido.php';
                $controller = new ContentController();
                $controller->handleUnitApi();
                exit();

            case 'units_list':
                require_once 'app/controller/ControladorContenido.php';
                $controller = new ContentController();
                $controller->handleUnitsList();
                exit();

            case 'topics':
                require_once 'app/controller/ControladorContenido.php';
                $controller = new ContentController();
                $controller->handleTopicsApi();
                exit();

            case 'topic_exercises':
                require_once 'app/controller/ControladorContenido.php';
                $controller = new ContentController();
                $controller->handleTopicExercisesApi();
                exit();

            case 'get_topic_exercises':
                require_once 'app/controller/Gestionar.php';
                exit();

            case 'get_all_units':
                require_once 'app/controller/ControladorContenido.php';
                $controller = new ContentController();
                $controller->getAllUnitsJSON();
                break;

            case 'change_unit_order':
                require_once 'app/controller/ControladorContenido.php';
                $controller = new ContentController();
                $controller->handleUpdateUnitOrder();
                break;

            case 'create_unit':
                require_once 'app/controller/ControladorContenido.php';
                $controller = new ContentController();
                $controller->handleCreateUnit();
                break;

            case 'get_units_by_trimestre':
                require_once 'app/controller/ControladorContenido.php';
                $controller = new ContentController();
                $controller->getUnitsByTrimestre();
                break;

                case 'toggle_trimestre_status':
            require_once 'app/controller/ControladorContenido.php';
            $controller = new ContentController();
            $controller->toggleTrimestreVisibilidad();
            break;

            case 'emote_api':
                require 'app/controller/ControladorEmote.php';
                exit();

            case 'update_name':
                header('Content-Type: application/json; charset=utf-8');
                if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                    http_response_code(405);
                    echo json_encode(['success' => false, 'error' => 'Método no permitido']);
                    exit();
                }
                require_once 'app/controller/ControladorUsuario.php';
                $controller = new UserController();
                $controller->updateName();
                exit();

            case 'update_password':
                header('Content-Type: application/json; charset=utf-8');
                if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                    http_response_code(405);
                    echo json_encode(['success' => false, 'error' => 'Método no permitido']);
                    exit();
                }
                require_once 'app/controller/ControladorUsuario.php';
                $controller = new UserController();
                $controller->updatePassword();
                exit();

            case 'update_primera_vez':
                header('Content-Type: application/json; charset=utf-8');
                if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                    http_response_code(405);
                    echo json_encode(['success' => false, 'error' => 'Método no permitido']);
                    exit();
                }

                $input = json_decode(file_get_contents('php://input'), true);
                if (!isset($input['primera_vez']) || !is_numeric($input['primera_vez'])) {
                    http_response_code(400);
                    echo json_encode(['success' => false, 'error' => 'Datos inválidos']);
                    exit();
                }

                require_once 'app/model/ModeloUsuario.php';
                require_once 'app/config/BaseDatos.php';

                try {
                    $database = new Database();
                    $db = $database->getConnection();
                    $userModel = new ModeloUsuario($db);

                    $userId = $_SESSION['user_id'] ?? null;
                    if (!$userId) {
                        http_response_code(401);
                        echo json_encode(['success' => false, 'error' => 'Usuario no autenticado']);
                        exit();
                    }

                    $success = $userModel->updateUser($userId, ['primera_vez' => (int)$input['primera_vez']]);
                    echo json_encode(['success' => $success]);

                } catch (Exception $e) {
                    error_log('Error al actualizar primera_vez: ' . $e->getMessage());
                    http_response_code(500);
                    echo json_encode(['success' => false, 'error' => 'Error interno']);
                }
                exit();

            default:
                error_log("Acción no reconocida: " . $action);
                break;
        }
    }
    // --- FIN MANEJO AJAX ---

    // Usuario autenticado - manejar diferentes páginas (Lógica de vista HTML)
    $page = $_GET['page'] ?? 'home';
    error_log("Página solicitada: " . $page);
    
switch ($page) {
    case 'home':
        error_log("Cargando ControladorInicio...");
        require_once 'app/controller/ControladorInicio.php';
        $controller = new HomeController();
        $controller->showHomePage();
        break;
        
    case 'content':
        require_once 'app/controller/ControladorContenido.php';
        $controller = new ContentController();
        $controller->showContentPage();
        break;

    case 'practices':
        require_once 'app/controller/ControladorContenido.php';
        $controller = new ContentController();
        $controller->showPracticesPage();
        break;

    case 'exercise':
        require_once 'app/controller/ControladorContenido.php';
        $controller = new ContentController();
        $controller->showExercisePage();
        break;

    case 'evaluation':
        require_once 'app/controller/ControladorContenido.php';
        $controller = new ContentController();
        $controller->showEvaluationPage();
        break;

    case 'settings':
        require_once 'app/controller/ControladorUsuario.php';
        $controller = new UserController();
        $controller->showSettingsPage();
        break;

    default:
        error_log("Página no reconocida, cargando home por defecto");
        require_once 'app/controller/ControladorInicio.php';
        $controller = new HomeController();
        $controller->showHomePage();
    }
    exit();
}

error_log("=== USUARIO NO AUTENTICADO - MANEJANDO AUTH ===");

// Usuario NO autenticado - manejar auth
require_once 'app/controller/ControladorAutenticacion.php';
try {
    $authController = new AuthController();
    $authController->handleRequest();
} catch (Exception $e) {
    error_log("ERROR en ControladorAutenticacion: " . $e->getMessage());
    echo "Error: " . $e->getMessage() . ". Por favor, inténtelo de nuevo.";
}
