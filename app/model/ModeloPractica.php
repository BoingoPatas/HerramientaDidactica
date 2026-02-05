<?php
require_once __DIR__ . '/../config/BaseDatos.php';

class PracticeModel {
    private $db;
    private $conn;

    public function __construct($db) {
        $this->db = $db;
        $this->conn = (is_object($db) && method_exists($db, 'getConnection')) ? $db->getConnection() : $db;
        // No longer creates tables - assumes normalized schema exists
    }

    public function listPracticeUnits(?int $userId = null, ?string $role = null, ?string $seccion = null): array {
        $conn = $this->conn;
        $out = [];
        
        $sql = 'SELECT id, slug, titulo, descripcion, icono, orden, activo, docente_id FROM unidades WHERE activo=1 AND (tipo="practica" OR tipo IS NULL)';
        $where = [];
        $params = [];
        $types = '';

        if ($role === 'Docente' && $userId) {
            // Docentes solo ven sus propias unidades
            $where[] = 'docente_id = ?';
            $params[] = $userId;
            $types .= 'i';
        } elseif ($role === 'Usuario' && $seccion) {
            // Estudiantes ven unidades de docentes que comparten su sección
            // O unidades sin docente asignado (globales)
            $where[] = '(docente_id IS NULL OR docente_id IN (SELECT id FROM usuarios WHERE rol = "Docente" AND (seccion = ? OR FIND_IN_SET(?, REPLACE(seccion, " ", "")))))';
            $params[] = $seccion;
            $params[] = $seccion;
            $types .= 'ss';
        }
        // Administradores ven todo (no se añade WHERE)

        if (!empty($where)) {
            $sql .= ' AND ' . implode(' AND ', $where);
        }

        $sql .= ' ORDER BY orden ASC, id DESC';

        if (!empty($params)) {
            $stmt = $conn->prepare($sql);
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            $res = $stmt->get_result();
        } else {
            $res = $conn->query($sql);
        }

        if ($res === false) {
            error_log("Database error in listPracticeUnits: " . $conn->error);
            return [];
        }

        while ($r = $res->fetch_assoc()) $out[] = $r;
        if ($res instanceof mysqli_result) $res->free();
        return $out;
    }

    public function getPracticeUnit(string $slug): ?array {
        $conn = $this->conn;
        if ($stmt = $conn->prepare('SELECT id, slug, titulo, descripcion, icono, orden, activo FROM unidades WHERE slug = ? AND activo=1 AND (tipo="practica" OR tipo IS NULL) LIMIT 1')) {
            $stmt->bind_param('s', $slug);
            if ($stmt->execute()) {
                $id_out = null; $slug_out = null; $titulo_out = null; $descripcion_out = null; $icono_out = null; $orden_out = null; $activo_out = null;
                $stmt->bind_result($id_out, $slug_out, $titulo_out, $descripcion_out, $icono_out, $orden_out, $activo_out);
                if ($stmt->fetch()) {
                    $stmt->close();
                    return [
                        'id' => $id_out,
                        'slug' => $slug_out,
                        'title' => $titulo_out, // Keep 'title' for compatibility
                        'description' => $descripcion_out,
                        'icon' => $icono_out,
                        'orden' => $orden_out,
                        'activo' => $activo_out
                    ];
                }
            }
            $stmt->close();
        }
        return null;
    }

    public function listExercisesByUnit(string $unit_slug): array {
        $conn = $this->conn;
        $out = [];

        // Map practice unit slugs to their corresponding evaluation unit slugs
        $practiceToEvalMapping = [
            '17' => 'variables',
            '17-1' => 'operadores',
            '123123-2' => 'condicionales',
            'verificationkey' => 'bucles',
            'unidad5prueba' => 'bucles' // Map test unit to bucles to avoid duplicates
        ];

        // Use the mapped evaluation unit slug if available, otherwise use the original slug
        $target_unit_slug = $practiceToEvalMapping[$unit_slug] ?? $unit_slug;

        // Consulta actualizada para incluir tema_id y activo
        if ($stmt = $conn->prepare('SELECT e.id, e.slug, e.titulo, e.orden, e.instrucciones, e.ejemplo, e.salida_esperada, e.solucion, e.tema_id, e.activo FROM ejercicios e JOIN unidades u ON e.unidad_id = u.id WHERE u.slug = ? AND e.tipo = "practica" ORDER BY e.orden ASC, e.id DESC')) {
            $stmt->bind_param('s', $target_unit_slug);
            if ($stmt->execute()) {
                $id_out = $slug_out = $titulo_out = $orden_out = $instrucciones_out = $ejemplo_out = $salida_esperada_out = $solucion_out = $tema_id_out = $activo_out = null;
                $stmt->bind_result($id_out, $slug_out, $titulo_out, $orden_out, $instrucciones_out, $ejemplo_out, $salida_esperada_out, $solucion_out, $tema_id_out, $activo_out);
                while ($stmt->fetch()) {
                    $out[] = [
                        'id' => $id_out,
                        'slug' => $slug_out,
                        'title' => $titulo_out, // Keep 'title' for compatibility
                        'orden' => $orden_out,
                        'instructions' => $instrucciones_out,
                        'example' => $ejemplo_out,
                        'expected_output' => $salida_esperada_out,
                        'solution' => $solucion_out,
                        'tema_id' => $tema_id_out,
                        'activo' => $activo_out
                    ];
                }
                $stmt->close();
            }
        }
        return $out;
    }

    public function getExercise(string $unit_slug, string $exercise_slug): ?array {
        $conn = $this->conn;

        // Map practice unit slugs to their corresponding evaluation unit slugs
        $practiceToEvalMapping = [
            '17' => 'variables',
            '17-1' => 'operadores',
            '123123-2' => 'condicionales',
            'verificationkey' => 'bucles',
            'unidad5prueba' => 'bucles' // Map test unit to bucles to avoid duplicates
        ];

        // Use the mapped evaluation unit slug if available, otherwise use the original slug
        $target_unit_slug = $practiceToEvalMapping[$unit_slug] ?? $unit_slug;

        if ($stmt = $conn->prepare('SELECT e.id, e.slug, e.titulo, e.orden, e.instrucciones, e.ejemplo, e.salida_esperada, e.solucion FROM ejercicios e JOIN unidades u ON e.unidad_id = u.id WHERE u.slug = ? AND e.slug = ? AND e.tipo = "practica" LIMIT 1')) {
            $stmt->bind_param('ss', $target_unit_slug, $exercise_slug);
            if ($stmt->execute()) {
                $id_out = null; $slug_out = null; $titulo_out = null; $orden_out = null; $instrucciones_out = null; $ejemplo_out = null; $salida_esperada_out = null; $solucion_out = null;
                $stmt->bind_result($id_out, $slug_out, $titulo_out, $orden_out, $instrucciones_out, $ejemplo_out, $salida_esperada_out, $solucion_out);
                if ($stmt->fetch()) {
                    $stmt->close();
                    return [
                        'id' => $id_out,
                        'slug' => $slug_out,
                        'title' => $titulo_out, // Keep 'title' for compatibility
                        'orden' => $orden_out,
                        'instructions' => $instrucciones_out,
                        'example' => $ejemplo_out,
                        'expected_output' => $salida_esperada_out,
                        'solution' => $solucion_out
                    ];
                }
            }
            $stmt->close();
        }
        return null;
    }

    public function createPracticeUnit(string $slug, string $title, string $description = '', string $icon = '', int $orden = 0): int|false {
        $conn = $this->conn;

        // Validaciones básicas
        if (empty($slug) || empty($title)) {
            error_log("Validation failed: slug or title is empty");
            return false;
        }

        if ($this->practiceUnitSlugExists($slug)) {
            error_log("Validation failed: slug already exists - {$slug}");
            return false;
        }

        $stmt = $conn->prepare('INSERT INTO unidades (slug, titulo, descripcion, icono, orden, tipo, activo) VALUES (?, ?, ?, ?, ?, "practica", 1)');
        if (!$stmt) {
            error_log("Prepare failed in createPracticeUnit: {$conn->error}");
            return false;
        }

        $stmt->bind_param('ssssi', $slug, $title, $description, $icon, $orden);
        $res = $stmt->execute();

        if (!$res) {
            error_log("Execute failed in createPracticeUnit: {$stmt->error}");
            $stmt->close();
            return false;
        }

        $insertId = $conn->insert_id;
        $stmt->close();
        return $insertId ? (int)$insertId : false;
    }

    public function practiceUnitSlugExists(string $slug, ?int $excludeId = null): bool {
        $conn = $this->conn;
        $sql = 'SELECT id FROM unidades WHERE slug = ? AND tipo = "practica"';
        $types = 's';

        if ($excludeId !== null) {
            $sql .= ' AND id != ?';
            $types .= 'i';
        }

        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            error_log("Prepare failed in practiceUnitSlugExists: {$conn->error}");
            return false;
        }

        if ($excludeId !== null) {
            $stmt->bind_param($types, $slug, $excludeId);
        } else {
            $stmt->bind_param($types, $slug);
        }

        $stmt->execute();
        $id_out = null;
        $stmt->bind_result($id_out);
        $found = $stmt->fetch();
        $stmt->close();
        return (bool)$found && !empty($id_out);
    }

    public function updateExercise(string $unit_slug, string $exercise_slug, array $fields): bool {
        $conn = $this->conn;
        $sets = [];
        $types = '';
        $values = [];

        // Map old field names to new ones
        $fieldMapping = [
            'title' => 'titulo',
            'instructions' => 'instrucciones',
            'example' => 'ejemplo',
            'expected_output' => 'salida_esperada',
            'solution' => 'solucion',
            'rubric' => 'rubrica',
            'expected_code' => 'solucion'
        ];

        foreach (array_intersect_key($fields, $fieldMapping) as $oldCol => $val) {
            $newCol = $fieldMapping[$oldCol];
            $sets[] = "$newCol = ?";
            $types .= 's';
            $values[] = $val;
        }
        if (empty($sets)) return true; // No hay nada que actualizar

        $sql = 'UPDATE ejercicios e JOIN unidades u ON e.unidad_id = u.id SET ' . implode(', ', $sets) . ' WHERE u.slug = ? AND e.slug = ? AND e.activo=1';
        $types .= 'ss';
        $values[] = $unit_slug;
        $values[] = $exercise_slug;
        $stmt = $conn->prepare($sql);
        if (!$stmt) return false;
        $refs = [];
        $refs[] = &$types;
        foreach ($values as $k => $v) $refs[] = &$values[$k];
        call_user_func_array([$stmt, 'bind_param'], $refs);
        $res = $stmt->execute();
        $stmt->close();
        return (bool)$res;
    }

    public function getCompletedExercisesForUserInUnit(int $userId, string $unit_slug): array {
        $conn = $this->conn;
        $out = [];
        if ($stmt = $conn->prepare('SELECT e.id FROM progreso_usuario p JOIN ejercicios e ON p.ejercicio_id = e.id JOIN unidades u ON e.unidad_id = u.id WHERE p.usuario_id = ? AND u.slug = ? AND p.completado = 1')) {
            $stmt->bind_param('is', $userId, $unit_slug);
            if ($stmt->execute()) {
                $id_out = null;
                $stmt->bind_result($id_out);
                while ($stmt->fetch()) {
                    $out[] = $id_out;
                }
                $stmt->close();
            }
        }
        return $out;
    }

    /**
     * Obtiene temas con sus ejercicios para una unidad de práctica
     *
     * @param string $unit_slug Slug de la unidad
     * @return array Array de temas con sus ejercicios
     */
    public function getTopicsWithExercisesForUnit(string $unit_slug): array {
        $conn = $this->conn;
        $result = [];

        // Obtener ID de la unidad
        $unitId = null;
        if ($stmt = $conn->prepare('SELECT id FROM unidades WHERE slug = ? LIMIT 1')) {
            $stmt->bind_param('s', $unit_slug);
            if ($stmt->execute()) {
                $stmt->bind_result($unitId);
                if ($stmt->fetch()) {
                    // $unitId ya se asigna directamente
                }
                $stmt->close();
            }
        }

        if (!$unitId) {
            return [];
        }

        // Obtener temas de la unidad
        $topics = [];
        if ($stmt = $conn->prepare('SELECT id, nombre, descripcion FROM temas WHERE unidad_id = ? ORDER BY orden ASC')) {
            $stmt->bind_param('i', $unitId);
            if ($stmt->execute()) {
                $result_topics = $stmt->get_result();
                while ($row = $result_topics->fetch_assoc()) {
                    $topics[] = $row;
                }
                $stmt->close();
            }
        }

        // Para cada tema, obtener sus ejercicios
        foreach ($topics as $topic) {
            $exercises = [];
            if ($stmt = $conn->prepare('SELECT id, slug, titulo, orden FROM ejercicios WHERE tema_id = ? AND tipo = "practica" ORDER BY orden ASC')) {
                $stmt->bind_param('i', $topic['id']);
                if ($stmt->execute()) {
                    $result_exercises = $stmt->get_result();
                    while ($row = $result_exercises->fetch_assoc()) {
                        $exercises[] = $row;
                    }
                    $stmt->close();
                }
            }

            $result[] = [
                'topic' => $topic,
                'exercises' => $exercises
            ];
        }

        return $result;
    }

    /**
     * Crea un ejercicio asociado a un tema
     *
     * @param string $unit_slug Slug de la unidad
     * @param int $topic_id ID del tema
     * @param string $slug Slug del ejercicio (opcional, se genera automáticamente desde el título)
     * @param array $data Datos del ejercicio
     * @return bool True si éxito, false si falla
     */
    public function createExerciseForTopic(string $unit_slug, int $topic_id, string $slug, array $data): bool {
        $conn = $this->conn;

        // Obtener ID de la unidad con validación más estricta
        $unitId = null;
        if ($stmt = $conn->prepare('SELECT id FROM unidades WHERE slug = ? AND tipo = "practica" AND activo = 1 LIMIT 1')) {
            $stmt->bind_param('s', $unit_slug);
            if ($stmt->execute()) {
                $stmt->bind_result($unitId);
                if ($stmt->fetch()) {
                    // $unitId ya se asigna directamente
                }
                $stmt->close();
            }
        }

        if (!$unitId) {
            error_log("Unit not found or inactive: {$unit_slug}");
            return false;
        }

        // Obtener datos del ejercicio con validaciones más estrictas
        $title = trim($data['title'] ?? '');
        $orden = (int)($data['orden'] ?? 0);
        $instructions = trim($data['instructions'] ?? '');
        $example = trim($data['example'] ?? '');
        $expected_output = trim($data['expected_output'] ?? '');
        $solution = trim($data['solution'] ?? '');

        // Validaciones estrictas de campos obligatorios
        if (empty($title)) {
            error_log("Validation failed: title is empty");
            return false;
        }

        if (empty($instructions)) {
            error_log("Validation failed: instructions are empty");
            return false;
        }

        if (empty($example)) {
            error_log("Validation failed: example is empty");
            return false;
        }

        // Si no se proporciona slug o está vacío, generar uno automáticamente desde el título
        if (empty($slug) && !empty($title)) {
            $slug = $this->generateSlugFromTitle($title);
        }

        // Validar que el slug no esté vacío
        if (empty($slug)) {
            error_log("Validation failed: slug is empty after generation");
            return false;
        }

        // Validar formato del slug
        if (!$this->validateSlugFormat($slug)) {
            error_log("Validation failed: slug format is invalid - {$slug}");
            return false;
        }

        // Verificar que el slug sea único en la unidad
        if ($this->exerciseSlugExistsInUnit($unitId, $slug)) {
            error_log("Validation failed: slug already exists in unit - {$slug}");
            return false;
        }

        // Verificar que el tema exista y pertenezca a la unidad
        if (!$this->validateTopicBelongsToUnit($topic_id, $unitId)) {
            error_log("Validation failed: topic {$topic_id} does not belong to unit {$unitId}");
            return false;
        }

        // Insertar ejercicio con validaciones adicionales
        $stmt = $conn->prepare('INSERT INTO ejercicios (unidad_id, tema_id, slug, titulo, tipo, orden, instrucciones, ejemplo, salida_esperada, solucion, activo) VALUES (?, ?, ?, ?, "practica", ?, ?, ?, ?, ?, 1)');
        if (!$stmt) {
            error_log("Prepare failed in createExerciseForTopic: {$conn->error}");
            return false;
        }

        $stmt->bind_param('iisssssss', $unitId, $topic_id, $slug, $title, $orden, $instructions, $example, $expected_output, $solution);
        $res = $stmt->execute();
        
        if (!$res) {
            error_log("Execute failed in createExerciseForTopic: {$stmt->error}");
        }
        
        $stmt->close();
        return (bool)$res;
    }

    /**
     * Valida que un tema pertenezca a una unidad específica
     *
     * @param int $topic_id ID del tema
     * @param int $unit_id ID de la unidad
     * @return bool True si el tema pertenece a la unidad, false en caso contrario
     */
    private function validateTopicBelongsToUnit(int $topic_id, int $unit_id): bool {
        $conn = $this->conn;
        $stmt = $conn->prepare('SELECT id FROM temas WHERE id = ? AND unidad_id = ? LIMIT 1');
        if (!$stmt) {
            return false;
        }

        $stmt->bind_param('ii', $topic_id, $unit_id);
        $stmt->execute();
        $id_out = null;
        $stmt->bind_result($id_out);
        $found = $stmt->fetch();
        $stmt->close();
        return (bool)$found && !empty($id_out);
    }

    /**
     * Genera un slug a partir de un título
     *
     * @param string $title Título del ejercicio
     * @return string Slug generado
     */
    private function generateSlugFromTitle(string $title): string {
        if (empty($title)) {
            return '';
        }

        // Convertir a minúsculas
        $slug = strtolower(trim($title));
        
        // Reemplazar espacios y caracteres especiales con guiones
        $slug = preg_replace('/[^a-z0-9\s-]/', '', $slug);
        $slug = preg_replace('/\s+/', '-', $slug);
        $slug = preg_replace('/-+/', '-', $slug);
        $slug = trim($slug, '-');

        return $slug;
    }

    /**
     * Valida el formato de un slug
     *
     * @param string $slug Slug a validar
     * @return bool True si el formato es válido, false en caso contrario
     */
    private function validateSlugFormat(string $slug): bool {
        return preg_match('/^[a-z0-9\-]+$/', $slug) === 1;
    }

    /**
     * Verifica si un slug ya existe en una unidad específica
     *
     * @param int $unitId ID de la unidad
     * @param string $slug Slug a verificar
     * @return bool True si el slug ya existe, false en caso contrario
     */
    private function exerciseSlugExistsInUnit(int $unitId, string $slug): bool {
        $conn = $this->conn;
        $stmt = $conn->prepare('SELECT id FROM ejercicios WHERE unidad_id = ? AND slug = ? AND tipo = "practica" LIMIT 1');
        if (!$stmt) {
            return false;
        }

        $stmt->bind_param('is', $unitId, $slug);
        $stmt->execute();
        $id_out = null;
        $stmt->bind_result($id_out);
        $found = $stmt->fetch();
        $stmt->close();
        return (bool)$found && !empty($id_out);
    }

    /**
     * Actualiza un ejercicio para asociarlo a un tema
     *
     * @param string $unit_slug Slug de la unidad
     * @param string $exercise_slug Slug del ejercicio
     * @param int $topic_id ID del tema
     * @return bool True si éxito, false si falla
     */
    public function updateExerciseTopic(string $unit_slug, string $exercise_slug, int $topic_id): bool {
        $conn = $this->conn;

        $stmt = $conn->prepare('UPDATE ejercicios e JOIN unidades u ON e.unidad_id = u.id SET e.tema_id = ? WHERE u.slug = ? AND e.slug = ? AND e.tipo = "practica"');
        if (!$stmt) {
            return false;
        }

        $stmt->bind_param('iss', $topic_id, $unit_slug, $exercise_slug);
        $res = $stmt->execute();
        $stmt->close();
        return (bool)$res;
    }

    /**
     * Obtiene ejercicios asociados a un tema específico
     *
     * @param int $topic_id ID del tema
     * @param string|null $type Tipo de ejercicio a filtrar (practica o evaluacion), null para todos
     * @return array Array de ejercicios
     */
    public function getExercisesByTopic(int $topic_id, ?string $type = null): array {
        $conn = $this->conn;
        $exercises = [];

        error_log("DEBUG: ModeloPractica getExercisesByTopic - buscando tema_id: {$topic_id}, type: {$type}");
        
        // Primero verificar si el tema existe
        $stmt_check = $conn->prepare('SELECT id, nombre FROM temas WHERE id = ? LIMIT 1');
        if ($stmt_check) {
            $stmt_check->bind_param('i', $topic_id);
            if ($stmt_check->execute()) {
                $result_check = $stmt_check->get_result();
                if ($result_check->num_rows > 0) {
                    $topic_data = $result_check->fetch_assoc();
                    error_log("DEBUG: ModeloPractica - Tema encontrado: id={$topic_data['id']}, nombre={$topic_data['nombre']}");
                } else {
                    error_log("DEBUG: ModeloPractica - Tema NO encontrado con id: {$topic_id}");
                }
            }
            $stmt_check->close();
        }

        // Consulta principal para obtener ejercicios
        if ($type === 'practica' || $type === 'evaluacion') {
            // Filtrar por tipo específico
            $sql = 'SELECT id, slug, titulo, orden, tipo, activo, tema_id FROM ejercicios WHERE tema_id = ? AND tipo = ? ORDER BY orden ASC';
            $stmt = $conn->prepare($sql);
            if ($stmt) {
                $stmt->bind_param('is', $topic_id, $type);
                error_log("DEBUG: ModeloPractica - Filtrando por tipo: {$type}");
            } else {
                error_log("DEBUG: ModeloPractica - Error en prepare con tipo: {$conn->error}");
                return [];
            }
        } else {
            // Obtener todos los ejercicios (practica y evaluacion)
            $sql = 'SELECT id, slug, titulo, orden, tipo, activo, tema_id FROM ejercicios WHERE tema_id = ? AND (tipo = "practica" OR tipo = "evaluacion") ORDER BY orden ASC';
            $stmt = $conn->prepare($sql);
            if ($stmt) {
                $stmt->bind_param('i', $topic_id);
                error_log("DEBUG: ModeloPractica - Sin filtro de tipo");
            } else {
                error_log("DEBUG: ModeloPractica - Error en prepare sin tipo: {$conn->error}");
                return [];
            }
        }

        if ($stmt && $stmt->execute()) {
            $result = $stmt->get_result();
            $count = 0;
            while ($row = $result->fetch_assoc()) {
                $exercises[] = $row;
                $count++;
                error_log("DEBUG: ModeloPractica - Ejercicio encontrado: id={$row['id']}, slug={$row['slug']}, tipo={$row['tipo']}, activo={$row['activo']}");
            }
            error_log("DEBUG: ModeloPractica - Total ejercicios encontrados: {$count}");
            $stmt->close();
        } else {
            error_log("DEBUG: ModeloPractica - Error en execute: " . ($stmt ? $stmt->error : 'stmt is null'));
        }

        return $exercises;
    }

    /**
     * Obtiene un ejercicio por su ID
     *
     * @param int $exercise_id ID del ejercicio
     * @return array|null Datos del ejercicio o null si no se encuentra
     */
    public function getExerciseById(int $exercise_id): ?array {
        $conn = $this->conn;

        if ($stmt = $conn->prepare('SELECT id, slug, titulo, orden, instrucciones, ejemplo, salida_esperada, solucion, rubrica, tema_id, tipo, activo FROM ejercicios WHERE id = ? LIMIT 1')) {
            $stmt->bind_param('i', $exercise_id);
            if ($stmt->execute()) {
                $id_out = null; $slug_out = null; $titulo_out = null; $orden_out = null; $instrucciones_out = null; $ejemplo_out = null; $salida_esperada_out = null; $solucion_out = null; $rubrica_out = null; $tema_id_out = null; $tipo_out = null; $activo_out = null;
                $stmt->bind_result($id_out, $slug_out, $titulo_out, $orden_out, $instrucciones_out, $ejemplo_out, $salida_esperada_out, $solucion_out, $rubrica_out, $tema_id_out, $tipo_out, $activo_out);
                if ($stmt->fetch()) {
                    $stmt->close();
                    return [
                        'id' => $id_out,
                        'slug' => $slug_out,
                        'titulo' => $titulo_out,
                        'orden' => $orden_out,
                        'instrucciones' => $instrucciones_out,
                        'ejemplo' => $ejemplo_out,
                        'salida_esperada' => $salida_esperada_out,
                        'solucion' => $solucion_out,
                        'rubrica' => $rubrica_out,
                        'tema_id' => $tema_id_out,
                        'tipo' => $tipo_out,
                        'activo' => $activo_out
                    ];
                }
            }
            $stmt->close();
        }
        return null;
    }

    /**
     * Crea una evaluación asociada a un tema
     *
     * @param string $unit_slug Slug de la unidad
     * @param int $topic_id ID del tema
     * @param string $slug Slug de la evaluación (opcional, se genera automáticamente desde el título)
     * @param array $data Datos de la evaluación
     * @return bool True si éxito, false si falla
     */
    public function createEvaluationForTopic(string $unit_slug, int $topic_id, string $slug, array $data): bool {
        $conn = $this->conn;

        // Depuración: Registrar parámetros de entrada
        error_log("DEBUG: ModeloPractica createEvaluationForTopic - unit_slug: $unit_slug, topic_id: $topic_id, slug: $slug");
        error_log("DEBUG: ModeloPractica createEvaluationForTopic - data: " . print_r($data, true));

        // Obtener ID de la unidad con validación más estricta
        $unitId = null;
        if ($stmt = $conn->prepare('SELECT id FROM unidades WHERE slug = ? AND tipo = "practica" AND activo = 1 LIMIT 1')) {
            $stmt->bind_param('s', $unit_slug);
            if ($stmt->execute()) {
                $stmt->bind_result($unitId);
                if ($stmt->fetch()) {
                    // $unitId ya se asigna directamente
                }
                $stmt->close();
            }
        }

        if (!$unitId) {
            error_log("Unit not found or inactive: {$unit_slug}");
            return false;
        }

        // Depuración: ID de unidad encontrado
        error_log("DEBUG: ModeloPractica - unitId encontrado: $unitId");

        // Obtener datos de la evaluación con validaciones más estrictas
        $title = trim($data['title'] ?? '');
        $orden = (int)($data['orden'] ?? 0);
        $instructions = trim($data['instructions'] ?? '');
        $example = trim($data['example'] ?? '');
        $rubric = trim($data['rubric'] ?? '');
        $expectedCode = trim($data['expected_code'] ?? '');

        // Depuración: Valores procesados
        error_log("DEBUG: ModeloPractica - title: $title, orden: $orden, instructions: " . substr($instructions, 0, 50) . "...");
        error_log("DEBUG: ModeloPractica - example: " . substr($example, 0, 50) . "..., rubric length: " . strlen($rubric));
        error_log("DEBUG: ModeloPractica - expectedCode length: " . strlen($expectedCode));

        // Validaciones estrictas de campos obligatorios
        if (empty($title)) {
            error_log("Validation failed: title is empty");
            return false;
        }

        if (empty($instructions)) {
            error_log("Validation failed: instructions are empty");
            return false;
        }

        if (empty($example)) {
            error_log("Validation failed: example is empty");
            return false;
        }

        // Validar que el código esperado no esté vacío
        if (empty($expectedCode)) {
            error_log("Validation failed: expected_code is empty");
            return false;
        }

        // Validar que la rúbrica sea un JSON válido si se proporciona
        if (!empty($rubric)) {
            if (!is_string($rubric)) {
                error_log("Validation failed: rubric must be a string");
                return false;
            }
            
            // Intentar decodificar el JSON para validar que sea válido
            $decoded = json_decode($rubric, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                error_log("Validation failed: rubric is not valid JSON - " . json_last_error_msg());
                error_log("Rubric content: " . substr($rubric, 0, 200));
                return false;
            }
            
            // Validar que la rúbrica tenga el formato esperado
            if (!is_array($decoded)) {
                error_log("Validation failed: rubric must be a JSON array");
                return false;
            }
        }

        // Si no se proporciona slug o está vacío, generar uno automáticamente desde el título
        if (empty($slug) && !empty($title)) {
            $slug = $this->generateSlugFromTitle($title);
        }

        // Validar que el slug no esté vacío
        if (empty($slug)) {
            error_log("Validation failed: slug is empty after generation");
            return false;
        }

        // Validar formato del slug
        if (!$this->validateSlugFormat($slug)) {
            error_log("Validation failed: slug format is invalid - {$slug}");
            return false;
        }

        // Verificar que el slug sea único en la unidad
        if ($this->exerciseSlugExistsInUnit($unitId, $slug)) {
            error_log("Validation failed: slug already exists in unit - {$slug}");
            return false;
        }

        // Verificar que el tema exista y pertenezca a la unidad
        if (!$this->validateTopicBelongsToUnit($topic_id, $unitId)) {
            error_log("Validation failed: topic {$topic_id} does not belong to unit {$unitId}");
            return false;
        }

        // Depuración: Todas las validaciones pasaron, preparando inserción
        error_log("DEBUG: ModeloPractica - Todas las validaciones pasaron, preparando inserción");

        // Insertar evaluación con validaciones adicionales
        $stmt = $conn->prepare('INSERT INTO ejercicios (unidad_id, tema_id, slug, titulo, tipo, orden, instrucciones, ejemplo, rubrica, solucion, activo) VALUES (?, ?, ?, ?, "evaluacion", ?, ?, ?, ?, ?, 1)');
        if (!$stmt) {
            error_log("Prepare failed in createEvaluationForTopic: {$conn->error}");
            return false;
        }

        // Depuración: Parámetros para bind_param
        error_log("DEBUG: ModeloPractica - bind_param params: unitId=$unitId, topic_id=$topic_id, slug=$slug, title=$title, orden=$orden");
        error_log("DEBUG: ModeloPractica - bind_param params: instructions=" . substr($instructions, 0, 30) . "...");
        error_log("DEBUG: ModeloPractica - bind_param params: example=" . substr($example, 0, 30) . "...");
        error_log("DEBUG: ModeloPractica - bind_param params: rubric length=" . strlen($rubric));
        error_log("DEBUG: ModeloPractica - bind_param params: expectedCode length=" . strlen($expectedCode));

        $stmt->bind_param('iisssssss', $unitId, $topic_id, $slug, $title, $orden, $instructions, $example, $rubric, $expectedCode);
        $res = $stmt->execute();
        
        if (!$res) {
            error_log("Execute failed in createEvaluationForTopic: {$stmt->error}");
            error_log("DEBUG: ModeloPractica - SQL error details: " . $stmt->error);
        } else {
            error_log("DEBUG: ModeloPractica - Inserción exitosa, affected_rows: " . $stmt->affected_rows);
        }
        
        $stmt->close();
        return (bool)$res;
    }

    /**
     * Habilita o inhabilita un ejercicio
     *
     * @param string $unit_slug Slug de la unidad
     * @param string $exercise_slug Slug del ejercicio
     * @param bool $active True para habilitar, false para inhabilitar
     * @return bool True si éxito, false si falla
     */
    public function setExerciseActive(string $unit_slug, string $exercise_slug, bool $active): bool {
        $conn = $this->conn;

        $stmt = $conn->prepare('UPDATE ejercicios e JOIN unidades u ON e.unidad_id = u.id SET e.activo = ? WHERE u.slug = ? AND e.slug = ? AND e.tipo = "practica"');
        if (!$stmt) {
            return false;
        }

        $activeInt = $active ? 1 : 0;
        $stmt->bind_param('iss', $activeInt, $unit_slug, $exercise_slug);
        $res = $stmt->execute();
        $stmt->close();
        return (bool)$res;
    }

    /**
     * Habilita o inhabilita una evaluación
     *
     * @param string $unit_slug Slug de la unidad
     * @param string $exercise_slug Slug de la evaluación
     * @param bool $active True para habilitar, false para inhabilitar
     * @return bool True si éxito, false si falla
     */
    public function setEvaluationActive(string $unit_slug, string $exercise_slug, bool $active): bool {
        $conn = $this->conn;

        $stmt = $conn->prepare('UPDATE ejercicios e JOIN unidades u ON e.unidad_id = u.id SET e.activo = ? WHERE u.slug = ? AND e.slug = ? AND e.tipo = "evaluacion"');
        if (!$stmt) {
            return false;
        }

        $activeInt = $active ? 1 : 0;
        $stmt->bind_param('iss', $activeInt, $unit_slug, $exercise_slug);
        $res = $stmt->execute();
        $stmt->close();
        return (bool)$res;
    }

    /**
     * Elimina un ejercicio de forma permanente
     *
     * @param string $unit_slug Slug de la unidad
     * @param string $exercise_slug Slug del ejercicio
     * @return bool True si éxito, false si falla
     */
    public function deleteExercise(string $unit_slug, string $exercise_slug): bool {
        $conn = $this->conn;

        // Primero verificar que el ejercicio exista y pertenezca a la unidad
        $stmt = $conn->prepare('SELECT e.id FROM ejercicios e JOIN unidades u ON e.unidad_id = u.id WHERE u.slug = ? AND e.slug = ? AND e.tipo = "practica" LIMIT 1');
        if (!$stmt) {
            error_log("Prepare failed in deleteExercise: {$conn->error}");
            return false;
        }

        $stmt->bind_param('ss', $unit_slug, $exercise_slug);
        if (!$stmt->execute()) {
            error_log("Execute failed in deleteExercise: {$stmt->error}");
            $stmt->close();
            return false;
        }

        $result = $stmt->get_result();
        if ($result->num_rows === 0) {
            $stmt->close();
            error_log("Exercise not found or doesn't belong to unit: {$unit_slug}/{$exercise_slug}");
            return false;
        }

        $row = $result->fetch_assoc();
        $exerciseId = $row['id'];
        $stmt->close();

        // Eliminar el progreso de usuarios relacionado con este ejercicio
        $stmt = $conn->prepare('DELETE FROM progreso_usuario WHERE ejercicio_id = ?');
        if ($stmt) {
            $stmt->bind_param('i', $exerciseId);
            $stmt->execute();
            $stmt->close();
        }

        // Eliminar el ejercicio
        $stmt = $conn->prepare('DELETE FROM ejercicios WHERE id = ? AND tipo = "practica" LIMIT 1');
        if (!$stmt) {
            error_log("Prepare failed in deleteExercise: {$conn->error}");
            return false;
        }

        $stmt->bind_param('i', $exerciseId);
        $res = $stmt->execute();
        
        if (!$res) {
            error_log("Execute failed in deleteExercise: {$stmt->error}");
        }
        
        $stmt->close();
        return (bool)$res;
    }

    /**
     * Elimina una evaluación de forma permanente
     *
     * @param string $unit_slug Slug de la unidad
     * @param string $exercise_slug Slug de la evaluación
     * @return bool True si éxito, false si falla
     */
    public function deleteEvaluation(string $unit_slug, string $exercise_slug): bool {
        $conn = $this->conn;

        // Primero verificar que la evaluación exista y pertenezca a la unidad
        $stmt = $conn->prepare('SELECT e.id FROM ejercicios e JOIN unidades u ON e.unidad_id = u.id WHERE u.slug = ? AND e.slug = ? AND e.tipo = "evaluacion" LIMIT 1');
        if (!$stmt) {
            error_log("Prepare failed in deleteEvaluation: {$conn->error}");
            return false;
        }

        $stmt->bind_param('ss', $unit_slug, $exercise_slug);
        if (!$stmt->execute()) {
            error_log("Execute failed in deleteEvaluation: {$stmt->error}");
            $stmt->close();
            return false;
        }

        $result = $stmt->get_result();
        if ($result->num_rows === 0) {
            $stmt->close();
            error_log("Evaluation not found or doesn't belong to unit: {$unit_slug}/{$exercise_slug}");
            return false;
        }

        $row = $result->fetch_assoc();
        $exerciseId = $row['id'];
        $stmt->close();

        // Eliminar los intentos de evaluación relacionados con este ejercicio
        $stmt = $conn->prepare('DELETE FROM intentos_evaluacion WHERE ejercicio_id = ?');
        if ($stmt) {
            $stmt->bind_param('i', $exerciseId);
            $stmt->execute();
            $stmt->close();
        }

        // Eliminar la evaluación
        $stmt = $conn->prepare('DELETE FROM ejercicios WHERE id = ? AND tipo = "evaluacion" LIMIT 1');
        if (!$stmt) {
            error_log("Prepare failed in deleteEvaluation: {$conn->error}");
            return false;
        }

        $stmt->bind_param('i', $exerciseId);
        $res = $stmt->execute();
        
        if (!$res) {
            error_log("Execute failed in deleteEvaluation: {$stmt->error}");
        }
        
        $stmt->close();
        return (bool)$res;
    }

    /**
     * Actualiza una evaluación existente
     *
     * @param string $unit_slug Slug de la unidad
     * @param string $exercise_slug Slug de la evaluación
     * @param array $fields Campos a actualizar
     * @return bool True si éxito, false si falla
     */
    public function updateEvaluation(string $unit_slug, string $exercise_slug, array $fields): bool {
        $conn = $this->conn;
        $sets = [];
        $types = '';
        $values = [];

        // Mapear nombres de campos antiguos a nuevos
        $fieldMapping = [
            'title' => 'titulo',
            'instructions' => 'instrucciones',
            'example' => 'ejemplo',
            'rubric' => 'rubrica'
        ];

        foreach (array_intersect_key($fields, $fieldMapping) as $oldCol => $val) {
            $newCol = $fieldMapping[$oldCol];
            $sets[] = "$newCol = ?";
            $types .= 's';
            $values[] = $val;
        }
        
        // Manejar el campo especial 'expected_code' que se mapea a 'solucion'
        if (isset($fields['expected_code'])) {
            $sets[] = "solucion = ?";
            $types .= 's';
            $values[] = $fields['expected_code'];
        }

        if (empty($sets)) return true; // No hay nada que actualizar

        $sql = 'UPDATE ejercicios e JOIN unidades u ON e.unidad_id = u.id SET ' . implode(', ', $sets) . ' WHERE u.slug = ? AND e.slug = ? AND e.tipo = "evaluacion" AND e.activo=1';
        $types .= 'ss';
        $values[] = $unit_slug;
        $values[] = $exercise_slug;
        
        $stmt = $conn->prepare($sql);
        if (!$stmt) return false;
        
        $refs = [];
        $refs[] = &$types;
        foreach ($values as $k => $v) $refs[] = &$values[$k];
        call_user_func_array([$stmt, 'bind_param'], $refs);
        
        $res = $stmt->execute();
        $stmt->close();
        return (bool)$res;
    }
}
?>
