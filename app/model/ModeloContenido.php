<?php
require_once __DIR__ . '/../config/BaseDatos.php';

class ContentModel {
    private $db;
    private $conn;

    public function __construct($db) {
        $this->db = $db;
        $this->conn = (is_object($db) && method_exists($db, 'getConnection')) ? $db->getConnection() : $db;
        $this->ensureTable();
    }

    public function ensureTable() {
        $conn = $this->conn;
        $conn->query('CREATE TABLE IF NOT EXISTS contenido_didactico (
            id INT AUTO_INCREMENT PRIMARY KEY,
            unidad_id INT NOT NULL,
            tema_id INT NULL,
            tipo ENUM("texto", "documento", "video", "enlace", "imagen") NOT NULL,
            titulo VARCHAR(255) NOT NULL,
            contenido TEXT,
            url VARCHAR(500),
            orden INT NOT NULL DEFAULT 0,
            activo TINYINT(1) NOT NULL DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (unidad_id) REFERENCES unidades(id) ON DELETE CASCADE,
            FOREIGN KEY (tema_id) REFERENCES temas(id) ON DELETE SET NULL,
            INDEX idx_unidad_orden (unidad_id, orden),
            INDEX idx_tema_orden (tema_id, orden),
            INDEX idx_activo (activo)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');

        // Add tema_id column if it doesn't exist (for existing installations)
        $result = $conn->query("SHOW COLUMNS FROM contenido_didactico LIKE 'tema_id'");
        if ($result->num_rows === 0) {
            $conn->query('ALTER TABLE contenido_didactico ADD COLUMN tema_id INT NULL AFTER unidad_id');
            $conn->query('ALTER TABLE contenido_didactico ADD FOREIGN KEY (tema_id) REFERENCES temas(id) ON DELETE SET NULL');
            $conn->query('ALTER TABLE contenido_didactico ADD INDEX idx_tema_orden (tema_id, orden)');
        }
    }

    public function listContentByUnit(int $unidadId, bool $onlyActive = true): array {
        $conn = $this->conn;
        $out = [];
        $sql = 'SELECT id, unidad_id, tema_id, tipo, titulo, contenido, url, orden, activo FROM contenido_didactico WHERE unidad_id = ?';
        if ($onlyActive) {
            $sql .= ' AND activo = 1';
        }
        $sql .= ' ORDER BY orden ASC, id ASC';

        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            error_log("Prepare failed in listContentByUnit: {$conn->error}");
            return [];
        }

        $stmt->bind_param('i', $unidadId);
        if ($stmt->execute()) {
            $id = null; $unidad_id = null; $tema_id = null; $tipo = null; $titulo = null; $contenido = null; $url = null; $orden = null; $activo = null;
            $stmt->bind_result($id, $unidad_id, $tema_id, $tipo, $titulo, $contenido, $url, $orden, $activo);
            while ($stmt->fetch()) {
                $out[] = [
                    'id' => $id,
                    'unidad_id' => $unidad_id,
                    'tema_id' => $tema_id,
                    'tipo' => $tipo,
                    'titulo' => $titulo,
                    'contenido' => $contenido,
                    'url' => $url,
                    'orden' => $orden,
                    'activo' => $activo
                ];
            }
        } else {
            error_log("Execute failed in listContentByUnit: {$stmt->error}");
        }
        $stmt->close();
        return $out;
    }

    public function listContentByTopic(int $temaId, bool $onlyActive = true): array {
        $conn = $this->conn;
        $out = [];
        $sql = 'SELECT id, unidad_id, tema_id, tipo, titulo, contenido, url, orden, activo FROM contenido_didactico WHERE tema_id = ?';
        if ($onlyActive) {
            $sql .= ' AND activo = 1';
        }
        $sql .= ' ORDER BY orden ASC, id ASC';

        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            error_log("Prepare failed in listContentByTopic: {$conn->error}");
            return [];
        }

        $stmt->bind_param('i', $temaId);
        if ($stmt->execute()) {
            $id = null; $unidad_id = null; $tema_id = null; $tipo = null; $titulo = null; $contenido = null; $url = null; $orden = null; $activo = null;
            $stmt->bind_result($id, $unidad_id, $tema_id, $tipo, $titulo, $contenido, $url, $orden, $activo);
            while ($stmt->fetch()) {
                $out[] = [
                    'id' => $id,
                    'unidad_id' => $unidad_id,
                    'tema_id' => $tema_id,
                    'tipo' => $tipo,
                    'titulo' => $titulo,
                    'contenido' => $contenido,
                    'url' => $url,
                    'orden' => $orden,
                    'activo' => $activo
                ];
            }
        } else {
            error_log("Execute failed in listContentByTopic: {$stmt->error}");
        }
        $stmt->close();
        return $out;
    }

    public function getContent(int $id): ?array {
        $conn = $this->conn;
        $stmt = $conn->prepare('SELECT id, unidad_id, tema_id, tipo, titulo, contenido, url, orden, activo FROM contenido_didactico WHERE id = ? LIMIT 1');
        if (!$stmt) {
            error_log("Prepare failed in getContent: {$conn->error}");
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
            error_log("Execute failed in getContent: {$stmt->error}");
        }
        $stmt->close();
        return null;
    }

    public function createContent(int $unidadId, string $tipo, string $titulo, ?string $contenido = null, ?string $url = null, int $orden = 0, ?int $temaId = null): bool|int {
        $conn = $this->conn;

        // Validaciones básicas
        if (empty($titulo) || empty($tipo)) {
            error_log("Validation failed: titulo or tipo is empty");
            return false;
        }

        // Validar que la unidad existe
        require_once __DIR__ . '/ModeloUnidad.php';
        $unitModel = new ModeloUnidad($this->db);
        if (!$unitModel->obtenerUnidadPorId($unidadId)) {
            error_log("Validation failed: unidad_id {$unidadId} does not exist");
            return false;
        }

        // Validar que el tema existe si se proporciona
        if ($temaId !== null) {
            require_once __DIR__ . '/ModeloTema.php';
            $topicModel = new ModeloTema($this->db);
            $topic = $topicModel->obtenerTema($temaId);
            if (!$topic || $topic['unidad_id'] != $unidadId) {
                error_log("Validation failed: tema_id {$temaId} does not exist or doesn't belong to unidad_id {$unidadId}");
                return false;
            }
        }

        // Validar tipo
        $tiposValidos = ['texto', 'documento', 'video', 'enlace', 'imagen'];
        if (!in_array($tipo, $tiposValidos)) {
            error_log("Validation failed: invalid tipo '{$tipo}'");
            return false;
        }

        // ==================================================
        // CORRECCIÓN: Validación de URL mejorada
        // ==================================================
        if (in_array($tipo, ['documento', 'video', 'enlace', 'imagen']) && empty($url)) {
            error_log("Validation failed: URL required for tipo '{$tipo}'");
            return false;
        }

        // Validar formato de URL si se proporciona
        if ($url) {
            // Primero, verificar si ya tiene protocolo
            $originalUrl = $url;
            
            if (!preg_match('/^https?:\/\//', $url)) {
                // Agregar https:// si no tiene protocolo
                $url = 'https://' . $url;
                error_log("URL sin protocolo detectada. Corregida a: {$url}");
            }
            
            // Ahora validar con filter_var
            if (!filter_var($url, FILTER_VALIDATE_URL)) {
                error_log("Validation failed: Invalid URL format '{$originalUrl}' (corregida a '{$url}')");
                
                // Para YouTube, intentar un formato más permisivo
                if (strpos($originalUrl, 'youtube.com') !== false || strpos($originalUrl, 'youtu.be') !== false) {
                    error_log("URL de YouTube detectada, permitiendo formato especial");
                    // Para YouTube, aceptamos incluso sin protocolo completo
                    // El frontend ya debería haberlo corregido
                } else {
                    return false;
                }
            }
        }
        // ==================================================

        $stmt = $conn->prepare('INSERT INTO contenido_didactico (unidad_id, tema_id, tipo, titulo, contenido, url, orden) VALUES (?, ?, ?, ?, ?, ?, ?)');
        if (!$stmt) {
            error_log("Prepare failed in createContent: {$conn->error}");
            return false;
        }

        // Usar la URL corregida (con https:// si fue necesario)
        $stmt->bind_param('iissssi', $unidadId, $temaId, $tipo, $titulo, $contenido, $url, $orden);
        $res = $stmt->execute();

        if (!$res) {
            error_log("Execute failed in createContent: {$stmt->error}");
            $stmt->close();
            return false;
        }

        $insertId = $conn->insert_id;
        $stmt->close();
        return $insertId ? (int)$insertId : false;
    }

    public function updateContent(int $id, array $fields): bool {
        $conn = $this->conn;

        // Validar que el contenido existe
        if (!$this->getContent($id)) {
            error_log("Update failed: content ID {$id} does not exist");
            return false;
        }

        $allowed = ['tema_id', 'tipo', 'titulo', 'contenido', 'url', 'orden', 'activo'];
        $sets = [];
        $types = '';
        $values = [];

        foreach ($allowed as $col) {
            if (array_key_exists($col, $fields)) {
                $sets[] = "$col = ?";
                $types .= $this->getParamType($col);
                $values[] = $fields[$col];

                // Validar tipo si se está actualizando
                if ($col === 'tipo') {
                    $tiposValidos = ['texto', 'documento', 'video', 'enlace', 'imagen'];
                    if (!in_array($fields[$col], $tiposValidos)) {
                        error_log("Update failed: invalid tipo '{$fields[$col]}'");
                        return false;
                    }
                }

                // ==================================================
                // CORRECCIÓN: Validación de URL si se está actualizando
                // ==================================================
                if ($col === 'url' && $fields[$col] !== null && $fields[$col] !== '') {
                    $url = $fields[$col];
                    
                    if (!preg_match('/^https?:\/\//', $url)) {
                        // Agregar https:// si no tiene protocolo
                        $fields[$col] = 'https://' . $url;
                        $values[count($values) - 1] = $fields[$col]; // Actualizar el valor en el array
                        error_log("URL sin protocolo detectada en update. Corregida a: {$fields[$col]}");
                    }
                    
                    // Revalidar con filter_var
                    if (!filter_var($fields[$col], FILTER_VALIDATE_URL)) {
                        error_log("Update failed: Invalid URL format '{$url}' (corregida a '{$fields[$col]}')");
                        
                        // Permitir URLs de YouTube incluso si filter_var falla
                        if (strpos($url, 'youtube.com') === false && strpos($url, 'youtu.be') === false) {
                            return false;
                        }
                    }
                }
                // ==================================================

                // Validar tema_id si se está actualizando
                if ($col === 'tema_id') {
                    if ($fields[$col] !== null) {
                        require_once __DIR__ . '/ModeloTema.php';
                        $topicModel = new ModeloTema($this->db);
                        $content = $this->getContent($id);
                        if (!$topicModel->obtenerTema($fields[$col]) || $topicModel->obtenerTema($fields[$col])['unidad_id'] != $content['unidad_id']) {
                            error_log("Update failed: tema_id {$fields[$col]} does not exist or doesn't belong to the same unit");
                            return false;
                        }
                    }
                }
            }
        }

        if (empty($sets)) {
            error_log("Update failed: no valid fields to update");
            return false;
        }

        $sql = 'UPDATE contenido_didactico SET ' . implode(', ', $sets) . ' WHERE id = ? LIMIT 1';
        $types .= 'i';
        $values[] = $id;

        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            error_log("Prepare failed in updateContent: {$conn->error}");
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
            error_log("Execute failed in updateContent: {$stmt->error}");
        }

        $stmt->close();
        return $res;
    }

    public function deleteContent(int $id): bool {
        $conn = $this->conn;

        // Validar que el contenido existe
        if (!$this->getContent($id)) {
            error_log("Delete failed: content ID {$id} does not exist");
            return false;
        }

        $stmt = $conn->prepare('DELETE FROM contenido_didactico WHERE id = ? LIMIT 1');
        if (!$stmt) {
            error_log("Prepare failed in deleteContent: {$conn->error}");
            return false;
        }

        $stmt->bind_param('i', $id);
        $res = $stmt->execute();

        if (!$res) {
            error_log("Execute failed in deleteContent: {$stmt->error}");
        }

        $stmt->close();
        return $res;
    }

    public function reorderContent(int $unidadId, array $contentOrder): bool {
        $conn = $this->conn;
        $conn->begin_transaction();

        try {
            foreach ($contentOrder as $orden => $contentId) {
                $stmt = $conn->prepare('UPDATE contenido_didactico SET orden = ? WHERE id = ? AND unidad_id = ?');
                $stmt->bind_param('iii', $orden, $contentId, $unidadId);
                if (!$stmt->execute()) {
                    throw new Exception("Failed to update order for content ID {$contentId}");
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

    private function getParamType(string $column): string {
        $typeMap = [
            'tema_id' => 'i',
            'tipo' => 's',
            'titulo' => 's',
            'contenido' => 's',
            'url' => 's',
            'orden' => 'i',
            'activo' => 'i'
        ];
        return $typeMap[$column] ?? 's';
    }
}
?>
