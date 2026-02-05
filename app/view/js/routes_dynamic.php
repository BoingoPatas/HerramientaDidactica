<?php
require_once __DIR__ . '/../../config/Rutas.php';
header('Content-Type: application/javascript; charset=utf-8');
$routes = [
    'home' => route('home'),
    'home_inicio' => route('home', [], 'inicio'),
    'home_manual' => route('home', [], 'manual'),
    'content' => route('content'),
    'evaluation' => route('evaluation'),
    'exercise' => route('exercise'),
    'logout' => action_url('logout'),
    'action_log' => action_url('log'),
    'action_manage' => action_url('manage'),
    'action_progress' => action_url('progress'),
    'action_check_code' => action_url('check_code'),
    'action_evaluation_api' => action_url('evaluation_api'),
];
echo 'window.APP_ROUTES = ' . json_encode($routes, JSON_UNESCAPED_UNICODE) . ';';
?>
