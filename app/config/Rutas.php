<?php
// Helper simple para generar URLs consistentes en todo el proyecto
function route(string $page, array $params = [], string $hash = ''): string {
    $url = 'index.php?page=' . rawurlencode($page);
    if (!empty($params)) {
        $pairs = [];
        foreach ($params as $k => $v) {
            $pairs[] = rawurlencode($k) . '=' . rawurlencode((string)$v);
        }
        $url .= '&' . implode('&', $pairs);
    }
    if ($hash !== '') $url .= '#' . ltrim($hash, '#');
    return $url;
}

// Helper para rutas de acción (endpoints API)
function action_url(string $action, array $params = []): string {
    $url = 'index.php?action=' . rawurlencode($action);
    if (!empty($params)) {
        $pairs = [];
        foreach ($params as $k => $v) $pairs[] = rawurlencode($k) . '=' . rawurlencode((string)$v);
        $url .= '&' . implode('&', $pairs);
    }
    return $url;
}

?>
