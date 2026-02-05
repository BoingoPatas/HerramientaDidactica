<?php
/**
 * Librería de validación de sintaxis para código C y PSeInt
 * Proporciona funciones para validar sintaxis y comparar código
 */
class ValidacionSintaxis {
    /**
     * Valida sintaxis básica de código C
     *
     * @param string $code Código a validar
     * @return array Array con errores encontrados
     */
    public static function validarSintaxisC($code) {
        $errores = [];
        $lineas = explode("\n", $code);

        // Validar declaraciones de variables
        $variablesDeclaradas = [];
        $lineaNum = 1;

        foreach ($lineas as $linea) {
            $linea = trim($linea);

            // Ignorar líneas vacías y comentarios
            if (empty($linea) || strpos($linea, '//') === 0) {
                $lineaNum++;
                continue;
            }

            // Validar declaraciones de variables
            if (preg_match('/^(int|float|char|double)\s+([a-zA-Z_][a-zA-Z0-9_]*)\s*(?:=\s*([^;]*))?\s*;$/', $linea, $matches)) {
                $tipo = $matches[1];
                $nombre = $matches[2];
                $valor = $matches[3] ?? null;

                $variablesDeclaradas[$nombre] = [
                    'tipo' => $tipo,
                    'linea' => $lineaNum,
                    'valor' => $valor
                ];
            }
            // Validar punto y coma
            else if (!preg_match('/[;{}]$/', $linea) &&
                    !preg_match('/^(if|else|for|while|do)\s*\(?.*[^{;]$/', $linea) &&
                    !preg_match('/^\s*\)\s*$/', $linea)) {
                $errores[] = [
                    'linea' => $lineaNum,
                    'mensaje' => 'Falta punto y coma (;) al final de la declaración',
                    'tipo' => 'sintaxis'
                ];
            }

            $lineaNum++;
        }

        // Validar que todas las variables usadas estén declaradas
        preg_match_all('/\b([a-zA-Z_][a-zA-Z0-9_]*)\b/', $code, $variablesUsadas);
        foreach ($variablesUsadas[1] as $variable) {
            if (!isset($variablesDeclaradas[$variable]) &&
                !in_array($variable, ['if', 'else', 'for', 'while', 'do', 'return', 'int', 'float', 'char', 'double', 'printf', 'scanf'])) {
                $errores[] = [
                    'linea' => 0,
                    'mensaje' => "Variable '$variable' no declarada",
                    'tipo' => 'variable'
                ];
            }
        }

        return $errores;
    }

    /**
     * Valida sintaxis básica de código PSeInt
     *
     * @param string $code Código a validar
     * @return array Array con errores encontrados
     */
    public static function validarSintaxisPSeInt($code) {
        $errores = [];
        $lineas = explode("\n", $code);
        $lineaNum = 1;
        $variablesDeclaradas = [];
        $estructurasAbiertas = [];
        $esperandoFin = false;

        foreach ($lineas as $linea) {
            $lineaOriginal = $linea;
            $linea = trim($linea);

            // Ignorar líneas vacías y comentarios
            if (empty($linea) || strpos($linea, '//') === 0 || strpos($linea, '/*') === 0) {
                $lineaNum++;
                continue;
            }

            // Validar declaraciones de variables en PSeInt
            if (preg_match('/^Definir\s+([a-zA-Z_][a-zA-Z0-9_]*)\s+Como\s+(Entero|Real|Caracter|Logico|Cadena)\s*$/', $linea, $matches)) {
                $variablesDeclaradas[$matches[1]] = [
                    'tipo' => $matches[2],
                    'linea' => $lineaNum
                ];
            }
            // Validar asignaciones
            else if (preg_match('/^([a-zA-Z_][a-zA-Z0-9_]*)\s*<-\s*(.+)$/', $linea, $matches)) {
                $variable = $matches[1];
                if (!isset($variablesDeclaradas[$variable])) {
                    $errores[] = [
                        'linea' => $lineaNum,
                        'mensaje' => "Variable '$variable' no declarada",
                        'tipo' => 'variable'
                    ];
                }
            }
            // Validar estructuras de control - apertura
            else if (preg_match('/^(Si|Para|Mientras|Repetir|Segun)\s(.*)$/', $linea, $matches)) {
                $estructura = $matches[1];
                $condicion = trim($matches[2]);

                // Validar condiciones específicas
                if ($estructura === 'Si' && empty($condicion)) {
                    $errores[] = [
                        'linea' => $lineaNum,
                        'mensaje' => 'La estructura Si requiere una condición',
                        'tipo' => 'sintaxis'
                    ];
                } else if ($estructura === 'Para' && !preg_match('/\b([a-zA-Z_][a-zA-Z0-9_]*)\s*<-\s*([^;]+)\s+Hasta\s*([^;]+)$/', $condicion)) {
                    $errores[] = [
                        'linea' => $lineaNum,
                        'mensaje' => 'Estructura Para requiere formato: variable <- inicio Hasta fin',
                        'tipo' => 'sintaxis'
                    ];
                }

                $estructurasAbiertas[] = $estructura;
                $esperandoFin = true;
            }
            // Validar estructuras de control - cierre
            else if (preg_match('/^(FinSi|FinPara|FinMientras|FinSegun)$/', $linea, $matches)) {
                $estructuraFin = $matches[1];
                $estructuraApertura = str_replace('Fin', '', $estructuraFin);

                if (empty($estructurasAbiertas) || end($estructurasAbiertas) !== $estructuraApertura) {
                    $errores[] = [
                        'linea' => $lineaNum,
                        'mensaje' => "Fin$estructuraApertura inesperado o sin apertura correspondiente",
                        'tipo' => 'sintaxis'
                    ];
                } else {
                    array_pop($estructurasAbiertas);
                    $esperandoFin = !empty($estructurasAbiertas);
                }
            }
            // Validar llamadas a procedimientos/funciones
            else if (preg_match('/^([a-zA-Z_][a-zA-Z0-9_]*)\s*\(.*\)$/', $linea, $matches)) {
                // Validación básica de llamadas a procedimientos
            }
            // Validar final de línea para declaraciones
            else if (!$esperandoFin && !empty($linea) &&
                    !preg_match('/^[}\s]*$/', $linea) &&
                    !preg_match('/^\s*(Sino|DeOtroModo)\s.*$/', $linea)) {
                $errores[] = [
                    'linea' => $lineaNum,
                    'mensaje' => 'Falta punto y coma (;) al final de la declaración',
                    'tipo' => 'sintaxis'
                ];
            }

            $lineaNum++;
        }

        // Verificar estructuras sin cerrar
        if (!empty($estructurasAbiertas)) {
            foreach ($estructurasAbiertas as $estructura) {
                $errores[] = [
                    'linea' => 0,
                    'mensaje' => "Estructura $estructura sin cerrar (falta Fin$estructura)",
                    'tipo' => 'sintaxis'
                ];
            }
        }

        return $errores;
    }

    /**
     * Detecta el lenguaje de programación (C o PSeInt)
     *
     * @param string $code Código a analizar
     * @return string 'C' o 'PSeInt'
     */
    public static function detectarLenguaje($code) {
        // Patrones típicos de cada lenguaje
        $patronesC = [
            '/\b(int|float|char|double)\b/',
            '/#include\s*<[^>]+>/',
            '/printf\s*\(/',
            '/scanf\s*\(/',
            '/\bmain\s*\(/',
            '/\breturn\s+[^;]+;/'
        ];

        $patronesPSeInt = [
            '/\bDefinir\b/',
            '/\bComo\b/',
            '/<-\s*/',
            '/\bProceso\b/',
            '/\bFinProceso\b/',
            '/\bEscribir\b/',
            '/\bLeer\b/'
        ];

        $esC = false;
        $esPSeInt = false;

        foreach ($patronesC as $patron) {
            if (preg_match($patron, $code)) {
                $esC = true;
                break;
            }
        }

        foreach ($patronesPSeInt as $patron) {
            if (preg_match($patron, $code)) {
                $esPSeInt = true;
                break;
            }
        }

        // Si ambos son verdaderos, priorizar PSeInt ya que es más específico
        if ($esPSeInt) {
            return 'PSeInt';
        } elseif ($esC) {
            return 'C';
        }

        // Si no se detecta claramente, usar heurística adicional
        if (strpos($code, '<-') !== false || strpos($code, 'Definir') !== false) {
            return 'PSeInt';
        } elseif (strpos($code, ';') !== false && strpos($code, '(') !== false) {
            return 'C';
        }

        // Por defecto, asumir C
        return 'C';
    }

    /**
     * Compara código de estudiante con solución esperada
     *
     * @param string $studentCode Código del estudiante
     * @param string $solutionCode Código solución
     * @return array Array con resultados de comparación
     */
    public static function compararCodigo($studentCode, $solutionCode) {
        $resultado = [
            'similaridad' => 0,
            'coincidencias' => [],
            'diferencias' => [],
            'puntuacion' => 0
        ];

        // Normalizar código
        $studentNormalized = self::normalizarCodigo($studentCode);
        $solutionNormalized = self::normalizarCodigo($solutionCode);

        // Comparar líneas
        $studentLines = explode("\n", $studentNormalized);
        $solutionLines = explode("\n", $solutionNormalized);

        $maxLines = max(count($studentLines), count($solutionLines));
        $coincidencias = 0;

        for ($i = 0; $i < $maxLines; $i++) {
            $studentLine = $studentLines[$i] ?? '';
            $solutionLine = $solutionLines[$i] ?? '';

            if ($studentLine === $solutionLine) {
                $coincidencias++;
                $resultado['coincidencias'][] = [
                    'linea' => $i + 1,
                    'contenido' => $studentLine
                ];
            } else {
                $resultado['diferencias'][] = [
                    'linea' => $i + 1,
                    'esperado' => $solutionLine,
                    'estudiante' => $studentLine
                ];
            }
        }

        // Calcular similitud
        $resultado['similaridad'] = $maxLines > 0 ? ($coincidencias / $maxLines) * 100 : 0;
        $resultado['puntuacion'] = min(100, round($resultado['similaridad'] * 1.5)); // Máximo 100 puntos

        return $resultado;
    }

    /**
     * Normaliza código para comparación
     *
     * @param string $code Código a normalizar
     * @return string Código normalizado
     */
    private static function normalizarCodigo($code) {
        // Eliminar comentarios
        $code = preg_replace('/\/\/.*$/m', '', $code);
        $code = preg_replace('/\/\*.*?\*\//s', '', $code);

        // Normalizar espacios y saltos de línea
        $code = preg_replace('/\s+/', ' ', $code);
        $code = trim($code);

        // Convertir a minúsculas para comparación case-insensitive
        $code = strtolower($code);

        return $code;
    }

    /**
     * Genera rúbrica automática basada en solución esperada
     *
     * @param string $solutionCode Código solución
     * @return array Rúbrica generada
     */
    public static function generarRubricaAutomatica($solutionCode) {
        $rubrica = [];
        $lineas = explode("\n", $solutionCode);
        $lineaNum = 1;

        foreach ($lineas as $linea) {
            $linea = trim($linea);

            // Ignorar líneas vacías y comentarios
            if (empty($linea) || strpos($linea, '//') === 0) {
                $lineaNum++;
                continue;
            }

            // Generar criterios de rúbrica
            if (preg_match('/^(int|float|char|double)\s+([a-zA-Z_][a-zA-Z0-9_]*)\s*=\s*([^;]+);$/', $linea, $matches)) {
                $rubrica[] = [
                    'criterio' => "Declarar variable {$matches[2]} de tipo {$matches[1]} con valor {$matches[3]}",
                    'puntos' => 20,
                    'tipo' => 'declaracion'
                ];
            }
            else if (preg_match('/^([a-zA-Z_][a-zA-Z0-9_]*)\s*=\s*([^;]+);$/', $linea, $matches)) {
                $rubrica[] = [
                    'criterio' => "Asignar valor {$matches[2]} a variable {$matches[1]}",
                    'puntos' => 15,
                    'tipo' => 'asignacion'
                ];
            }
            else if (preg_match('/^if\s*\(([^)]+)\)$/', $linea, $matches)) {
                $rubrica[] = [
                    'criterio' => "Estructura condicional con condición {$matches[1]}",
                    'puntos' => 25,
                    'tipo' => 'condicional'
                ];
            }
            else if (preg_match('/^for\s*\(([^;]+);\s*([^;]+);\s*([^)]+)\)$/', $linea, $matches)) {
                $rubrica[] = [
                    'criterio' => "Estructura de bucle for con inicialización {$matches[1]}, condición {$matches[2]} e incremento {$matches[3]}",
                    'puntos' => 30,
                    'tipo' => 'bucle'
                ];
            }

            $lineaNum++;
        }

        // Si no se generaron criterios automáticos, usar criterios genéricos
        if (empty($rubrica)) {
            $rubrica = [
                [
                    'criterio' => 'Estructura general del código',
                    'puntos' => 40,
                    'tipo' => 'estructura'
                ],
                [
                    'criterio' => 'Declaraciones de variables',
                    'puntos' => 30,
                    'tipo' => 'declaracion'
                ],
                [
                    'criterio' => 'Lógica y algoritmos',
                    'puntos' => 30,
                    'tipo' => 'logica'
                ]
            ];
        }

        return $rubrica;
    }
}
?>
