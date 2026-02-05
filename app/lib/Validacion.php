<?php

/**
 * Validation - Sistema centralizado de validaciones para la aplicación
 *
 * Este archivo contiene todas las validaciones de formularios y datos del sistema,
 * proporcionando un único punto de control para garantizar consistencia y seguridad.
 */

class Validation
{
    // Mensajes de error estandarizados
    private static $messages = [
        'required' => 'El campo %s es obligatorio.',
        'min_length' => 'El campo %s debe tener al menos %d caracteres.',
        'max_length' => 'El campo %s no puede tener más de %d caracteres.',
        'exact_length' => 'El campo %s debe tener exactamente %d caracteres.',
        'email' => 'El campo %s debe ser un correo electrónico válido.',
        'username_format' => 'El nombre de usuario solo puede contener letras, números, guion, guion bajo y punto.',
        'password_strength' => 'La contraseña debe tener al menos 8 caracteres, incluyendo mayúscula, minúscula, número y carácter especial.',
        'password_match' => 'Las contraseñas no coinciden.',
        'number' => 'El campo %s debe ser un número válido.',
        'integer' => 'El campo %s debe ser un número entero.',
        'url' => 'El campo %s debe ser una URL válida.',
        'date' => 'El campo %s debe ser una fecha válida.',
        'csrf' => 'Token CSRF inválido. Por favor, inténtalo de nuevo.',
        'unique' => 'El valor %s ya está en uso.',
        'slug_format' => 'El slug solo puede contener letras minúsculas, números y guiones.',
        'section_format' => 'La sección debe contener solo números separados por comas (ej: 22,23).'
    ];

    /**
     * Validar datos de registro de usuario
     *
     * @param string $username Nombre de usuario
     * @param string $email Correo electrónico
     * @param string $password Contraseña
     * @param string $confirmPassword Confirmación de contraseña
     * @return array|true Array de errores o true si es válido
     */
    public static function validateRegistration($username, $email, $password, $confirmPassword = null)
    {
        $errors = [];

        // Validar campos requeridos
        if (empty($username)) {
            $errors['username'] = sprintf(self::$messages['required'], 'nombre de usuario');
        } elseif (!self::validateUsernameFormat($username)) {
            $errors['username'] = self::$messages['username_format'];
        } elseif (strlen($username) < 3) {
            $errors['username'] = sprintf(self::$messages['min_length'], 'nombre de usuario', 3);
        } elseif (strlen($username) > 32) {
            $errors['username'] = sprintf(self::$messages['max_length'], 'nombre de usuario', 32);
        }

        if (empty($email)) {
            $errors['email'] = sprintf(self::$messages['required'], 'correo electrónico');
        } elseif (!self::validateEmail($email)) {
            $errors['email'] = self::$messages['email'];
        }

        if (empty($password)) {
            $errors['password'] = sprintf(self::$messages['required'], 'contraseña');
        } elseif (!self::validatePasswordStrength($password)) {
            $errors['password'] = self::$messages['password_strength'];
        }

        if ($confirmPassword !== null && $password !== $confirmPassword) {
            $errors['confirm_password'] = self::$messages['password_match'];
        }

        return empty($errors) ? true : $errors;
    }

    /**
     * Validar datos de inicio de sesión
     *
     * @param string $username Nombre de usuario
     * @param string $password Contraseña
     * @return array|true Array de errores o true si es válido
     */
    public static function validateLogin($username, $password)
    {
        $errors = [];

        if (empty($username)) {
            $errors['username'] = sprintf(self::$messages['required'], 'nombre de usuario');
        } elseif (strlen($username) > 64) {
            $errors['username'] = sprintf(self::$messages['max_length'], 'nombre de usuario', 64);
        }

        if (empty($password)) {
            $errors['password'] = sprintf(self::$messages['required'], 'contraseña');
        }

        return empty($errors) ? true : $errors;
    }

    /**
     * Validar token CSRF
     *
     * @param string $sessionToken Token de sesión
     * @param string $formToken Token del formulario
     * @return bool
     */
    public static function validateCSRF($sessionToken, $formToken)
    {
        if (empty($sessionToken) || empty($formToken)) {
            return false;
        }

        return hash_equals($sessionToken, $formToken);
    }

    /**
     * Validar formato de nombre de usuario
     *
     * @param string $username Nombre de usuario
     * @return bool
     */
    public static function validateUsernameFormat($username)
    {
        return preg_match('/^[A-Za-z0-9_\-.]{3,32}$/', $username);
    }

    /**
     * Validar formato de correo electrónico
     *
     * @param string $email Correo electrónico
     * @return bool
     */
    public static function validateEmail($email)
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL);
    }

    /**
     * Validar fuerza de contraseña
     *
     * @param string $password Contraseña
     * @return bool
     */
    public static function validatePasswordStrength($password)
    {
        if (strlen($password) < 8) {
            return false;
        }

        // Requerir al menos una mayúscula, una minúscula, un número y un carácter especial
        $hasUpper = preg_match('/[A-Z]/', $password);
        $hasLower = preg_match('/[a-z]/', $password);
        $hasNumber = preg_match('/\d/', $password);
        $hasSpecial = preg_match('/[!@#$%^&*()_+\-=\[\]{};\':"\\|,.<>\/?]/', $password);

        return $hasUpper && $hasLower && $hasNumber && $hasSpecial;
    }

    /**
     * Validar datos de usuario/docente para creación/edición
     *
     * @param string $cedula Cédula
     * @param string $email Correo electrónico (opcional)
     * @param string $password Contraseña (opcional)
     * @param string $role Rol
     * @param string $section Sección (opcional)
     * @return array|true Array de errores o true si es válido
     */
    public static function validateUserData($cedula, $email = '', $password = '', $role = 'Usuario', $section = '')
    {
        $errors = [];

        // Cédula es obligatoria y debe contener solo números
        if (empty($cedula)) {
            $errors['cedula'] = sprintf(self::$messages['required'], 'cédula');
        } elseif (!preg_match('/^\d+$/', $cedula)) {
            $errors['cedula'] = 'La cédula solo debe contener números.';
        }

        // Correo es opcional, pero si se proporciona debe ser válido
        if (!empty($email) && !self::validateEmail($email)) {
            $errors['email'] = self::$messages['email'];
        }

        // Contraseña es opcional, pero si se proporciona debe ser válida
        if (!empty($password)) {
            if (!self::validatePasswordStrength($password)) {
                $errors['password'] = self::$messages['password_strength'];
            }
        }

        // Validar rol
        $validRoles = ['Usuario', 'Docente', 'Administrador'];
        if (!in_array($role, $validRoles)) {
            $errors['role'] = 'Rol inválido.';
        }

        // Validar sección si se proporciona
        if (!empty($section) && !self::validateSectionFormat($section)) {
            $errors['section'] = self::$messages['section_format'];
        }

        return empty($errors) ? true : $errors;
    }

    /**
     * Validar formato de sección
     *
     * @param string $section Sección
     * @return bool
     */
    public static function validateSectionFormat($section)
    {
        return preg_match('/^[0-9]+(,[0-9]+)*$/', $section);
    }

    /**
     * Validar datos de unidad
     *
     * @param string $slug Slug de la unidad
     * @param string $title Título de la unidad
     * @param string $description Descripción (opcional)
     * @param int $order Orden (opcional)
     * @return array|true Array de errores o true si es válido
     */
    public static function validateUnitData($slug, $title, $description = '', $order = 0)
    {
        $errors = [];

        if (empty($title)) {
            $errors['title'] = sprintf(self::$messages['required'], 'título');
        }

        // Slug es opcional, pero si se proporciona debe ser válido
        if (!empty($slug) && !self::validateSlugFormat($slug)) {
            $errors['slug'] = self::$messages['slug_format'];
        }

        return empty($errors) ? true : $errors;
    }

    /**
     * Validar formato de slug
     *
     * @param string $slug Slug
     * @return bool
     */
    public static function validateSlugFormat($slug)
    {
        return preg_match('/^[a-z0-9\-]+$/', $slug);
    }

    /**
     * Validar datos de evaluación
     *
     * @param string $key Clave de la evaluación
     * @param string $title Título
     * @param array $data Datos adicionales
     * @return array|true Array de errores o true si es válido
     */
    public static function validateEvaluationData($key, $title, $data = [])
    {
        $errors = [];

        if (empty($key)) {
            $errors['key'] = sprintf(self::$messages['required'], 'clave');
        }

        if (empty($title)) {
            $errors['title'] = sprintf(self::$messages['required'], 'título');
        }

        return empty($errors) ? true : $errors;
    }

    /**
     * Validar datos de contenido teórico
     *
     * @param string $type Tipo de contenido
     * @param string $title Título
     * @param string $content Contenido (para texto)
     * @param string $url URL (para otros tipos)
     * @return array|true Array de errores o true si es válido
     */
    public static function validateContentData($type, $title, $content = '', $url = '')
    {
        $errors = [];

        $validTypes = ['texto', 'imagen', 'documento', 'video', 'enlace'];

        if (!in_array($type, $validTypes)) {
            $errors['type'] = 'Tipo de contenido inválido.';
        }

        if (empty($title)) {
            $errors['title'] = sprintf(self::$messages['required'], 'título');
        }

        // Para contenido de tipo texto, el contenido es obligatorio
        if ($type === 'texto' && empty($content)) {
            $errors['content'] = sprintf(self::$messages['required'], 'contenido');
        }

        // Para otros tipos, la URL es obligatoria
        if ($type !== 'texto' && empty($url)) {
            $errors['url'] = sprintf(self::$messages['required'], 'URL');
        } elseif (!empty($url) && !self::validateUrl($url)) {
            $errors['url'] = self::$messages['url'];
        }

        return empty($errors) ? true : $errors;
    }

    /**
     * Validar formato de URL
     *
     * @param string $url URL
     * @return bool
     */
    public static function validateUrl($url)
    {
        return filter_var($url, FILTER_VALIDATE_URL);
    }

    /**
     * Sanitizar entrada de texto para prevenir XSS
     *
     * @param string $input Entrada de texto
     * @return string Texto sanitizado
     */
    public static function sanitizeText($input)
    {
        return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
    }

    /**
     * Sanitizar entrada para uso en HTML
     *
     * @param string $input Entrada de texto
     * @return string Texto seguro para HTML
     */
    public static function sanitizeHtml($input)
    {
        return htmlspecialchars($input, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    /**
     * Sanitizar entrada para uso en consultas SQL (prevenir inyección SQL)
     *
     * @param string $input Entrada de texto
     * @return string Texto seguro para SQL
     */
    public static function sanitizeSql($input)
    {
        // Nota: Esto es un complemento, no un reemplazo para consultas preparadas
        return addslashes($input);
    }

    /**
     * Validar que un valor sea único en la base de datos
     *
     * @param PDO $db Conexión a la base de datos
     * @param string $table Tabla
     * @param string $column Columna
     * @param string $value Valor
     * @param string $excludeId ID a excluir (para ediciones)
     * @return bool
     */
    public static function validateUnique($db, $table, $column, $value, $excludeId = null)
    {
        try {
            $query = "SELECT COUNT(*) FROM $table WHERE $column = :value";
            $params = [':value' => $value];

            if ($excludeId !== null) {
                $query .= " AND id != :excludeId";
                $params[':excludeId'] = $excludeId;
            }

            $stmt = $db->prepare($query);
            $stmt->execute($params);

            return $stmt->fetchColumn() === 0;
        } catch (PDOException $e) {
            error_log("Error validando unicidad: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtener mensaje de error
     *
     * @param string $key Clave del mensaje
     * @return string Mensaje de error
     */
    public static function getMessage($key)
    {
        return self::$messages[$key] ?? 'Error de validación.';
    }

    /**
     * Establecer mensaje de error personalizado
     *
     * @param string $key Clave del mensaje
     * @param string $message Mensaje personalizado
     */
    public static function setMessage($key, $message)
    {
        self::$messages[$key] = $message;
    }
}
