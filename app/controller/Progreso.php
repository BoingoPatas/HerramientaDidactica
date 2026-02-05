<?php

// Endpoint para guardar y consultar progreso por usuario

header('Content-Type: application/json; charset=utf-8');

// --- CORRECCIÓN DE RUTAS ---
// Las rutas deben salir de app/controller/ y subir un nivel (../)
require_once __DIR__ . '/../config/BaseDatos.php';
require_once __DIR__ . '/../model/Registrador.php';

if (!isset($_SESSION['usuario'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'No autenticado']);
    exit;
}

// Validar CSRF para POST (no se usa para GET, pero se mantiene la verificación de seguridad)
if (strtoupper($_SERVER['REQUEST_METHOD']) === 'POST') {
    $raw = file_get_contents('php://input');
    $requestPayload = json_decode($raw, true);
    if (!is_array($requestPayload)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'JSON inválido']);
        exit;
    }
    $csrf = $requestPayload['csrf_token'] ?? '';
    if (empty($csrf) || !hash_equals($_SESSION['csrf_token'] ?? '', $csrf)) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'CSRF inválido']);
        exit;
    }
}

// --- LÓGICA DE PROGRESO (GET request) ---
$db = new Database();
$conn = $db->getConnection();
$userId = $_SESSION['user_id'] ?? 0;

// En una aplicación real, esto se cargaría de un controlador o base de datos.
// Definición de la cantidad MÁXIMA de ejercicios por unidad (sin contar la evaluación)
$unitMax = [
    'variables' => 2,
    'operadores' => 1,
    'condicionales' => 1,
    'bucles' => 1
];

if (!$userId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'ID de usuario no encontrado en la sesión.']);
    exit;
}

// 1. Obtener ejercicios completados (unit y exercise)
$completedByUnit = [];
if ($stmt = $conn->prepare('SELECT u.slug as unit, e.slug as exercise FROM progreso_usuario pu JOIN ejercicios e ON pu.ejercicio_id = e.id JOIN unidades u ON e.unidad_id = u.id WHERE pu.usuario_id = ? AND pu.completado = 1 AND e.tipo = "practica"')) {
    $stmt->bind_param('i', $userId);
    if ($stmt->execute()) {
        $stmt->bind_result($unit, $exercise);
        while ($stmt->fetch()) {
            if (!isset($completedByUnit[$unit])) $completedByUnit[$unit] = [];
            // Almacenar el slug del ejercicio completado
            $completedByUnit[$unit][] = $exercise;
        }
    }
    $stmt->close();
}

// 2. Obtener evaluaciones completadas (intento_usado = 1)
$evaluationsDone = [];
if ($stmt = $conn->prepare('SELECT u.slug as evaluation_key FROM intentos_evaluacion ie JOIN ejercicios e ON ie.ejercicio_id = e.id JOIN unidades u ON e.unidad_id = u.id WHERE ie.usuario_id = ? AND ie.intento_usado = 1 AND e.tipo = "evaluacion"')) {
    $stmt->bind_param('i', $userId);
    if ($stmt->execute()) {
        $stmt->bind_result($evalKey);
        while ($stmt->fetch()) {
            $evaluationsDone[$evalKey] = true;
        }
    }
    $stmt->close();
}

// 3. Calcular progreso por unidad y armar el objeto de salida
$progressOutput = [];
foreach ($unitMax as $unit => $max) {
    // Ejercicios
    $completedExercises = isset($completedByUnit[$unit]) ? array_unique($completedByUnit[$unit]) : [];
    $completedCount = count($completedExercises);

    // Evaluación
    $hasEval = !empty($evaluationsDone[$unit]);
    $evalCount = $hasEval ? 1 : 0;
    
    // El total de ítems es (ejercicios + 1 evaluación)
    $completedTotal = $completedCount + $evalCount;
    $maxTotal = $max + 1; 

    // Cálculo del porcentaje
    $percentage = max(0, min(100, (int)round(($completedTotal / $maxTotal) * 100)));
    
    // Devolver el objeto de progreso completo para el frontend (content.js)
    $progressOutput[$unit] = [
        'percentage' => $percentage,
        'completed_exercises' => array_values($completedExercises), // Array plano de ejercicios completados (ej: ["1", "2"])
        'total_exercises' => $max // Total de ejercicios solamente (sin evaluación)
    ];
}

// 4. Devolver la respuesta JSON con la estructura correcta
echo json_encode(['success' => true, 'progress' => $progressOutput]);
exit;
