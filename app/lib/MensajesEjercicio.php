<?php
/**
 * Librería de mensajes para ejercicios prácticos
 * Proporciona mensajes estandarizados de éxito y error para los ejercicios
 */
class MensajesEjercicio {
    /**
     * Obtiene un mensaje de éxito basado en el tipo de ejercicio
     *
     * @param string $tipoEjercicio Tipo de ejercicio (variables, operadores, condicionales, bucles)
     * @return string Mensaje de éxito formateado
     */
    public static function getMensajeExito($tipoEjercicio) {
        $mensajes = [
            'variables' => '¡Excelente! Has declarado correctamente las variables.',
            'operadores' => '¡Muy bien! Los operadores y cálculos son correctos.',
            'condicionales' => '¡Perfecto! La estructura condicional funciona correctamente.',
            'bucles' => '¡Genial! El bucle está implementado correctamente.',
            'default' => '¡Código correcto!'
        ];
        return $mensajes[$tipoEjercicio] ?? $mensajes['default'];
    }

    /**
     * Obtiene un mensaje de error específico con detalles
     *
     * @param string $tipoError Tipo de error (sintaxis, variable_faltante, valor_incorrecto, etc.)
     * @param array $detalles Detalles para reemplazar en el mensaje
     * @return string Mensaje de error formateado
     */
    public static function getMensajeError($tipoError, $detalles = []) {
        $mensajes = [
            'sintaxis' => 'Error de sintaxis: %s',
            'variable_faltante' => 'Falta declarar la variable: %s',
            'valor_incorrecto' => 'La variable %s debe tener valor: %s',
            'estructura_faltante' => 'Falta la estructura: %s',
            'punto_y_coma' => 'No olvides el punto y coma (;) al final de la declaración.',
            'parentesis' => 'Falta cerrar paréntesis en la condición.',
            'llave' => 'Falta cerrar llave { en el bloque de código.',
            'tipo_dato' => 'El tipo de dato de %s debe ser %s.',
            'default' => 'Revisa tu código e intenta nuevamente.'
        ];

        if (isset($mensajes[$tipoError])) {
            return sprintf($mensajes[$tipoError], ...$detalles);
        }
        return $mensajes['default'];
    }

    /**
     * Obtiene mensajes de feedback detallados para errores de sintaxis en C
     *
     * @param string $errorCode Código de error o tipo de error
     * @return string Mensaje de error específico
     */
    public static function getMensajesSintaxisC($errorCode) {
        $mensajes = [
            'missing_semicolon' => 'Error: Falta punto y coma (;) al final de la declaración.',
            'missing_parenthesis' => 'Error: Falta paréntesis de cierre ).',
            'missing_brace' => 'Error: Falta llave de cierre }.',
            'invalid_variable' => 'Error: Nombre de variable no válido. Solo se permiten letras, números y guión bajo.',
            'type_mismatch' => 'Error: Tipo de dato no coincide con el valor asignado.',
            'undeclared_variable' => 'Error: Variable no declarada.',
            'syntax_error' => 'Error de sintaxis: Revisa la estructura de tu código.'
        ];

        return $mensajes[$errorCode] ?? $mensajes['syntax_error'];
    }

    /**
     * Obtiene mensajes de feedback para ejercicios específicos
     *
     * @param string $ejercicioType Tipo de ejercicio
     * @param string $ejercicioNumber Número de ejercicio
     * @return array Array con mensajes específicos para el ejercicio
     */
    public static function getMensajesEjercicioEspecifico($ejercicioType, $ejercicioNumber) {
        $mensajes = [
            'variables' => [
                '1' => [
                    'success' => '¡Perfecto! Has declarado correctamente la variable edad con valor 25.',
                    'errors' => [
                        'missing_declaration' => 'Falta declarar la variable "edad" de tipo int.',
                        'wrong_value' => 'La variable "edad" debe tener el valor 25.',
                        'missing_semicolon' => 'No olvides el punto y coma (;) al final.'
                    ]
                ],
                '2' => [
                    'success' => '¡Excelente! Has declarado correctamente ambas variables con sus valores.',
                    'errors' => [
                        'missing_float' => 'Falta declarar la variable "precio" de tipo float con valor 15.99.',
                        'missing_char' => 'Falta declarar la variable "inicial" de tipo char con valor \'A\'.',
                        'wrong_char_value' => 'La variable "inicial" debe ser de tipo char y tener valor \'A\'.'
                    ]
                ]
            ],
            'operadores' => [
                '1' => [
                    'success' => '¡Muy bien! Has calculado correctamente la suma de las variables.',
                    'errors' => [
                        'missing_variables' => 'Falta declarar las variables "a" y "b".',
                        'missing_sum' => 'Falta calcular la suma y guardarla en la variable "suma".',
                        'wrong_operation' => 'La operación debe ser a + b.'
                    ]
                ]
            ]
        ];

        return $mensajes[$ejercicioType][$ejercicioNumber] ?? [
            'success' => '¡Código correcto!',
            'errors' => ['default' => 'Revisa tu código e intenta nuevamente.']
        ];
    }
}
?>
