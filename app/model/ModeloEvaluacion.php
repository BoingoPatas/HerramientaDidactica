<?php
require_once __DIR__ . '/../config/BaseDatos.php';

class EvaluationModel {
    private $db;
    private $conn;

    public function __construct($db) {
        $this->db = $db;
        $this->conn = (is_object($db) && method_exists($db, 'getConnection')) ? $db->getConnection() : $db;
        // No longer creates tables - assumes normalized schema exists
    }

    public function listEvaluationUnits(): array {
        $conn = $this->conn;
        $out = [];
        $res = $conn->query('SELECT id, slug, titulo, descripcion, icono, orden, activo FROM unidades WHERE activo=1 AND tipo="evaluacion" ORDER BY orden ASC, id DESC');
        if ($res === false) {
            error_log("Database error in listEvaluationUnits: " . $conn->error);
            return [];
        }

        while ($r = $res->fetch_assoc()) {
            $out[] = [
                'id' => $r['id'],
                'slug' => $r['slug'],
                'title' => $r['titulo'], // Keep 'title' for compatibility
                'description' => $r['descripcion'],
                'icon' => $r['icono'],
                'orden' => $r['orden'],
                'activo' => $r['activo']
            ];
        }
        $res->free();
        return $out;
    }

    public function getEvaluationUnit(string $slug): ?array {
        $conn = $this->conn;
        if ($stmt = $conn->prepare('SELECT id, slug, titulo, descripcion, icono, orden, activo FROM unidades WHERE slug = ? AND activo=1 AND tipo="evaluacion" LIMIT 1')) {
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
        if ($stmt = $conn->prepare('SELECT e.id, e.slug, e.titulo, e.orden, e.instrucciones, e.ejemplo, e.rubrica, e.activo FROM ejercicios e JOIN unidades u ON e.unidad_id = u.id WHERE u.slug = ? AND e.tipo = "evaluacion" AND e.activo=1 ORDER BY e.orden ASC, e.id DESC')) {
            $stmt->bind_param('s', $unit_slug);
            if ($stmt->execute()) {
                $id_out = $slug_out = $titulo_out = $orden_out = $instrucciones_out = $ejemplo_out = $rubrica_out = $activo_out = null;
                $stmt->bind_result($id_out, $slug_out, $titulo_out, $orden_out, $instrucciones_out, $ejemplo_out, $rubrica_out, $activo_out);
                while ($stmt->fetch()) {
                    $out[] = [
                        'id' => $id_out,
                        'slug' => $slug_out,
                        'title' => $titulo_out, // Keep 'title' for compatibility
                        'orden' => $orden_out,
                        'instructions' => $instrucciones_out,
                        'example' => $ejemplo_out,
                        'rubric' => $rubrica_out ? json_decode($rubrica_out, true) : [],
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
        if ($stmt = $conn->prepare('SELECT e.id, e.slug, e.titulo, e.orden, e.instrucciones, e.ejemplo, e.rubrica FROM ejercicios e JOIN unidades u ON e.unidad_id = u.id WHERE u.slug = ? AND e.slug = ? AND e.tipo = "evaluacion" AND e.activo=1 LIMIT 1')) {
            $stmt->bind_param('ss', $unit_slug, $exercise_slug);
            if ($stmt->execute()) {
                $id_out = null; $slug_out = null; $titulo_out = null; $orden_out = null; $instrucciones_out = null; $ejemplo_out = null; $rubrica_out = null;
                $stmt->bind_result($id_out, $slug_out, $titulo_out, $orden_out, $instrucciones_out, $ejemplo_out, $rubrica_out);
                if ($stmt->fetch()) {
                    $stmt->close();
                    return [
                        'id' => $id_out,
                        'slug' => $slug_out,
                        'title' => $titulo_out, // Keep 'title' for compatibility
                        'orden' => $orden_out,
                        'instructions' => $instrucciones_out,
                        'example' => $ejemplo_out,
                        'rubric' => $rubrica_out ? json_decode($rubrica_out, true) : []
                    ];
                }
            }
            $stmt->close();
        }
        return null;
    }

    // Método para compatibilidad con versiones anteriores, ahora utiliza el sistema de unidades y ejercicios
    public function getEvaluation(string $key): ?array {
        // Intenta primero obtener el ejercicio tratando la clave como slug de la unidad 'variables' o similar
        // Dado que la clave es similar a 'variables', se trata como slug de unidad y se obtiene el primer ejercicio
        $exercises = $this->listExercisesByUnit($key);
        if ($exercises) {
            $ex = $exercises[0];
            $unit = $this->getEvaluationUnit($key);
            return [
                'key' => $key,
                'title' => $unit ? $unit['title'] : $ex['title'],
                'description' => $unit ? $unit['description'] : '',
                'unit' => $key,
                'instructions' => $ex['instructions'],
                'example' => $ex['example'],
                'rubric' => $ex['rubric']
            ];
        }
        return null;
    }

    public function listEvaluations(): array {
        $out = [];
        $units = $this->listEvaluationUnits();
        foreach ($units as $unit) {
            $exercises = $this->listExercisesByUnit($unit['slug']);
            if ($exercises) {
                $ex = $exercises[0];
                $out[$unit['slug']] = [
                    'key' => $unit['slug'],
                    'title' => $unit['title'],
                    'description' => $unit['description'],
                    'unit' => $unit['slug'],
                    'instructions' => $ex['instructions'] ?? '',
                    'example' => $ex['example'] ?? '',
                    'rubric' => $ex['rubric'] ?? []
                ];
            }
        }
        return $out;
    }

    public function createEvaluationUnit(string $slug, array $data): bool {
        $conn = $this->conn;
        if ($this->getEvaluationUnit($slug)) return false; // exists
        $stmt = $conn->prepare('INSERT INTO unidades (slug, titulo, descripcion, icono, orden, tipo, activo) VALUES (?, ?, ?, ?, ?, "evaluacion", 1)');
        if (!$stmt) return false;
        $title = $data['title'] ?? '';
        $description = $data['description'] ?? '';
        $icon = $data['icon'] ?? '';
        $orden = $data['orden'] ?? 0;
        $stmt->bind_param('ssssis', $slug, $title, $description, $icon, $orden);
        $res = $stmt->execute();
        $stmt->close();
        return (bool)$res;
    }

    public function createExercise(string $unit_slug, string $slug, array $data): bool {
        $conn = $this->conn;

        // First get the unit_id
        $stmt = $conn->prepare('SELECT id FROM unidades WHERE slug = ? AND tipo = "evaluacion" LIMIT 1');
        $stmt->bind_param('s', $unit_slug);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            $stmt->close();
            return false; // Unit not found
        }

        $row = $result->fetch_assoc();
        $unitId = $row['id'];
        $stmt->close();

        $rubric_json = isset($data['rubric']) ? json_encode($data['rubric']) : '';
        $stmt = $conn->prepare('INSERT INTO ejercicios (unidad_id, slug, titulo, tipo, orden, instrucciones, ejemplo, rubrica, activo) VALUES (?, ?, ?, "evaluacion", ?, ?, ?, ?, 1)');
        if (!$stmt) return false;
        $title = $data['title'] ?? '';
        $orden = $data['orden'] ?? 0;
        $instructions = $data['instructions'] ?? '';
        $example = $data['example'] ?? '';
        $stmt->bind_param('ississs', $unitId, $slug, $title, $orden, $instructions, $example, $rubric_json);
        $res = $stmt->execute();
        $stmt->close();
        return (bool)$res;
    }

    // Se conservan los métodos antiguos para compatibilidad, pero ahora operan con las nuevas tablas

    public function createEvaluation(string $key, array $data): bool {
        // Crear unidad usando la clave como slug, y el ejercicio correspondiente
        $data['rubric'] = $data['rubric'] ?? [];
        return $this->createEvaluationUnit($key, ['title' => $data['title'] ?? '', 'description' => $data['description'] ?? '', 'icon' => '', 'orden' => 0]) &&
               $this->createExercise($key, $key, $data);
    }

    public function updateEvaluation(string $key, array $fields): bool {
        $conn = $this->conn;
        // Actualizar la unidad y el ejercicio correspondiente
        if (isset($fields['title']) || isset($fields['description'])) {
            $sets = []; $types = ''; $values = [];
            if (isset($fields['title'])) { $sets[] = 'titulo = ?'; $types .= 's'; $values[] = $fields['title']; }
            if (isset($fields['description'])) { $sets[] = 'descripcion = ?'; $types .= 's'; $values[] = $fields['description']; }
            if ($sets) {
                $sql = 'UPDATE unidades SET ' . implode(', ', $sets) . ' WHERE slug = ? AND tipo = "evaluacion"';
                $types .= 's'; $values[] = $key;
                $stmt = $conn->prepare($sql);
                if ($stmt) {
                    $refs = [];$refs[] = &$types; foreach ($values as $k => $v) $refs[] = &$values[$k];
                    call_user_func_array([$stmt, 'bind_param'], $refs);
                    $stmt->execute(); $stmt->close();
                }
            }
        }
        $fields_ex = array_intersect_key($fields, array_flip(['instructions', 'example']));
        if ($fields_ex) {
            $sets = []; $types = ''; $values = [];
            foreach ($fields_ex as $col => $val) {
                $colName = ($col === 'instructions') ? 'instrucciones' : (($col === 'example') ? 'ejemplo' : $col);
                $sets[] = "$colName = ?"; $types .= 's'; $values[] = $val;
            }
            $sql = 'UPDATE ejercicios e JOIN unidades u ON e.unidad_id = u.id SET ' . implode(', ', $sets) . ' WHERE u.slug = ? AND e.slug = ? AND e.tipo = "evaluacion"';
            $types .= 'ss'; $values[] = $key; $values[] = $key;
            $stmt = $conn->prepare($sql);
            if ($stmt) {
                $refs = [];$refs[] = &$types; foreach ($values as $k => $v) $refs[] = &$values[$k];
                call_user_func_array([$stmt, 'bind_param'], $refs);
                $stmt->execute(); $stmt->close();
            }
        }
        if (isset($fields['rubric'])) {
            $rubric_json = json_encode($fields['rubric']);
            $stmt = $conn->prepare('UPDATE ejercicios e JOIN unidades u ON e.unidad_id = u.id SET e.rubrica = ? WHERE u.slug = ? AND e.slug = ? AND e.tipo = "evaluacion"');
            if ($stmt) {
                $stmt->bind_param('sss', $rubric_json, $key, $key);
                $stmt->execute(); $stmt->close();
            }
        }
        return true;
    }

    public function deleteEvaluation(string $key): bool {
        $conn = $this->conn;
        // Soft delete: cambiar activo a 0 en lugar de eliminar físicamente
        $stmt = $conn->prepare('UPDATE unidades SET activo = 0 WHERE slug = ? AND tipo = "evaluacion"');
        if (!$stmt) return false;
        $stmt->bind_param('s', $key);
        $res = $stmt->execute();
        $stmt->close();
        return (bool)$res;
    }

    /**
     * Habilita o inhabilita una unidad de evaluación
     *
     * @param string $slug Slug de la unidad
     * @param bool $active True para habilitar, false para inhabilitar
     * @return bool True si éxito, false si falla
     */
    public function setEvaluationUnitActive(string $slug, bool $active): bool {
        $conn = $this->conn;
        
        // Validar que la unidad existe
        if (!$this->getEvaluationUnit($slug)) {
            error_log("Set active failed: evaluation unit {$slug} does not exist");
            return false;
        }
        
        $stmt = $conn->prepare('UPDATE unidades SET activo = ? WHERE slug = ? AND tipo = "evaluacion" LIMIT 1');
        if (!$stmt) {
            error_log("Prepare failed in setEvaluationUnitActive: {$conn->error}");
            return false;
        }
        
        $activeInt = $active ? 1 : 0;
        $stmt->bind_param('is', $activeInt, $slug);
        $res = $stmt->execute();
        
        if (!$res) {
            error_log("Execute failed in setEvaluationUnitActive: {$stmt->error}");
        }
        
        $stmt->close();
        return $res;
    }

    /**
     * Habilita o inhabilita una evaluación
     *
     * @param string $unit_slug Slug de la unidad
     * @param string $exercise_slug Slug de la evaluación
     * @param bool $active True para habilitar, false para inhabilitar
     * @return bool True si éxito, false si falla
     */
    public function setEvaluationExerciseActive(string $unit_slug, string $exercise_slug, bool $active): bool {
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
}
