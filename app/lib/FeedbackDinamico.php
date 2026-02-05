<?php
/**
 * Sistema de Feedback Dinámico para ejercicios prácticos
 * Proporciona análisis automático de código y feedback personalizado
 * para cualquier ejercicio, no solo los predefinidos
 */
require_once 'ValidacionSintaxis.php';

class FeedbackDinamico {

    /**
     * Genera feedback completo para un ejercicio
     *
     * @param string $studentCode Código del estudiante
     * @param string $solutionCode Código solución esperado
     * @param string|null $lenguaje Lenguaje de programación (C, PSeInt) - se detecta automáticamente si no se especifica
     * @return array Array con resultados del análisis y feedback
     */
    public static function generarFeedback($studentCode, $solutionCode, $lenguaje = null) {
        // 1. Detectar lenguaje automáticamente si no se especifica
        $lenguaje = $lenguaje ?? ValidacionSintaxis::detectarLenguaje($studentCode);

        // 2. Validar sintaxis del código del estudiante
        $syntaxErrors = self::validarSintaxis($studentCode, $lenguaje);

        // 3. Si hay errores de sintaxis, devolverlos inmediatamente
        if (!empty($syntaxErrors)) {
            return [
                'success' => false,
                'feedback' => self::formatearErroresSintaxis($syntaxErrors),
                'score' => 0,
                'rubric' => [],
                'lenguaje' => $lenguaje,
                'tipo_errores' => 'sintaxis',
                'detalles' => $syntaxErrors
            ];
        }

        // 4. Comparar código del estudiante con solución esperada
        $comparison = ValidacionSintaxis::compararCodigo($studentCode, $solutionCode);

        // 5. Generar rúbrica automática basada en la solución
        $rubrica = ValidacionSintaxis::generarRubricaAutomatica($solutionCode);

        // 6. Evaluar criterios de rúbrica
        $evaluacionRubrica = self::evaluarRubrica($comparison, $rubrica);

        // 7. Generar mensajes de feedback
        $feedback = self::generarMensajesFeedback($comparison, $evaluacionRubrica);

        // 8. Calcular puntuación final
        $puntuacionFinal = self::calcularPuntuacionFinal($comparison, $evaluacionRubrica);

        return [
            'success' => $comparison['similaridad'] >= 70, // 70% de similitud como umbral
            'feedback' => $feedback,
            'score' => $puntuacionFinal,
            'rubric' => $evaluacionRubrica,
            'lenguaje' => $lenguaje,
            'tipo_errores' => 'logica',
            'similaridad' => $comparison['similaridad'],
            'detalles' => [
                'coincidencias' => $comparison['coincidencias'],
                'diferencias' => $comparison['diferencias']
            ]
        ];
    }

    /**
     * Valida sintaxis del código según el lenguaje
     *
     * @param string $code Código a validar
     * @param string $lenguaje Lenguaje de programación
     * @return array Array de errores de sintaxis
     */
    private static function validarSintaxis($code, $lenguaje) {
        if ($lenguaje === 'C') {
            return ValidacionSintaxis::validarSintaxisC($code);
        } elseif ($lenguaje === 'PSeInt') {
            return ValidacionSintaxis::validarSintaxisPSeInt($code);
        } else {
            // Lenguaje desconocido, intentar con C por defecto
            return ValidacionSintaxis::validarSintaxisC($code);
        }
    }

    /**
     * Formatea errores de sintaxis para mostrar al usuario
     *
     * @param array $syntaxErrors Array de errores de sintaxis
     * @return array Array de mensajes formateados
     */
    private static function formatearErroresSintaxis($syntaxErrors) {
        $mensajes = ['Tu código tiene errores de sintaxis que debes corregir:'];

        foreach ($syntaxErrors as $error) {
            if ($error['linea'] > 0) {
                $mensajes[] = "❌ Línea {$error['linea']}: {$error['mensaje']}";
            } else {
                $mensajes[] = "❌ {$error['mensaje']}";
            }
        }

        return $mensajes;
    }

    /**
     * Evalúa los criterios de rúbrica contra el código del estudiante
     *
     * @param array $comparison Resultado de comparación de código
     * @param array $rubrica Rúbrica automática generada
     * @return array Evaluación de cada criterio
     */
    private static function evaluarRubrica($comparison, $rubrica) {
        $evaluacion = [];

        foreach ($rubrica as $criterio) {
            $cumplido = self::verificarCriterio($comparison['coincidencias'], $criterio);

            $evaluacion[] = [
                'criterio' => $criterio['criterio'],
                'puntos_total' => $criterio['puntos'],
                'puntos_obtenidos' => $cumplido ? $criterio['puntos'] : 0,
                'cumplido' => $cumplido,
                'tipo' => $criterio['tipo']
            ];
        }

        return $evaluacion;
    }

    /**
     * Verifica si un criterio específico de rúbrica se cumple
     *
     * @param array $coincidencias Líneas coincidentes
     * @param array $criterio Criterio de rúbrica
     * @return bool True si el criterio se cumple
     */
    private static function verificarCriterio($coincidencias, $criterio) {
        // Lógica simplificada: si la similitud es alta, asumir que los criterios se cumplen
        // En una implementación más avanzada, se podría analizar específicamente cada criterio
        return count($coincidencias) > 0;
    }

    /**
     * Genera mensajes de feedback basados en la comparación y evaluación
     *
     * @param array $comparison Resultado de comparación
     * @param array $evaluacionRubrica Evaluación de criterios
     * @return array Array de mensajes de feedback
     */
    private static function generarMensajesFeedback($comparison, $evaluacionRubrica) {
        $mensajes = [];

        // Mensaje principal basado en similitud
        if ($comparison['similaridad'] >= 90) {
            $mensajes[] = '🎉 ¡Excelente! Tu código es prácticamente idéntico a la solución esperada.';
        } elseif ($comparison['similaridad'] >= 70) {
            $mensajes[] = '👍 ¡Muy bien! Tu código tiene la estructura correcta y funciona correctamente.';
        } elseif ($comparison['similaridad'] >= 50) {
            $mensajes[] = '💪 ¡Buen progreso! Tu código está en el camino correcto, pero necesita algunos ajustes.';
        } else {
            $mensajes[] = '🔧 Tu código necesita más trabajo. Revisa los siguientes aspectos:';
        }

        // Detalles de criterios cumplidos y no cumplidos
        $cumplidos = array_filter($evaluacionRubrica, fn($c) => $c['cumplido']);
        $noCumplidos = array_filter($evaluacionRubrica, fn($c) => !$c['cumplido']);

        if (!empty($cumplidos)) {
            $mensajes[] = '✅ Aspectos correctos:';
            foreach ($cumplidos as $criterio) {
                $mensajes[] = "   • {$criterio['criterio']} (+{$criterio['puntos_obtenidos']} puntos)";
            }
        }

        if (!empty($noCumplidos)) {
            $mensajes[] = '❌ Aspectos a mejorar:';
            foreach ($noCumplidos as $criterio) {
                $mensajes[] = "   • {$criterio['criterio']}";
            }
        }

        // Información adicional sobre similitud
        $mensajes[] = "📊 Similitud con la solución: {$comparison['similaridad']}%";

        return $mensajes;
    }

    /**
     * Calcula la puntuación final basada en similitud y criterios de rúbrica
     *
     * @param array $comparison Resultado de comparación
     * @param array $evaluacionRubrica Evaluación de criterios
     * @return int Puntuación final (0-100)
     */
    private static function calcularPuntuacionFinal($comparison, $evaluacionRubrica) {
        // Combinar similitud de código con evaluación de criterios
        $similitudScore = $comparison['similaridad'];
        $rubricaScore = 0;

        if (!empty($evaluacionRubrica)) {
            $totalRubrica = array_sum(array_column($evaluacionRubrica, 'puntos_total'));
            $obtenidoRubrica = array_sum(array_column($evaluacionRubrica, 'puntos_obtenidos'));

            if ($totalRubrica > 0) {
                $rubricaScore = ($obtenidoRubrica / $totalRubrica) * 100;
            }
        }

        // Ponderación: 60% similitud de código, 40% criterios de rúbrica
        $puntuacionFinal = round(($similitudScore * 0.6) + ($rubricaScore * 0.4));

        return min(100, max(0, $puntuacionFinal));
    }

    /**
     * Genera un resumen ejecutivo del feedback
     *
     * @param array $resultado Resultado completo del análisis
     * @return string Resumen ejecutivo
     */
    public static function generarResumen($resultado) {
        $estado = $resultado['success'] ? '✅ APROBADO' : '❌ REQUIERE CORRECCIÓN';
        $similitud = $resultado['similaridad'] ?? 0;
        $puntuacion = $resultado['score'] ?? 0;

        $resumen = "Estado: {$estado}\n";
        $resumen .= "Puntuación: {$puntuacion}/100\n";
        $resumen .= "Similitud: {$similitud}%\n";
        $resumen .= "Lenguaje detectado: {$resultado['lenguaje']}\n";

        if ($resultado['tipo_errores'] === 'sintaxis') {
            $resumen .= "Tipo: Errores de sintaxis\n";
        } else {
            $resumen .= "Tipo: Análisis lógico\n";
        }

        return $resumen;
    }

    /**
     * Analiza un ejercicio completo y proporciona recomendaciones para mejorarlo
     *
     * @param string $studentCode Código del estudiante
     * @param string $solutionCode Código solución
     * @return array Análisis completo con recomendaciones
     */
    public static function analizarEjercicioCompleto($studentCode, $solutionCode) {
        $resultado = self::generarFeedback($studentCode, $solutionCode);

        // Añadir recomendaciones específicas
        $recomendaciones = self::generarRecomendaciones($resultado);

        $resultado['recomendaciones'] = $recomendaciones;
        $resultado['resumen'] = self::generarResumen($resultado);

        return $resultado;
    }

    /**
     * Genera recomendaciones específicas para mejorar el código
     *
     * @param array $resultado Resultado del análisis
     * @return array Array de recomendaciones
     */
    private static function generarRecomendaciones($resultado) {
        $recomendaciones = [];

        if ($resultado['tipo_errores'] === 'sintaxis') {
            $recomendaciones[] = 'Corrige los errores de sintaxis antes de continuar.';
            $recomendaciones[] = 'Revisa la ortografía de las palabras reservadas y operadores.';
            $recomendaciones[] = 'Asegúrate de que todas las llaves y paréntesis estén correctamente balanceadas.';
        } else {
            $similitud = $resultado['similaridad'] ?? 0;

            if ($similitud < 30) {
                $recomendaciones[] = 'Revisa completamente el algoritmo. Puede que estés usando un enfoque diferente al esperado.';
                $recomendaciones[] = 'Estudia el ejemplo proporcionado y compara con tu código.';
            } elseif ($similitud < 50) {
                $recomendaciones[] = 'Tu lógica está parcialmente correcta. Revisa las variables y operaciones.';
                $recomendaciones[] = 'Asegúrate de que estés usando los tipos de datos correctos.';
            } elseif ($similitud < 70) {
                $recomendaciones[] = 'Estás cerca de la solución. Revisa los detalles menores.';
                $recomendaciones[] = 'Verifica que estés siguiendo exactamente los requisitos del ejercicio.';
            }
        }

        return $recomendaciones;
    }
}
?>
