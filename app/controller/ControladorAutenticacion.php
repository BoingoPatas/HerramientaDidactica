<?php

require_once 'app/model/ModeloUsuario.php';
require_once 'app/config/BaseDatos.php';
require_once 'app/model/Registrador.php';
require_once 'app/lib/Validacion.php';

class AuthController {
    private $model;
    private $db;

    public function __construct() {
        // Iniciar sesión si no está iniciada
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        $this->db = new Database();
        $this->model = new ModeloUsuario($this->db);
    }

    public function handleRequest() {
        // Asegurar que exista un token CSRF
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
            // Validación CSRF
            $csrfToken = $_POST['csrf_token'] ?? '';
            if (empty($csrfToken) || !hash_equals($_SESSION['csrf_token'], $csrfToken)) {
                $error_message = 'Solicitud inválida. Por favor, inténtalo de nuevo.';
                require 'app/view/InicioSesion.php';
                return;
            }

            $action = $_POST['action'];

            if ($action === 'register') {
                $this->register();
                return;
            } elseif ($action === 'login') {
                $this->login();
                return;
            }
        }
        
        // Si no hay acción o ya se ha iniciado sesión, mostrar la vista
        $this->showAuthView();
    }

    private function register() {
        $nombre_usuario = $this->sanitize($_POST['nombre_usuario_reg'] ?? '');
        $correo = $this->sanitize($_POST['correo_reg'] ?? '');
        $contrasena = $_POST['contrasena_reg'] ?? '';

        $validationError = $this->validateRegistration($nombre_usuario, $correo, $contrasena);
        if ($validationError !== null) {
            $error_message = $validationError;
            require 'app/view/InicioSesion.php';
            return;
        }

        if ($this->model->registrarUsuario($nombre_usuario, $correo, $contrasena)) {
            // Regenerar ID de sesión para prevenir fijación
            session_regenerate_id(true);
            $_SESSION['usuario'] = $nombre_usuario;
            
            // Obtener el rol real desde la base de datos después del registro
            $usuario_info = $this->model->obtenerUsuarioPorNombre($nombre_usuario);
            $_SESSION['rol'] = $usuario_info['rol'] ?? 'Usuario';
            $_SESSION['user_id'] = $usuario_info['id']; // Cómo olvidé esto
            
            // Registrar en bitácora
            Registrador::log($nombre_usuario, $_SESSION['rol'], 'registro_exitoso', 'Usuario registrado y autenticado');
            
            // Debug: Verificar sesión
            error_log("Registro exitoso - Usuario: " . $_SESSION['usuario'] . ", Rol: " . $_SESSION['rol']);
            
            header('Location: index.php');
            exit();
        } else {
            $error_message = "Error: El nombre de usuario o correo electrónico ya están registrados.";
            require 'app/view/InicioSesion.php';
            exit();
        }
    }

        private function login() {
        $nombre_usuario = $this->sanitize($_POST['nombre_usuario_login'] ?? '');
        $contrasena = $_POST['contrasena_login'] ?? '';

        $validationError = $this->validateLogin($nombre_usuario, $contrasena);
        if ($validationError !== null) {
            $error_message = $validationError;
            require 'app/view/InicioSesion.php';
            return;
        }

        // Obtener información del usuario incluyendo el rol
        $usuario_info = $this->model->verificarCredenciales($nombre_usuario, $contrasena);

        if ($usuario_info) {
            // Verificar si el usuario está inactivo
            if (isset($usuario_info['activo']) && $usuario_info['activo'] === false) {
                // Registrar intento de acceso de usuario inactivo
                Registrador::log($nombre_usuario, 'desconocido', 'login_inactivo', 'Intento de acceso de usuario inactivo');
                $error_message = "Error: Tu cuenta ha sido desactivada. Contacta al administrador.";
                require 'app/view/InicioSesion.php';
                exit();
            }

            // Regenerar ID de sesión para prevenir fijación
            session_regenerate_id(true);
            $_SESSION['usuario'] = $nombre_usuario;
            $_SESSION['rol'] = $usuario_info['rol']; // Usar el rol real de la BD
            
            // ✅ NUEVO: Guardar el user_id que YA VIENE en usuario_info
            if (isset($usuario_info['id'])) {
                $_SESSION['user_id'] = $usuario_info['id'];
                error_log("User ID guardado en sesión: " . $_SESSION['user_id']);
            } else {
                error_log("ERROR: No se pudo obtener user_id del resultado de verificarCredenciales");
            }
            
            // Registrar en bitácora
            Registrador::log($nombre_usuario, $_SESSION['rol'], 'login_exitoso', 'Inicio de sesión correcto');
            
            // Debug: Verificar que la sesión se está creando
            error_log("Sesión iniciada - Usuario: " . $_SESSION['usuario'] . ", Rol: " . $_SESSION['rol'] . ", User ID: " . ($_SESSION['user_id'] ?? 'NO OBTENIDO'));
            
            header('Location: index.php');
            exit();
        } else {
            // Registrar intento fallido (sin asignar rol)
            Registrador::log($nombre_usuario, 'desconocido', 'login_fallido', 'Credenciales inválidas');
            $error_message = "Error: Usuario o contraseña incorrectos.";
            require 'app/view/InicioSesion.php';
            exit();
        }
    }

    private function showAuthView() {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        require 'app/view/InicioSesion.php';
    }

    private function sanitize(string $value): string {
        return trim($value);
    }

    private function validateRegistration(string $usuario, string $correo, string $pwd): ?string {
        $validation = Validation::validateRegistration($usuario, $correo, $pwd);

        if ($validation !== true) {
            // Devolver el primer error encontrado
            return reset($validation);
        }

        return null;
    }

    private function validateLogin(string $usuario, string $pwd): ?string {
        $validation = Validation::validateLogin($usuario, $pwd);

        if ($validation !== true) {
            // Devolver el primer error encontrado
            return reset($validation);
        }

        return null;
    }
}
