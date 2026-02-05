<?php
require_once __DIR__ . '/../config/BaseDatos.php';

class ModeloTema {
    private $db;
    private $conn;

    public function __construct($db) {
        $this->db = $db;
        $this->conn = (is_object($db) && method_exists($db, 'getConnection')) ? $db->getConnection() : $db;
        $this->ensureTable();
    }

    public function ensureTable() {
        $conn = $this->conn;
        $conn->query('CREATE TABLE IF NOT EXISTS temas (
            id INT AUTO_INCREMENT PRIMARY KEY,
            unidad_id INT NOT NULL,
            nombre VARCHAR(255) NOT NULL,
            descripcion TEXT,
            orden INT DEFAULT 0,
            activo TINYINT(1) NOT NULL DEFAULT 1,
            creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            actualizado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (unidad_id) REFERENCES unidades(id) ON DELETE CASCADE,
            INDEX idx_unidad_orden_activo (unidad_id, orden, activo),
            INDEX idx_activo (activo)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
    }

    /**
     * Lista todos los temas de una unidad
     *
     * @param int $unidadId ID de la unidad
     * @return array Array de temas
     */
    public function listarTemasPorUnidad(int $unidadId): array {
        $conn = $this->conn;
        $out = [];
        $stmt = $conn->prepare('SELECT id, unidad_id, nombre, descripcion, orden, activo FROM temas WHERE unidad_id = ? ORDER BY orden ASC, id ASC');
        if (!$stmt) {
            error_log("Prepare failed in listarTemasPorUnidad: {$conn->error}");
            return [];
        }

        $stmt->bind_param('i', $unidadId);
        if ($stmt->execute()) {
            $id = null; $unidad_id = null; $nombre = null; $descripcion = null; $orden = null; $activo = null;
            $stmt->bind_result($id, $unidad_id, $nombre, $descripcion, $orden, $activo);
            while ($stmt->fetch()) {
                $out[] = [
                    'id' => $id,
                    'unidad_id' => $unidad_id,
                    'nombre' => $nombre,
                    'descripcion' => $descripcion,
                    'orden' => $orden,
                    'activo' => $activo
                ];
            }
        } else {
            error_log("Execute failed in listarTemasPorUnidad: {$stmt->error}");
        }
        $stmt->close();
        return $out;
    }

    /**
     * Obtiene un tema específico
     *
     * @param int $id ID del tema
     * @return array|null Tema encontrado o null si no existe
     */
    public function obtenerTema(int $id): ?array {
        $conn = $this->conn;
        $stmt = $conn->prepare('SELECT id, unidad_id, nombre, descripcion, orden FROM temas WHERE id = ? LIMIT 1');
        if (!$stmt) {
            error_log("Prepare failed in obtenerTema: {$conn->error}");
            return null;
        }

        $stmt->bind_param('i', $id);
        if ($stmt->execute()) {
            $result = $stmt->get_result();
            if ($row = $result->fetch_assoc()) {
                $stmt->close();
                return $row;
            }
        } else {
            error_log("Execute failed in obtenerTema: {$stmt->error}");
        }
        $stmt->close();
        return null;
    }

    /**
     * Crea un nuevo tema
     *
     * @param int $unidadId ID de la unidad
     * @param string $nombre Nombre del tema
     * @param string $descripcion Descripción del tema
     * @param int $orden Orden de aparición
     * @return int|false ID del tema creado o false si falla
     */
    public function crearTema(int $unidadId, string $nombre, string $descripcion = '', int $orden = 0) {
        $conn = $this->conn;

        // Validaciones básicas
        if (empty($nombre)) {
            error_log("Validation failed: nombre is empty");
            return false;
        }

        // Validar que la unidad existe
        require_once __DIR__ . '/ModeloUnidad.php';
        $unitModel = new ModeloUnidad($this->db);
        if (!$unitModel->obtenerUnidadPorId($unidadId)) {
            error_log("Validation failed: unidad_id {$unidadId} does not exist");
            return false;
        }

        $stmt = $conn->prepare('INSERT INTO temas (unidad_id, nombre, descripcion, orden) VALUES (?, ?, ?, ?)');
        if (!$stmt) {
            error_log("Prepare failed in crearTema: {$conn->error}");
            return false;
        }

        $stmt->bind_param('isss', $unidadId, $nombre, $descripcion, $orden);
        $res = $stmt->execute();

        if (!$res) {
            error_log("Execute failed in crearTema: {$stmt->error}");
            $stmt->close();
            return false;
        }

        $insertId = $conn->insert_id;
        $stmt->close();
        return $insertId ? (int)$insertId : false;
    }

    /**
     * Actualiza un tema existente
     *
     * @param int $id ID del tema
     * @param array $fields Campos a actualizar
     * @return bool True si éxito, false si falla
     */
    public function actualizarTema(int $id, array $fields): bool {
        $conn = $this->conn;

        // Validar que el tema existe
        if (!$this->obtenerTema($id)) {
            error_log("Update failed: tema ID {$id} does not exist");
            return false;
        }

        $allowed = ['nombre', 'descripcion', 'orden'];
        $sets = [];
        $types = '';
        $values = [];

        foreach ($allowed as $col) {
            if (array_key_exists($col, $fields)) {
                $sets[] = "$col = ?";
                $types .= 's'; // Todos son strings para simplificar
                $values[] = $fields[$col];
            }
        }

        if (empty($sets)) {
            error_log("Update failed: no valid fields to update");
            return false;
        }

        $sql = 'UPDATE temas SET ' . implode(', ', $sets) . ' WHERE id = ? LIMIT 1';
        $types .= 'i';
        $values[] = $id;

        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            error_log("Prepare failed in actualizarTema: {$conn->error}");
            return false;
        }

        $refs = [];
        $refs[] = &$types;
        foreach ($values as $k => $value) {
            $refs[] = &$values[$k];
        }
        call_user_func_array([$stmt, 'bind_param'], $refs);

        $res = $stmt->execute();
        if (!$res) {
            error_log("Execute failed in actualizarTema: {$stmt->error}");
        }

        $stmt->close();
        return $res;
    }

    /**
     * Elimina un tema
     *
     * @param int $id ID del tema
     * @return bool True si éxito, false si falla
     */
    public function eliminarTema(int $id): bool {
        $conn = $this->conn;

        // Validar que el tema existe
        if (!$this->obtenerTema($id)) {
            error_log("Delete failed: tema ID {$id} does not exist");
            return false;
        }

        $stmt = $conn->prepare('DELETE FROM temas WHERE id = ? LIMIT 1');
        if (!$stmt) {
            error_log("Prepare failed in eliminarTema: {$conn->error}");
            return false;
        }

        $stmt->bind_param('i', $id);
        $res = $stmt->execute();

        if (!$res) {
            error_log("Execute failed in eliminarTema: {$stmt->error}");
        }

        $stmt->close();
        return $res;
    }

    /**
     * Reordena los temas de una unidad
     *
     * @param int $unidadId ID de la unidad
     * @param array $temaOrder Array con IDs de temas en nuevo orden
     * @return bool True si éxito, false si falla
     */
    public function reordenarTemas(int $unidadId, array $temaOrder): bool {
        $conn = $this->conn;
        $conn->begin_transaction();

        try {
            foreach ($temaOrder as $orden => $temaId) {
                $stmt = $conn->prepare('UPDATE temas SET orden = ? WHERE id = ? AND unidad_id = ?');
                $stmt->bind_param('iii', $orden, $temaId, $unidadId);
                if (!$stmt->execute()) {
                    throw new Exception("Failed to update order for tema ID {$temaId}");
                }
                $stmt->close();
            }

            $conn->commit();
            return true;
        } catch (Exception $e) {
            $conn->rollback();
            error_log("Reorder failed: {$e->getMessage()}");
            return false;
        }
    }

    /**
     * Obtiene temas con sus ejercicios asociados
     *
     * @param int $unidadId ID de la unidad
     * @return array Array de temas con sus ejercicios
     */
    public function obtenerTemasConEjercicios(int $unidadId): array {
        $conn = $this->conn;
        $temas = $this->listarTemasPorUnidad($unidadId);
        $result = [];

        foreach ($temas as $tema) {
            $stmt = $conn->prepare('SELECT id, slug, titulo, tipo FROM ejercicios WHERE tema_id = ? ORDER BY orden ASC');
            $stmt->bind_param('i', $tema['id']);
            $stmt->execute();
            $exercises = [];
            $result_exercises = $stmt->get_result();
            while ($row = $result_exercises->fetch_assoc()) {
                $exercises[] = $row;
            }
            $stmt->close();

            $result[] = [
                'tema' => $tema,
                'ejercicios' => $exercises
            ];
        }

        return $result;
    }

    /**
     * Habilita o inhabilita un tema
     *
     * @param int $id ID del tema
     * @param bool $active True para habilitar, false para inhabilitar
     * @return bool True si éxito, false si falla
     */
    public function setTopicActive(int $id, bool $active): bool {
        $conn = $this->conn;
        error_log("DEBUG: setTopicActive called with id={$id}, active={$active}");

        // Validar que el tema existe
        if (!$this->obtenerTema($id)) {
            error_log("Set active failed: tema ID {$id} does not exist");
            return false;
        }

        $stmt = $conn->prepare('UPDATE temas SET activo = ? WHERE id = ? LIMIT 1');
        if (!$stmt) {
            error_log("Prepare failed in setTopicActive: {$conn->error}");
            return false;
        }

        $activeInt = $active ? 1 : 0;
        error_log("DEBUG: About to execute with activeInt={$activeInt}, id={$id}");
        $stmt->bind_param('ii', $activeInt, $id);
        $res = $stmt->execute();

        if (!$res) {
            error_log("Execute failed in setTopicActive: {$stmt->error}");
        } else {
            error_log("DEBUG: setTopicActive executed successfully, affected rows: " . $stmt->affected_rows);
        }

        $stmt->close();
        return $res;
    }

    /**
     * Lista temas activos de una unidad (para estudiantes)
     *
     * @param int $unidadId ID de la unidad
     * @return array Array de temas activos
     */
    public function listarTemasActivosPorUnidad(int $unidadId): array {
        $conn = $this->conn;
        $out = [];
        $stmt = $conn->prepare('SELECT id, unidad_id, nombre, descripcion, orden, activo FROM temas WHERE unidad_id = ? AND activo = 1 ORDER BY orden ASC, id ASC');
        if (!$stmt) {
            error_log("Prepare failed in listarTemasActivosPorUnidad: {$conn->error}");
            return [];
        }

        $stmt->bind_param('i', $unidadId);
        if ($stmt->execute()) {
            $id = null; $unidad_id = null; $nombre = null; $descripcion = null; $orden = null; $activo = null;
            $stmt->bind_result($id, $unidad_id, $nombre, $descripcion, $orden, $activo);
            while ($stmt->fetch()) {
                $out[] = [
                    'id' => $id,
                    'unidad_id' => $unidad_id,
                    'nombre' => $nombre,
                    'descripcion' => $descripcion,
                    'orden' => $orden,
                    'activo' => $activo
                ];
            }
        } else {
            error_log("Execute failed in listarTemasActivosPorUnidad: {$stmt->error}");
        }
        $stmt->close();
        return $out;
    }

    /**
     * Elimina permanentemente un tema (eliminación física)
     *
     * @param int $id ID del tema
     * @return bool True si éxito, false si falla
     */
    public function eliminarTemaPermanentemente(int $id): bool {
        $conn = $this->conn;

        // Validar que el tema existe
        if (!$this->obtenerTema($id)) {
            error_log("Delete permanently failed: tema ID {$id} does not exist");
            return false;
        }

        $stmt = $conn->prepare('DELETE FROM temas WHERE id = ? LIMIT 1');
        if (!$stmt) {
            error_log("Prepare failed in eliminarTemaPermanentemente: {$conn->error}");
            return false;
        }

        $stmt->bind_param('i', $id);
        $res = $stmt->execute();

        if (!$res) {
            error_log("Execute failed in eliminarTemaPermanentemente: {$stmt->error}");
        }

        $stmt->close();
        return $res;
    }
}
?>
