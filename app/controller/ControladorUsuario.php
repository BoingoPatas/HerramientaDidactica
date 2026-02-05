<?php
require_once 'app/model/Registrador.php';
require_once 'app/config/BaseDatos.php';

class UserController {
    private $db;
    private $userModel;

    public function __construct() {
        $this->db = new Database();
        require_once 'app/model/ModeloUsuario.php';
        $this->userModel = new ModeloUsuario($this->db);
    }

    public function showSettingsPage() {
        // Verificar sesión
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

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
        Registrador::log($nombre_usuario, $rol, 'acceso_configuracion', 'Acceso a página de configuración');

        $pageTitle = "⚙️ Configuración de cuenta";

        // Obtener datos actuales del usuario
        $userData = $this->userModel->obtenerUsuarioPorNombre($nombre_usuario);

        include 'app/view/Configuracion.php';
    }

    public function updateName() {
        header('Content-Type: application/json; charset=utf-8');

        if (!isset($_SESSION['usuario'])) {
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'Usuario no autenticado']);
            exit();
        }

        $input = json_decode(file_get_contents('php://input'), true);

        if (!isset($input['csrf_token']) || !hash_equals($_SESSION['csrf_token'] ?? '', $input['csrf_token'])) {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'CSRF inválido']);
            exit();
        }

        $newName = trim($input['name'] ?? '');
        if (empty($newName)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Nombre no válido']);
            exit();
        }

        // Evitar cambiar el nombre a uno ya existente
        if ($this->userModel->obtenerUsuarioPorNombre($newName) && $newName !== $_SESSION['usuario']) {
            http_response_code(409);
            echo json_encode(['success' => false, 'error' => 'El nombre ya está en uso']);
            exit();
        }

        $userId = $_SESSION['user_id'] ?? null;
        if (!$userId) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'ID de usuario no encontrado']);
            exit();
        }

        try {
            $success = $this->userModel->updateUser($userId, ['nombre_usuario' => $newName]);
            if ($success) {
                // Actualizar sesión
                $_SESSION['usuario'] = $newName;
                Registrador::log($_SESSION['usuario'], $_SESSION['rol'] ?? 'Usuario', 'cambio_nombre', 'Nombre cambiado');
                echo json_encode(['success' => true]);
            } else {
                http_response_code(500);
                echo json_encode(['success' => false, 'error' => 'Error al actualizar el nombre']);
            }
        } catch (Exception $e) {
            error_log('Error cambiando nombre: ' . $e->getMessage());
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'Error interno']);
        }
    }

    public function updatePassword() {
        header('Content-Type: application/json; charset=utf-8');

        if (!isset($_SESSION['usuario'])) {
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'Usuario no autenticado']);
            exit();
        }

        $input = json_decode(file_get_contents('php://input'), true);

        if (!isset($input['csrf_token']) || !hash_equals($_SESSION['csrf_token'] ?? '', $input['csrf_token'])) {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'CSRF inválido']);
            exit();
        }

        $oldPassword = $input['old_password'] ?? '';
        $newPassword = $input['new_password'] ?? '';
        $confirmPassword = $input['confirm_password'] ?? '';

        // Validaciones
        if (empty($oldPassword) || empty($newPassword) || empty($confirmPassword)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Todos los campos son obligatorios']);
            exit();
        }

        $userData = $this->userModel->obtenerUsuarioPorNombre($_SESSION['usuario']);
        if (!$userData || !password_verify($oldPassword, $userData['contraseña'])) {
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'Contraseña actual incorrecta']);
            exit();
        }

        // Validar nueva contraseña
        if (!$this->validatePassword($newPassword)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'La contraseña debe tener al menos 8 caracteres, incluyendo mayúscula, minúscula, número y carácter especial']);
            exit();
        }

        if ($newPassword !== $confirmPassword) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'La nueva contraseña y la confirmación no coinciden']);
            exit();
        }

        $userId = $_SESSION['user_id'] ?? null;
        if (!$userId) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'ID de usuario no encontrado']);
            exit();
        }

        try {
            $success = $this->userModel->updateUser($userId, ['contrasena' => $newPassword]);
            if ($success) {
                Registrador::log($_SESSION['usuario'], $_SESSION['rol'] ?? 'Usuario', 'cambio_contraseña', 'Contraseña cambiada');
                echo json_encode(['success' => true]);
            } else {
                http_response_code(500);
                echo json_encode(['success' => false, 'error' => 'Error al actualizar la contraseña']);
            }
        } catch (Exception $e) {
            error_log('Error cambiando contraseña: ' . $e->getMessage());
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'Error interno']);
        }
    }

    private function validatePassword($password) {
        // Al menos 8 caracteres
        if (strlen($password) < 8) return false;

        // Al menos una mayúscula
        if (!preg_match('/[A-Z]/', $password)) return false;

        // Al menos una minúscula
        if (!preg_match('/[a-z]/', $password)) return false;

        // Al menos un número
        if (!preg_match('/[0-9]/', $password)) return false;

        // Al menos un carácter especial
        if (!preg_match('/[!@#$%^&*()_+\-=\[\]{};\':"\\|,.<>\/?]/', $password)) return false;

        return true;
    }
}
?>
