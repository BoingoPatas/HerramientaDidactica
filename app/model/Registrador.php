<?php
require_once __DIR__ . '/../config/BaseDatos.php';
require_once 'ModeloUsuario.php';

class Registrador {
    private static function ensureBitacoraTable($conn): void {
        // Crear tabla bitacora si no existe
        $sql = "CREATE TABLE IF NOT EXISTS bitacora (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT,
            accion TEXT NOT NULL,
            fecha TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES usuarios(id) ON DELETE SET NULL
        )";
        $conn->query($sql);
    }

    public static function log(string $usuario, string $rol, string $evento, string $detalle = ''): void {
        try {
            // Obtener conexión a la base de datos
            $database = new Database();
            $conn = $database->getConnection();

            // Asegurar que la tabla bitacora existe
            self::ensureBitacoraTable($conn);

            // Obtener user_id desde el nombre de usuario
            $userModel = new ModeloUsuario($conn);
            $user = $userModel->getUserByUsername($usuario);

            if (!$user) {
                // Si no se encuentra el usuario, usar user_id = 0 o null
                $user_id = 0;
            } else {
                $user_id = $user['id'];
            }

            // Crear la acción combinando evento y detalle
            $accion = $evento;
            if (!empty($detalle)) {
                $accion .= ': ' . $detalle;
            }

            // Insertar en la tabla bitacora
            $sql = "INSERT INTO bitacora (user_id, accion) VALUES (?, ?)";
            $stmt = $conn->prepare($sql);

            if ($stmt) {
                $stmt->bind_param("is", $user_id, $accion);
                $stmt->execute();
                $stmt->close();
            }

            $conn->close();
        } catch (Exception $e) {
            // En caso de error, registrar internamente pero no interrumpir la ejecución
            error_log('Error al guardar en bitácora: ' . $e->getMessage());
        }
    }
}
