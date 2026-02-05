<?php
require_once __DIR__ . '/../config/BaseDatos.php';
require_once __DIR__ . '/../model/ModeloEmote.php';

class ControladorEmote {
    private $db;
    private $emoteModel;

    public function __construct() {
        $this->db = new Database();
        $this->emoteModel = new ModeloEmote($this->db);
    }

    /**
     * Maneja las solicitudes POST para guardar reacciones de emotes
     */
    public function guardarReacciones() {
        // Verificar autenticación
        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'Usuario no autenticado']);
            return;
        }

        // Obtener datos JSON
        $input = json_decode(file_get_contents('php://input'), true);

        if (!$input) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Datos JSON inválidos']);
            return;
        }

        // Validar CSRF token
        $csrfToken = $input['csrf_token'] ?? '';
        if (empty($csrfToken) || !hash_equals($_SESSION['csrf_token'] ?? '', $csrfToken)) {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Token CSRF inválido']);
            return;
        }

        // Validar parámetros requeridos
        $unit = $input['unit'] ?? '';
        $reactions = $input['reactions'] ?? [];

        if (empty($unit) || !is_string($unit)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Unidad inválida']);
            return;
        }

        if (!is_array($reactions)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Reacciones inválidas']);
            return;
        }

        // Validar que las reacciones sean válidas (excluyendo userReaction)
        $validEmotes = ['like', 'happy', 'wow', 'think', 'shock'];
        foreach ($reactions as $emote => $count) {
            // Excluir userReaction del validation (es un campo especial)
            if ($emote === 'userReaction') {
                continue;
            }

            if (!in_array($emote, $validEmotes) || !is_numeric($count) || $count < 0) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Reacciones contienen datos inválidos']);
                return;
            }
        }

        try {
            $userId = $_SESSION['user_id'];
            $success = $this->emoteModel->saveEmoteReactions($userId, $unit, $reactions);

            if ($success) {
                echo json_encode(['success' => true, 'message' => 'Reacciones guardadas correctamente']);
            } else {
                http_response_code(500);
                echo json_encode(['success' => false, 'error' => 'Error al guardar reacciones']);
            }
        } catch (Exception $e) {
            error_log('Error guardando reacciones de emotes: ' . $e->getMessage());
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'Error interno del servidor']);
        }
    }

    /**
     * Maneja las solicitudes GET para obtener reacciones de emotes
     */
    public function obtenerReacciones() {
        // Verificar autenticación
        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'Usuario no autenticado']);
            return;
        }

        $unit = $_GET['unit'] ?? '';

        if (empty($unit)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Unidad requerida']);
            return;
        }

        try {
            $userId = $_SESSION['user_id'];
            $reactions = $this->emoteModel->getEmoteReactions($userId, $unit);

            echo json_encode([
                'success' => true,
                'reactions' => $reactions
            ]);
        } catch (Exception $e) {
            error_log('Error obteniendo reacciones de emotes: ' . $e->getMessage());
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'Error interno del servidor']);
        }
    }

    /**
     * Maneja las solicitudes GET para obtener todas las reacciones de emotes del usuario
     */
    public function obtenerTodasReacciones() {
        // Verificar autenticación
        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'Usuario no autenticado']);
            return;
        }

        try {
            $userId = $_SESSION['user_id'];
            $allReactions = $this->emoteModel->getAllEmoteReactions($userId);

            echo json_encode([
                'success' => true,
                'all_reactions' => $allReactions
            ]);
        } catch (Exception $e) {
            error_log('Error obteniendo todas las reacciones de emotes: ' . $e->getMessage());
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'Error interno del servidor']);
        }
    }

    /**
     * Maneja las solicitudes DELETE para eliminar reacciones de emotes
     */
    public function eliminarReacciones() {
        // Verificar autenticación
        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'Usuario no autenticado']);
            return;
        }

        // Obtener datos JSON
        $input = json_decode(file_get_contents('php://input'), true);

        if (!$input) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Datos JSON inválidos']);
            return;
        }

        // Validar CSRF token
        $csrfToken = $input['csrf_token'] ?? '';
        if (empty($csrfToken) || !hash_equals($_SESSION['csrf_token'] ?? '', $csrfToken)) {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Token CSRF inválido']);
            return;
        }

        $unit = $input['unit'] ?? '';

        if (empty($unit)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Unidad requerida']);
            return;
        }

        try {
            $userId = $_SESSION['user_id'];
            $success = $this->emoteModel->clearEmoteReactions($userId, $unit);

            if ($success) {
                echo json_encode(['success' => true, 'message' => 'Reacciones eliminadas correctamente']);
            } else {
                http_response_code(500);
                echo json_encode(['success' => false, 'error' => 'Error al eliminar reacciones']);
            }
        } catch (Exception $e) {
            error_log('Error eliminando reacciones de emotes: ' . $e->getMessage());
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'Error interno del servidor']);
        }
    }
}

// Routing de la API
if (!isset($_SESSION)) {
    session_start();
}

$controller = new ControladorEmote();

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

switch ($method) {
    case 'GET':
        if ($action === 'get_all') {
            $controller->obtenerTodasReacciones();
        } else {
            $controller->obtenerReacciones();
        }
        break;

    case 'POST':
        $controller->guardarReacciones();
        break;

    case 'DELETE':
        $controller->eliminarReacciones();
        break;

    default:
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Método no permitido']);
        break;
}
