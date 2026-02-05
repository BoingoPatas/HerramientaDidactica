<?php


require_once 'app/controller/ControladorContenido.php';

try {
    $controller = new ContentController();
    $controller->checkCode();
} catch (Exception $e) {
    // Responder con JSON de error para que el frontend lo gestione
    header('Content-Type: application/json; charset=utf-8');
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Error de conexión. Por favor, intenta de nuevo.', 'feedback' => ['Error de conexión. Por favor, intenta de nuevo.']]);
    // Registrar detalle técnico en el log
    require_once 'app/model/Registrador.php';
    Registrador::log($_SESSION['usuario'] ?? 'anonimo', $_SESSION['rol'] ?? 'anonimo', 'error', 'check_code DB exception: ' . $e->getMessage());
}
?>
