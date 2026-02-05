<?php

/**
 * Messages - Sistema centralizado de mensajes para la aplicación
 *
 * Este archivo contiene todos los mensajes del sistema, incluyendo mensajes de error,
 * mensajes de éxito, mensajes de validación y otros mensajes utilizados en la interfaz.
 */

class Messages
{
    // Mensajes de éxito
    public static $success = [
        'user_created' => 'Usuario creado correctamente.',
        'user_updated' => 'Usuario actualizado correctamente.',
        'user_deleted' => 'Usuario eliminado correctamente.',
        'user_enabled' => 'Usuario habilitado correctamente.',
        'user_disabled' => 'Usuario deshabilitado correctamente.',
        'login_success' => 'Inicio de sesión exitoso.',
        'logout_success' => 'Sesión cerrada correctamente.',
        'registration_success' => 'Registro exitoso. Bienvenido a la plataforma.',
        'password_updated' => 'Contraseña actualizada correctamente.',
        'profile_updated' => 'Perfil actualizado correctamente.',
        'content_created' => 'Contenido creado correctamente.',
        'content_updated' => 'Contenido actualizado correctamente.',
        'content_deleted' => 'Contenido eliminado correctamente.',
        'unit_created' => 'Unidad creada correctamente.',
        'unit_updated' => 'Unidad actualizada correctamente.',
        'unit_deleted' => 'Unidad eliminada correctamente.',
        'evaluation_created' => 'Evaluación creada correctamente.',
        'evaluation_updated' => 'Evaluación actualizada correctamente.',
        'evaluation_deleted' => 'Evaluación eliminada correctamente.',
        'exercise_completed' => 'Ejercicio completado correctamente.',
        'exercise_updated' => 'Ejercicio actualizado correctamente.'
    ];

    // Mensajes de error
    public static $errors = [
        'auth_required' => 'Debes iniciar sesión para acceder a esta función.',
        'access_denied' => 'No tienes permiso para realizar esta acción.',
        'invalid_credentials' => 'Usuario o contraseña incorrectos.',
        'user_exists' => 'El nombre de usuario o correo electrónico ya están registrados.',
        'user_not_found' => 'Usuario no encontrado.',
        'invalid_email' => 'Correo electrónico no válido.',
        'invalid_username' => 'Nombre de usuario no válido.',
        'invalid_password' => 'Contraseña no válida.',
        'password_mismatch' => 'Las contraseñas no coinciden.',
        'weak_password' => 'La contraseña debe tener al menos 8 caracteres, incluyendo mayúscula, minúscula, número y carácter especial.',
        'csrf_invalid' => 'Token CSRF inválido. Por favor, inténtalo de nuevo.',
        'invalid_input' => 'Entrada no válida.',
        'database_error' => 'Error de base de datos. Por favor, inténtalo de nuevo.',
        'server_error' => 'Error del servidor. Por favor, inténtalo de nuevo más tarde.',
        'not_found' => 'Recurso no encontrado.',
        'validation_failed' => 'Validación fallida. Por favor, revisa los campos.',
        'operation_failed' => 'La operación falló. Por favor, inténtalo de nuevo.',
        'unique_constraint' => 'El valor ya está en uso.',
        'invalid_format' => 'Formato no válido.',
        'file_upload_error' => 'Error al subir el archivo.',
        'file_too_large' => 'El archivo es demasiado grande.',
        'invalid_file_type' => 'Tipo de archivo no válido.',
        'inactive_account' => 'Tu cuenta ha sido desactivada. Contacta al administrador.'
    ];

    // Mensajes de validación
    public static $validation = [
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

    // Mensajes de información
    public static $info = [
        'welcome' => 'Bienvenido a la Herramienta Didáctica del Saber Algorítmica y Programación del PNFI.',
        'logout_confirm' => '¿Estás seguro de que quieres cerrar sesión?',
        'delete_confirm' => '¿Estás seguro de que quieres eliminar este elemento?',
        'action_confirm' => '¿Estás seguro de que quieres realizar esta acción?',
        'no_results' => 'No se encontraron resultados.',
        'loading' => 'Cargando...',
        'saving' => 'Guardando...',
        'processing' => 'Procesando...',
        'no_data' => 'No hay datos disponibles.',
        'password_requirements' => 'La contraseña debe tener al menos 8 caracteres, incluyendo mayúscula, minúscula, número y carácter especial.'
    ];

    // Mensajes de advertencia
    public static $warnings = [
        'session_expired' => 'Tu sesión ha expirado. Por favor, inicia sesión de nuevo.',
        'inactive_session' => 'Tu sesión está a punto de expirar. ¿Quieres extenderla?',
        'unsaved_changes' => 'Tienes cambios sin guardar. ¿Quieres guardarlos antes de salir?',
        'password_change_recommended' => 'Se recomienda cambiar la contraseña temporal lo antes posible.',
        'account_inactive' => 'Tu cuenta está inactiva. Contacta al administrador para activarla.'
    ];

    /**
     * Obtener un mensaje por su clave
     *
     * @param string $category Categoría del mensaje (success, errors, validation, info, warnings)
     * @param string $key Clave del mensaje
     * @param array $params Parámetros para reemplazar en el mensaje
     * @return string Mensaje formateado
     */
    public static function getMessage($category, $key, $params = [])
    {
        if (!property_exists('Messages', $category)) {
            return 'Mensaje no encontrado.';
        }

        $messages = self::$$category;

        if (!isset($messages[$key])) {
            return 'Mensaje no encontrado.';
        }

        $message = $messages[$key];

        // Reemplazar parámetros en el mensaje
        if (!empty($params)) {
            $message = vsprintf($message, $params);
        }

        return $message;
    }

    /**
     * Obtener un mensaje de éxito
     *
     * @param string $key Clave del mensaje
     * @param array $params Parámetros para reemplazar en el mensaje
     * @return string Mensaje de éxito
     */
    public static function success($key, $params = [])
    {
        return self::getMessage('success', $key, $params);
    }

    /**
     * Obtener un mensaje de error
     *
     * @param string $key Clave del mensaje
     * @param array $params Parámetros para reemplazar en el mensaje
     * @return string Mensaje de error
     */
    public static function error($key, $params = [])
    {
        return self::getMessage('errors', $key, $params);
    }

    /**
     * Obtener un mensaje de validación
     *
     * @param string $key Clave del mensaje
     * @param array $params Parámetros para reemplazar en el mensaje
     * @return string Mensaje de validación
     */
    public static function validation($key, $params = [])
    {
        return self::getMessage('validation', $key, $params);
    }

    /**
     * Obtener un mensaje de información
     *
     * @param string $key Clave del mensaje
     * @param array $params Parámetros para reemplazar en el mensaje
     * @return string Mensaje de información
     */
    public static function info($key, $params = [])
    {
        return self::getMessage('info', $key, $params);
    }

    /**
     * Obtener un mensaje de advertencia
     *
     * @param string $key Clave del mensaje
     * @param array $params Parámetros para reemplazar en el mensaje
     * @return string Mensaje de advertencia
     */
    public static function warning($key, $params = [])
    {
        return self::getMessage('warnings', $key, $params);
    }

    /**
     * Establecer un mensaje personalizado
     *
     * @param string $category Categoría del mensaje
     * @param string $key Clave del mensaje
     * @param string $message Mensaje personalizado
     */
    public static function setMessage($category, $key, $message)
    {
        if (property_exists('Messages', $category)) {
            self::$$category[$key] = $message;
        }
    }

    /**
     * Obtener todos los mensajes de una categoría
     *
     * @param string $category Categoría de mensajes
     * @return array Mensajes de la categoría
     */
    public static function getAllMessages($category)
    {
        if (property_exists('Messages', $category)) {
            return self::$$category;
        }

        return [];
    }
}
