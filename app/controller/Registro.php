<?php
// Endpoint para registrar acciones en bitácora

header('Content-Type: application/json; charset=utf-8');

// CORRECCIÓN: Ruta correcta para Registrador.php
require_once __DIR__ . '/../model/Registrador.php';

if (!isset($_SESSION['usuario'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'No autenticado']);
    exit;
}

$raw = file_get_contents('php://input');
$payload = json_decode($raw, true);
if (!is_array($payload)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'JSON inválido']);
    exit;
}

$csrf = $payload['csrf_token'] ?? '';
if (empty($csrf) || !hash_equals($_SESSION['csrf_token'] ?? '', $csrf)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'CSRF inválido']);
    exit;
}

try {
    $action = trim((string)($payload['action'] ?? ''));
    $detail = trim((string)($payload['detail'] ?? ''));
    if ($action === '') {
        http_response_code(422);
        echo json_encode(['success' => false, 'error' => 'Acción requerida']);
        exit;
    }
    $usuario = $_SESSION['usuario'];
    $rol = $_SESSION['rol'] ?? 'Usuario';
    Registrador::log($usuario, $rol, $action, $detail);
    echo json_encode(['success' => true]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Error interno']);
}
