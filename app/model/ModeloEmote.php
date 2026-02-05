<?php
class ModeloEmote {
    private $db;
    private $conn;

    public function __construct($db) {
        $this->db = $db;
        $this->conn = (is_object($db) && method_exists($db, 'getConnection')) ? $db->getConnection() : $db;
    }

    /**
     * Asegura que la tabla progreso_usuario tenga el campo reacciones_emote
     */
    private function ensureEmoteSchema(): void {
        try {
            // Verificar si la columna reacciones_emote existe
            $result = $this->conn->query("SHOW COLUMNS FROM progreso_usuario LIKE 'reacciones_emote'");
            if ($result && $result->num_rows === 0) {
                // Crear la columna si no existe
                $this->conn->query("ALTER TABLE progreso_usuario ADD COLUMN reacciones_emote JSON DEFAULT NULL");
            }
        } catch (Exception $e) {
            error_log('Error en ensureEmoteSchema: ' . $e->getMessage());
        }
    }

    /**
     * Guarda o actualiza reacciones de emotes para un ejercicio específico
     */
    public function saveEmoteReactions(int $userId, string $unit, array $reactions): bool {
        $this->ensureEmoteSchema();

        // Convertir el array de reacciones a JSON
        $reactionsJson = json_encode($reactions);

        // Encontrar el ejercicio_id basado en el unit (slug de unidad) y asumiendo que es el primer ejercicio de práctica
        $stmt = $this->conn->prepare("SELECT e.id FROM ejercicios e JOIN unidades u ON e.unidad_id = u.id WHERE u.slug = ? AND e.tipo = 'practica' ORDER BY e.orden ASC LIMIT 1");
        $stmt->bind_param("s", $unit);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            return false; // No se encontró el ejercicio
        }

        $row = $result->fetch_assoc();
        $ejercicioId = $row['id'];
        $stmt->close();

        // Verificar si ya existe un registro para este usuario y ejercicio
        $stmt = $this->conn->prepare("SELECT id FROM progreso_usuario WHERE usuario_id = ? AND ejercicio_id = ? LIMIT 1");
        $stmt->bind_param("ii", $userId, $ejercicioId);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            // Actualizar registro existente
            $stmt = $this->conn->prepare("UPDATE progreso_usuario SET reacciones_emote = ? WHERE usuario_id = ? AND ejercicio_id = ?");
            $stmt->bind_param("sii", $reactionsJson, $userId, $ejercicioId);
        } else {
            // Crear nuevo registro
            $stmt = $this->conn->prepare("INSERT INTO progreso_usuario (usuario_id, ejercicio_id, reacciones_emote) VALUES (?, ?, ?)");
            $stmt->bind_param("iis", $userId, $ejercicioId, $reactionsJson);
        }

        $success = $stmt->execute();
        $stmt->close();
        return $success;
    }

    /**
     * Obtiene las reacciones de emotes para una unidad específica
     */
    public function getEmoteReactions(int $userId, string $unit): array {
        $this->ensureEmoteSchema();

        // Encontrar el ejercicio_id basado en el unit (slug de unidad)
        $stmt = $this->conn->prepare("SELECT e.id FROM ejercicios e JOIN unidades u ON e.unidad_id = u.id WHERE u.slug = ? AND e.tipo = 'practica' ORDER BY e.orden ASC LIMIT 1");
        $stmt->bind_param("s", $unit);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            return []; // No se encontró el ejercicio
        }

        $row = $result->fetch_assoc();
        $ejercicioId = $row['id'];
        $stmt->close();

        $stmt = $this->conn->prepare("SELECT reacciones_emote FROM progreso_usuario WHERE usuario_id = ? AND ejercicio_id = ? LIMIT 1");
        $stmt->bind_param("ii", $userId, $ejercicioId);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $reactionsJson = $row['reacciones_emote'];

            if ($reactionsJson) {
                $reactions = json_decode($reactionsJson, true);
                return is_array($reactions) ? $reactions : [];
            }
        }

        return [];
    }

    /**
     * Obtiene todas las reacciones de emotes para todas las unidades de un usuario
     */
    public function getAllEmoteReactions(int $userId): array {
        $this->ensureEmoteSchema();

        $stmt = $this->conn->prepare("SELECT u.slug as unit, p.reacciones_emote FROM progreso_usuario p JOIN ejercicios e ON p.ejercicio_id = e.id JOIN unidades u ON e.unidad_id = u.id WHERE p.usuario_id = ? AND p.reacciones_emote IS NOT NULL");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();

        $allReactions = [];
        while ($row = $result->fetch_assoc()) {
            $reactions = json_decode($row['reacciones_emote'], true);
            if (is_array($reactions)) {
                $allReactions[$row['unit']] = $reactions;
            }
        }

        $stmt->close();
        return $allReactions;
    }

    /**
     * Elimina todas las reacciones de emotes para una unidad específica
     */
    public function clearEmoteReactions(int $userId, string $unit): bool {
        $this->ensureEmoteSchema();

        // Encontrar el ejercicio_id basado en el unit (slug de unidad)
        $stmt = $this->conn->prepare("SELECT e.id FROM ejercicios e JOIN unidades u ON e.unidad_id = u.id WHERE u.slug = ? AND e.tipo = 'practica' ORDER BY e.orden ASC LIMIT 1");
        $stmt->bind_param("s", $unit);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            return false; // No se encontró el ejercicio
        }

        $row = $result->fetch_assoc();
        $ejercicioId = $row['id'];
        $stmt->close();

        $stmt = $this->conn->prepare("UPDATE progreso_usuario SET reacciones_emote = NULL WHERE usuario_id = ? AND ejercicio_id = ?");
        $stmt->bind_param("ii", $userId, $ejercicioId);
        $success = $stmt->execute();
        $stmt->close();
        return $success;
    }
}
