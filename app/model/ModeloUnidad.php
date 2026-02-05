<?php

/**
 * Modelo para gestionar unidades educativas
 * 
 * @author Samuel
 * @version 1.0
 */
class ModeloUnidad {
    private $db;

    /**
     * Constructor que inicializa la conexión a la base de datos
     */
    public function __construct($db_connection) {
        $this->db = $db_connection;
    }

    /**
     * Obtiene todas las unidades de un docente
     * 
     * @param int $docenteId ID del docente
     * @return array Lista de unidades del docente
     */
    public function obtenerUnidadesPorDocente($docenteId) {
        try {
            // Validar conexión
            if (!$this->db || !$this->db->conn) {
                error_log("Error: Conexión a base de datos no disponible");
                return [];
            }
            
            $query = "SELECT * FROM unidades WHERE docente_id = ? ORDER BY orden ASC";
            $stmt = $this->db->conn->prepare($query);
            $stmt->bind_param('i', $docenteId);
            $stmt->execute();
            
            $result = $stmt->get_result();
            return $result->fetch_all(MYSQLI_ASSOC);
        } catch (Exception $e) {
            error_log("Error al obtener unidades por docente: " . $e->getMessage());
            return [];
        }
    }
    /**
     * Obtiene una unidad específica por su ID
     * 
     * @param int $unidadId ID de la unidad
     * @return array|null Datos de la unidad o null si no existe
     */
    public function obtenerUnidadPorId($unidadId) {
        try {
            // Validar conexión
            if (!$this->db || !$this->db->conn) {
                error_log("Error: Conexión a base de datos no disponible");
                return null;
            }
            
            $query = "SELECT * FROM unidades WHERE id = ?";
            $stmt = $this->db->conn->prepare($query);
            $stmt->bind_param('i', $unidadId);
            $stmt->execute();
            
            $result = $stmt->get_result();
            return $result->fetch_assoc();
        } catch (Exception $e) {
            error_log("Error al obtener unidad por ID: " . $e->getMessage());
            return null;
        }
    }
    /**
     * Obtiene una unidad por su slug
     * 
     * @param string $slug Slug de la unidad
     * @return array|null Datos de la unidad o null si no existe
     */
    public function obtenerUnidadPorSlug($slug) {
        try {
            // Validar conexión
            if (!$this->db || !$this->db->conn) {
                error_log("Error: Conexión a base de datos no disponible");
                return null;
            }
            
            $query = "SELECT * FROM unidades WHERE slug = ?";
            $stmt = $this->db->conn->prepare($query);
            $stmt->bind_param('s', $slug);
            $stmt->execute();
            
            $result = $stmt->get_result();
            return $result->fetch_assoc();
        } catch (Exception $e) {
            error_log("Error al obtener unidad por slug: " . $e->getMessage());
            return null;
        }
    }
    /**
     * Obtiene el orden actual de una unidad
     * 
     * @param int $unidadId ID de la unidad
     * @return int|null Orden actual de la unidad o null si no existe
     */
    public function obtenerOrdenActual($unidadId) {
        try {
            // Validar conexión
            if (!$this->db || !$this->db->conn) {
                error_log("Error: Conexión a base de datos no disponible");
                return null;
            }
            
            $query = "SELECT orden FROM unidades WHERE id = ?";
            $stmt = $this->db->conn->prepare($query);
            $stmt->bind_param('i', $unidadId);
            $stmt->execute();
            
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            return $row ? (int)$row['orden'] : null;
        } catch (Exception $e) {
            error_log("Error al obtener orden actual: " . $e->getMessage());
            return null;
        }
    }
    /**
     * Obtiene el número total de unidades de un docente
     * 
     * @param int $docenteId ID del docente
     * @return int Número total de unidades
     */
    public function contarUnidadesPorDocente($docenteId) {
        try {
            // Validar conexión
            if (!$this->db || !$this->db->conn) {
                error_log("Error: Conexión a base de datos no disponible");
                return 0;
            }
            
            $query = "SELECT COUNT(*) as total FROM unidades WHERE docente_id = ?";
            $stmt = $this->db->conn->prepare($query);
            $stmt->bind_param('i', $docenteId);
            $stmt->execute();
            
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            return (int)$row['total'];
        } catch (Exception $e) {
            error_log("Error al contar unidades: " . $e->getMessage());
            return 0;
        }
    }
    /**
     * Obtiene el orden máximo de las unidades de un docente
     * 
     * @param int $docenteId ID del docente
     * @return int Orden máximo o 0 si no hay unidades
     */
    public function obtenerOrdenMaximo($docenteId) {
        try {
            // Validar conexión
            if (!$this->db || !$this->db->conn) {
                error_log("Error: Conexión a base de datos no disponible");
                return 0;
            }
            
            $query = "SELECT MAX(orden) as max_orden FROM unidades WHERE docente_id = ?";
            $stmt = $this->db->conn->prepare($query);
            $stmt->bind_param('i', $docenteId);
            $stmt->execute();
            
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            return $row && $row['max_orden'] !== null ? (int)$row['max_orden'] : 0;
        } catch (Exception $e) {
            error_log("Error al obtener orden máximo: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Verifica si una unidad pertenece a un docente específico
     * 
     * @param int $unidadId ID de la unidad
     * @param int $docenteId ID del docente
     * @return bool True si la unidad pertenece al docente, false en caso contrario
     */
    public function verificarPropiedadUnidad($unidadId, $docenteId) {
        try {
            // Validar conexión
            if (!$this->db || !$this->db->conn) {
                error_log("Error: Conexión a base de datos no disponible");
                return false;
            }
            
            $query = "SELECT COUNT(*) as total FROM unidades WHERE id = ? AND docente_id = ?";
            $stmt = $this->db->conn->prepare($query);
            $stmt->bind_param('ii', $unidadId, $docenteId);
            $stmt->execute();
            
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            return (int)$row['total'] > 0;
        } catch (Exception $e) {
            error_log("Error al verificar propiedad de unidad: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Cambia el orden de una unidad a una posición específica
     * 
     * @param int $unidadId ID de la unidad a mover
     * @param int $nuevaPosicion Nueva posición deseada (1-based)
     * @param int $docenteId ID del docente propietario
     * @return bool True si el reordenamiento fue exitoso, false en caso contrario
     */
    public function changeUnitOrderToPosition($unidadId, $nuevaPosicion, $docenteId) {
        try {
            // Validar conexión
            if (!$this->db || !$this->db->conn) {
                error_log("Error: Conexión a base de datos no disponible");
                return false;
            }

            // Validar que la unidad pertenece al docente
            if (!$this->verificarPropiedadUnidad($unidadId, $docenteId)) {
                error_log("Error: Unidad {$unidadId} no pertenece al docente {$docenteId}");
                return false;
            }

            // Validar rango de posición
            $totalUnidades = $this->contarUnidadesPorDocente($docenteId);
            if ($nuevaPosicion < 1 || $nuevaPosicion > $totalUnidades) {
                error_log("Error: Posición {$nuevaPosicion} fuera de rango (1-{$totalUnidades}) para docente {$docenteId}");
                return false;
            }

            // Iniciar transacción
            $this->db->conn->begin_transaction();

            // Obtener el orden actual de la unidad
            $ordenActual = $this->obtenerOrdenActual($unidadId);
            if ($ordenActual === null) {
                error_log("Error: No se encontró la unidad {$unidadId}");
                $this->db->conn->rollback();
                return false;
            }

            // Caso 1: Mover hacia arriba (posición menor)
            if ($nuevaPosicion < $ordenActual) {
                // Desplazar hacia abajo las unidades entre la nueva posición y la posición actual - 1
                $query = "UPDATE unidades 
                         SET orden = orden + 1 
                         WHERE docente_id = ? 
                         AND orden >= ? 
                         AND orden < ?";
                $stmt = $this->db->conn->prepare($query);
                $stmt->bind_param('iii', $docenteId, $nuevaPosicion, $ordenActual);
                
                if (!$stmt->execute()) {
                    error_log("Error al desplazar unidades hacia abajo: " . $stmt->error);
                    $this->db->conn->rollback();
                    return false;
                }

            // Caso 2: Mover hacia abajo (posición mayor)
            } elseif ($nuevaPosicion > $ordenActual) {
                // Desplazar hacia arriba las unidades entre la posición actual + 1 y la nueva posición
                $query = "UPDATE unidades 
                         SET orden = orden - 1 
                         WHERE docente_id = ? 
                         AND orden > ? 
                         AND orden <= ?";
                $stmt = $this->db->conn->prepare($query);
                $stmt->bind_param('iii', $docenteId, $ordenActual, $nuevaPosicion);
                
                if (!$stmt->execute()) {
                    error_log("Error al desplazar unidades hacia arriba: " . $stmt->error);
                    $this->db->conn->rollback();
                    return false;
                }
            }

            // Caso 3: Mover a la misma posición (idempotencia)
            // No es necesario hacer nada, pero debemos considerarlo como éxito

            // Actualizar la posición de la unidad objetivo
            $query = "UPDATE unidades 
                     SET orden = ? 
                     WHERE id = ? 
                     AND docente_id = ?";
            $stmt = $this->db->conn->prepare($query);
            $stmt->bind_param('iii', $nuevaPosicion, $unidadId, $docenteId);
            
            if (!$stmt->execute()) {
                error_log("Error al actualizar posición de unidad: " . $stmt->error);
                $this->db->conn->rollback();
                return false;
            }

            // Considerar éxito si:
            // 1. Se afectaron filas (movimiento real), o
            // 2. No se afectaron filas pero no hubo error (idempotencia)
            $affectedRows = $stmt->affected_rows;
            $success = ($affectedRows > 0) || ($affectedRows === 0 && $stmt->error === '');

            if ($success) {
                $this->db->conn->commit();
                error_log("Reordenamiento exitoso: unidad {$unidadId} movida a posición {$nuevaPosicion}");
                return true;
            } else {
                error_log("Reordenamiento fallido: unidad {$unidadId} no se movió a posición {$nuevaPosicion}");
                $this->db->conn->rollback();
                return false;
            }

        } catch (Exception $e) {
            error_log("Excepción al reordenar unidad: " . $e->getMessage());
            if ($this->db->conn->inTransaction()) {
                $this->db->conn->rollback();
            }
            return false;
        }
    }

    /**
     * Lista unidades para dropdown sin validaciones de rol
     * Devuelve un array limpio con solo los campos necesarios para el dropdown
     * 
     * @return array Lista de unidades con campos: id, slug, titulo, descripcion, orden, activo, docente_id
     */
    /**
     * Lista unidades con filtros opcionales por rol y usuario
     * * @param int|null $userId ID del usuario/docente
     * @param string|null $rol Rol del usuario
     * @param string|null $seccion Sección (para estudiantes)
     * @return array Lista de unidades
     */
    public function listUnits($userId = null, $rol = null, $trimestre = 1) {
        $query = "SELECT u.* FROM unidades u";
        
        if ($rol === 'Usuario') {
            // Un estudiante solo ve unidades de trimestres activos y unidades activas
            $query .= " JOIN trimestres_control tc ON u.docente_id = tc.docente_id AND u.trimestre = tc.trimestre 
                        WHERE u.activo = 1 AND tc.activo = 1 AND u.trimestre = ?";
        } else {
            // El docente ve todo lo de su trimestre seleccionado
            $query .= " WHERE u.docente_id = ? AND u.trimestre = ?";
        }
        
        $query .= " ORDER BY u.orden ASC";
        $stmt = $this->db->conn->prepare($query);
        
        if ($rol === 'Usuario') {
            $stmt->bind_param("i", $trimestre);
        } else {
            $stmt->bind_param("ii", $userId, $trimestre);
        }
        
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function reordenarUnidad($unidadId, $nuevaPosicion) {
        try {
            $this->db->conn->begin_transaction();

            // 1. Obtener orden actual
            $stmt = $this->db->conn->prepare("SELECT orden FROM unidades WHERE id = ?");
            $stmt->bind_param("i", $unidadId);
            $stmt->execute();
            $posVieja = $stmt->get_result()->fetch_assoc()['orden'];
            
            $nuevaPosicion = (int)$nuevaPosicion;
            $posVieja = (int)$posVieja;

            if ($posVieja === $nuevaPosicion) return true;

            if ($posVieja < $nuevaPosicion) {
                // Mover hacia abajo: las unidades entre medio restan 1 a su orden
                $sql = "UPDATE unidades SET orden = orden - 1 WHERE orden > ? AND orden <= ?";
                $stmt = $this->db->conn->prepare($sql);
                $stmt->bind_param("ii", $posVieja, $nuevaPosicion);
            } else {
                // Mover hacia arriba: las unidades entre medio suman 1 a su orden
                $sql = "UPDATE unidades SET orden = orden + 1 WHERE orden >= ? AND orden < ?";
                $stmt = $this->db->conn->prepare($sql);
                $stmt->bind_param("ii", $nuevaPosicion, $posVieja);
            }
            $stmt->execute();

            // 2. Colocar la unidad en su nuevo sitio
            $stmt = $this->db->conn->prepare("UPDATE unidades SET orden = ? WHERE id = ?");
            $stmt->bind_param("ii", $nuevaPosicion, $unidadId);
            $stmt->execute();

            $this->db->conn->commit();
            return true;
        } catch (Exception $e) {
            $this->db->conn->rollback();
            return false;
        }
    }

    public function crearUnidad($titulo, $descripcion, $docenteId, $ordenDeseado, $trimestre) {
        try {
            $this->db->conn->begin_transaction();

            $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $titulo)));
            $ordenFinal = (int)$ordenDeseado;

            // 1. "Hacer espacio"
            $querySpace = "UPDATE unidades SET orden = orden + 1 WHERE docente_id = ? AND orden >= ? AND trimestre = ?";
            $stmtSpace = $this->db->conn->prepare($querySpace);
            $stmtSpace->bind_param("iii", $docenteId, $ordenFinal, $trimestre);
            $stmtSpace->execute();

            // 2. Insertar la nueva unidad
            $query = "INSERT INTO unidades (titulo, descripcion, slug, orden, docente_id, activo, trimestre) 
                    VALUES (?, ?, ?, ?, ?, 1, ?)";
            
            $stmt = $this->db->conn->prepare($query);
            $stmt->bind_param('sssiii', $titulo, $descripcion, $slug, $ordenFinal, $docenteId, $trimestre);
            $result = $stmt->execute(); // Guardamos el resultado en una variable

            $this->db->conn->commit(); // PRIMERO hacemos el commit
            return $result;            // LUEGO retornamos el resultado
            
        } catch (Exception $e) {
            $this->db->conn->rollback();
            error_log("Error al crear unidad: " . $e->getMessage());
            return false;
        }
    }

    public function obtenerUnidadesPorTrimestre($userId, $trimestre, $rol = 'Usuario') {
        try {
            if ($rol === 'Docente' || $rol === 'Administrador') {
                // El docente ve todo lo de su trimestre
                $query = "SELECT * FROM unidades WHERE docente_id = ? AND trimestre = ? ORDER BY orden ASC";
                $stmt = $this->db->conn->prepare($query);
                $stmt->bind_param('ii', $userId, $trimestre);
            } else {
                // El ESTUDIANTE solo ve unidades si:
                // 1. La unidad está activa (activo = 1)
                // 2. El trimestre está marcado como visible (trimestres_control.activo = 1)
                $query = "SELECT u.* FROM unidades u 
                        INNER JOIN trimestres_control tc ON u.docente_id = tc.docente_id 
                        WHERE u.trimestre = ? 
                        AND u.activo = 1 
                        AND tc.trimestre = ? 
                        AND tc.activo = 1 
                        ORDER BY u.orden ASC";
                $stmt = $this->db->conn->prepare($query);
                $stmt->bind_param('ii', $trimestre, $trimestre);
            }
            
            $stmt->execute();
            return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        } catch (Exception $e) {
            error_log("Error en obtenerUnidadesPorTrimestre: " . $e->getMessage());
            return [];
        }
    }

    public function actualizarVisibilidadTrimestre($docenteId, $trimestre, $activo) {
        $query = "INSERT INTO trimestres_control (docente_id, trimestre, activo) 
                VALUES (?, ?, ?) 
                ON DUPLICATE KEY UPDATE activo = VALUES(activo)";
        $stmt = $this->db->conn->prepare($query);
        $stmt->bind_param("iii", $docenteId, $trimestre, $activo);
        return $stmt->execute();
    }

    public function esTrimestreVisible($docenteId, $trimestre) {
        $query = "SELECT activo FROM trimestres_control WHERE docente_id = ? AND trimestre = ?";
        $stmt = $this->db->conn->prepare($query);
        $stmt->bind_param("ii", $docenteId, $trimestre);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        // Por defecto, si no existe el registro, asumimos que está activo (1)
        return $result ? (bool)$result['activo'] : true;
    }

    // En ModeloUnidad.php, agregar:
    public function updateUnitActiveStatus($unitId, $activo) {
        try {
            $query = "UPDATE unidades SET activo = ? WHERE id = ?";
            $stmt = $this->db->conn->prepare($query);
            $stmt->bind_param('ii', $activo, $unitId);
            $success = $stmt->execute();
            $stmt->close();
            return $success;
        } catch (Exception $e) {
            error_log("Error al actualizar estado de unidad: " . $e->getMessage());
            return false;
        }
    }

}


