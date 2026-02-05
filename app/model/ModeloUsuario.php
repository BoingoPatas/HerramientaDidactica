<?php
class ModeloUsuario {
    private $conn;

    public function __construct($db) {
        // Aceptar tanto la instancia `Database` (con getConnection()) como
        // una conexión `mysqli` directa. Normalizar a la conexión mysqli
        // para evitar llamar a métodos inexistentes como $this->conn->prepare
        if (is_object($db) && method_exists($db, 'getConnection')) {
            $this->conn = $db->getConnection();
        } else {
            $this->conn = $db;
        }
    }

    public function registrarUsuario($nombre_usuario, $correo, $contrasena) {
        // Verificar si el usuario ya existe antes de intentar registrar
        if ($this->usuarioExiste($nombre_usuario, $correo)) {
            return false;
        }

        // Encriptar la contraseña para seguridad
        $contrasena_encriptada = password_hash($contrasena, PASSWORD_DEFAULT);
        
        if ($contrasena_encriptada === false) {
            // Error en el hashing de la contraseña
            return false;
        }

        $sql = "INSERT INTO usuarios (nombre_usuario, correo_electronico, contrasena, rol, activo, primera_vez) VALUES (?, ?, ?, 'Usuario', 1, 1)";
        $stmt = $this->conn->prepare($sql);
        
        if (!$stmt) {
            // Error en la preparación de la consulta
            return false;
        }

        $stmt->bind_param("sss", $nombre_usuario, $correo, $contrasena_encriptada);

        if ($stmt->execute()) {
            $stmt->close();
            return true;
        } else {
            // Manejar error específico de duplicado
            if ($stmt->errno === 1062) { // Código de error MySQL para entrada duplicada
                error_log("Intento de registro duplicado: $nombre_usuario / $correo");
            }
            $stmt->close();
            return false;
        }
    }

    public function verificarCredenciales($nombre_usuario, $contrasena) {
        $this->ensureSchema(); // Asegurar que la tabla tenga todas las columnas necesarias
        $sql = "SELECT id, nombre_usuario, contrasena, rol, activo, primera_vez FROM usuarios WHERE (nombre_usuario = ? OR correo_electronico = ?)";
        $stmt = $this->conn->prepare($sql);

        if (!$stmt) {
            return false;
        }

        $stmt->bind_param("ss", $nombre_usuario, $nombre_usuario);

        if (!$stmt->execute()) {
            $stmt->close();
            return false;
        }

        $stmt->store_result();

        if ($stmt->num_rows != 1) {
            $stmt->close();
            return false;
        }

        $id = $nombre = $contrasena_hash = $rol = $activo = $primera_vez = "";
        $stmt->bind_result($id, $nombre, $contrasena_hash, $rol, $activo, $primera_vez);
        $stmt->fetch();
        $stmt->close();

        // Verificar que tenemos un hash válido antes de verificar
        if (empty($contrasena_hash)) {
            return false;
        }

        // Verificar contraseña primero
        if (!password_verify($contrasena, $contrasena_hash)) {
            return false;
        }

        // Luego verificar si el usuario está activo
        if (!$activo) {
            return [
                'id' => $id,
                'nombre_usuario' => $nombre,
                'rol' => $rol,
                'activo' => false,  // Indicador de que el usuario está inactivo
                'primera_vez' => $primera_vez
            ];
        }

        return [
            'id' => $id,
            'nombre_usuario' => $nombre,
            'rol' => $rol,
            'activo' => true,
            'primera_vez' => $primera_vez
        ];
    }

    public function obtenerUsuarioPorNombre($nombre_usuario) {
        $this->ensureSchema(); // Asegurar que la tabla tenga todas las columnas necesarias
        $sql = "SELECT id, nombre_usuario, correo_electronico, contrasena, rol, activo, primera_vez FROM usuarios WHERE nombre_usuario = ?";
        $stmt = $this->conn->prepare($sql);

        if (!$stmt) {
            return false;
        }

        $stmt->bind_param("s", $nombre_usuario);

        if (!$stmt->execute()) {
            $stmt->close();
            return false;
        }

        $stmt->store_result();

        if ($stmt->num_rows != 1) {
            $stmt->close();
            return false;
        }

        $id = $nombre = $correo = $contrasena = $rol = $activo = $primera_vez = "";
        $stmt->bind_result($id, $nombre, $correo, $contrasena, $rol, $activo, $primera_vez);
        $stmt->fetch();
        $stmt->close();

        return [
            'id' => $id,
            'nombre_usuario' => $nombre,
            'correo_electronico' => $correo,
            'contraseña' => $contrasena,
            'rol' => $rol,
            'activo' => (bool)$activo,
            'primera_vez' => $primera_vez
        ];
    }

    /**
     * Verifica si un usuario o correo ya existen en la base de datos
     */
    private function usuarioExiste($nombre_usuario, $correo) {
        $sql = "SELECT id FROM usuarios WHERE nombre_usuario = ? OR correo_electronico = ? LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        
        if (!$stmt) {
            return false;
        }

        $stmt->bind_param("ss", $nombre_usuario, $correo);
        $stmt->execute();
        $stmt->store_result();
        $exists = $stmt->num_rows > 0;
        $stmt->close();

        return $exists;
    }

    /* ----------------- Funciones de gestión de usuarios (Docente/Admin) ----------------- */
    private function ensureSchema(): void {
        // Añadir columnas 'rol', 'activo', 'primera_vez' y 'seccion' si no existen
        // Usar la conexión mysqli normalizada en `$this->conn`.
        $conn = $this->conn;
        
        if ($conn->query("SHOW COLUMNS FROM usuarios LIKE 'rol'")->num_rows === 0) {
            @$conn->query("ALTER TABLE usuarios ADD COLUMN rol VARCHAR(32) NOT NULL DEFAULT 'Usuario'");
        }
        if ($conn->query("SHOW COLUMNS FROM usuarios LIKE 'activo'")->num_rows === 0) {
            @$conn->query("ALTER TABLE usuarios ADD COLUMN activo TINYINT(1) NOT NULL DEFAULT 1");
        }
        if ($conn->query("SHOW COLUMNS FROM usuarios LIKE 'primera_vez'")->num_rows === 0) {
            @$conn->query("ALTER TABLE usuarios ADD COLUMN primera_vez TINYINT(1) NOT NULL DEFAULT 1");
        }
        if ($conn->query("SHOW COLUMNS FROM usuarios LIKE 'seccion'")->num_rows === 0) {
            @$conn->query("ALTER TABLE usuarios ADD COLUMN seccion VARCHAR(50) DEFAULT NULL");
        }
    }

    public function getUsersByRole(string $role): array {
        $this->ensureSchema();
        $out = [];
        $sql = 'SELECT id, nombre_usuario, correo_electronico, rol, activo, seccion FROM usuarios WHERE rol = ? ORDER BY id DESC';
        if ($stmt = $this->conn->prepare($sql)) {
            $stmt->bind_param('s', $role);
            if ($stmt->execute()) {
                // Declarar variables para bind_result (mejora compatibilidad con analizadores estáticos)
                $id = null; $nombre = null; $correo = null; $rol = null; $activo = null; $seccion = null;
                $stmt->bind_result($id, $nombre, $correo, $rol, $activo, $seccion);
                while ($stmt->fetch()) {
                    $out[] = ['id' => (int)$id, 'nombre_usuario' => $nombre, 'correo_electronico' => $correo, 'rol' => $rol, 'activo' => (int)$activo, 'seccion' => $seccion];
                }
            }
            $stmt->close();
        }
        return $out;
    }

    public function getUsersByRoles(array $roles): array {
        $this->ensureSchema();
        $out = [];
        if (empty($roles)) return $out;
        // Crear placeholders
        $placeholders = implode(',', array_fill(0, count($roles), '?'));
        $types = str_repeat('s', count($roles));
        $sql = "SELECT id, nombre_usuario, correo_electronico, rol, activo, seccion FROM usuarios WHERE rol IN ($placeholders) ORDER BY id DESC";
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) return $out;
        // Bind params dynamically
        $refs = [];
        $refs[] = & $types;
        foreach ($roles as $i => $r) {
            $refs[] = & $roles[$i];
        }
        call_user_func_array([$stmt, 'bind_param'], $refs);
        if ($stmt->execute()) {
            $id = null; $nombre = null; $correo = null; $rol = null; $activo = null; $seccion = null;
            $stmt->bind_result($id, $nombre, $correo, $rol, $activo, $seccion);
            while ($stmt->fetch()) {
                $out[] = ['id' => (int)$id, 'nombre_usuario' => $nombre, 'correo_electronico' => $correo, 'rol' => $rol, 'activo' => (int)$activo, 'seccion' => $seccion];
            }
        }
        $stmt->close();
        return $out;
    }

    public function getUserById(int $id): ?array {
        $this->ensureSchema();
        $sql = 'SELECT id, nombre_usuario, correo_electronico, rol, activo, seccion FROM usuarios WHERE id = ? LIMIT 1';
        if ($stmt = $this->conn->prepare($sql)) {
            $stmt->bind_param('i', $id);
            if ($stmt->execute()) {
                $rid = null; $nombre = null; $correo = null; $rol = null; $activo = null; $seccion = null;
                $stmt->bind_result($rid, $nombre, $correo, $rol, $activo, $seccion);
                if ($stmt->fetch()) {
                    $stmt->close();
                    return ['id' => (int)$rid, 'nombre_usuario' => $nombre, 'correo_electronico' => $correo, 'rol' => $rol, 'activo' => (int)$activo, 'seccion' => $seccion];
                }
            }
            $stmt->close();
        }
        return null;
    }

    public function createUser(string $nombre_usuario, string $correo, string $contrasena, string $rol = 'Usuario'): bool {
        $this->ensureSchema();
        if ($this->usuarioExiste($nombre_usuario, $correo)) return false;
        $contrasena_encriptada = password_hash($contrasena, PASSWORD_DEFAULT);
        if ($contrasena_encriptada === false) return false;
        $sql = "INSERT INTO usuarios (nombre_usuario, correo_electronico, contrasena, rol, activo, primera_vez) VALUES (?, ?, ?, ?, 1, 1)";
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) return false;
        $stmt->bind_param('ssss', $nombre_usuario, $correo, $contrasena_encriptada, $rol);
        $res = $stmt->execute();
        $stmt->close();
        return $res;
    }

    public function updateUser(int $id, array $fields): bool {
        $this->ensureSchema();
        $allowed = ['nombre_usuario','correo_electronico','contrasena','rol','activo','primera_vez','seccion'];
        $sets = [];
        $types = '';
        $values = [];
        foreach ($allowed as $col) {
            if (array_key_exists($col, $fields)) {
                if ($col === 'contrasena') {
                    $fields[$col] = password_hash($fields[$col], PASSWORD_DEFAULT);
                }
                $sets[] = "$col = ?";
                $types .= (in_array($col, ['activo', 'primera_vez'])) ? 'i' : 's';
                $values[] = $fields[$col];
            }
        }
        if (empty($sets)) return false;
        $sql = 'UPDATE usuarios SET ' . implode(', ', $sets) . ' WHERE id = ? LIMIT 1';
        $types .= 'i';
        $values[] = $id;
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) return false;
        $refs = [];
        $refs[] = & $types;
        foreach ($values as $k => $v) $refs[] = & $values[$k];
        call_user_func_array([$stmt, 'bind_param'], $refs);
        $res = $stmt->execute();
        $stmt->close();
        return $res;
    }

    public function setUserActive(int $id, int $active): bool {
        return $this->updateUser($id, ['activo' => $active]);
    }

    // Método para obtener todos los usuarios (para administradores)
    public function obtenerTodosUsuarios() {
        $this->ensureSchema();
        $sql = "SELECT id, nombre_usuario, correo_electronico, rol, activo, seccion FROM usuarios ORDER BY id DESC";
        $result = $this->conn->query($sql);

        $usuarios = [];
        while ($row = $result->fetch_assoc()) {
            $usuarios[] = $row;
        }
        return $usuarios;
    }

    // Método para actualizar el rol de un usuario (para administradores)
    public function actualizarRol($usuario_id, $nuevo_rol) {
        $this->ensureSchema();
        $sql = "UPDATE usuarios SET rol = ? WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("si", $nuevo_rol, $usuario_id);
        return $stmt->execute();
    }

    public function getUserByUsername($username) {
    $stmt = $this->conn->prepare('SELECT id, nombre_usuario, correo_electronico, rol, activo, seccion FROM usuarios WHERE nombre_usuario = ?');
    if ($stmt) {
        $stmt->bind_param('s', $username);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        $stmt->close();
        return $user;
    }
    return null;
}

    public function getSectionsData() {
        $this->ensureSchema();
        $sections = [];

        // Primero, obtener todas las secciones posibles de usuarios activos
        $allSections = [];
        $sql = "SELECT seccion FROM usuarios WHERE seccion IS NOT NULL AND seccion != '' AND activo = 1";
        $result = $this->conn->query($sql);

        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $sectionStr = $row['seccion'];
                // Parsear múltiples secciones separadas por coma
                if (strpos($sectionStr, ',') !== false) {
                    $parts = array_map('trim', explode(',', $sectionStr));
                    $allSections = array_merge($allSections, $parts);
                } else {
                    $allSections[] = $sectionStr;
                }
            }
            $allSections = array_unique($allSections); // Remover duplicados
            sort($allSections); // Ordenar
            $result->free();
        }

        // Ahora, por cada sección única, calcular conteos y docentes asignados
        foreach ($allSections as $section) {
            // Contar usuarios (excluyendo docentes) asignados directamente a esta sección
            $userCountStmt = $this->conn->prepare("SELECT COUNT(*) as count FROM usuarios WHERE (seccion = ? OR seccion LIKE ? OR seccion LIKE ? OR seccion LIKE ?) AND rol = 'Usuario' AND activo = 1");
            $likeStart = $section;
            $likeMiddle = '%,' . $section;
            $likeEnd = $section . ',%';
            $likeBoth = '%,' . $section . ',%';
            $userCountStmt->bind_param("ssss", $likeStart, $likeMiddle, $likeEnd, $likeBoth);
            $userCountStmt->execute();
            $userCountResult = $userCountStmt->get_result();
            $userCount = $userCountResult->fetch_assoc()['count'];
            $userCountStmt->close();

            // Obtener docentes asignados a esta sección (pueden tener múltiples secciones)
            $teachersStmt = $this->conn->prepare("SELECT nombre_usuario, seccion FROM usuarios WHERE rol = 'Docente' AND activo = 1 AND (seccion = ? OR seccion LIKE ? OR seccion LIKE ? OR seccion LIKE ?)");
            $teachersStmt->bind_param("ssss", $likeStart, $likeMiddle, $likeEnd, $likeBoth);
            $teachersStmt->execute();
            $teachersResult = $teachersStmt->get_result();
            $teachers = [];
            while ($teacherRow = $teachersResult->fetch_assoc()) {
                // Solo incluir si esta sección específica está en su lista de secciones
                $teacherSections = array_map('trim', explode(',', $teacherRow['seccion']));
                if (in_array($section, $teacherSections)) {
                    $teachers[] = $teacherRow['nombre_usuario'];
                }
            }
            $teachersStmt->close();
            sort($teachers); // Ordenar nombres de docentes

            $sections[] = [
                'seccion' => $section,
                'user_count' => (int)$userCount,
                'teachers' => $teachers,
                'multiple_teachers' => count($teachers) > 1
            ];
        }

        return $sections;
    }
}
