<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Acceso - Herramienta Didáctica</title>
    <link rel="stylesheet" href="app/view/css/styles.css?v=1.1">
</head>
<body>
    <div class="auth-container">
        <h1 class="welcome-title">Herramienta Didáctica del Saber Algorítmica y Programación del PNFI</h1>
        <img src="app/view/img/logo%20departamento.jpeg" alt="Logo del Departamento" class="logo-image">
        <img src="app/view/img/LOGO UPTP.png" alt="Logo UPTP" class="logo-image">
        <h2>Inicio de Sesión</h2>
        <form action="index.php" method="POST">
            <input type="hidden" name="action" value="login">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>">
            <input type="text" name="nombre_usuario_login" placeholder="Nombre de usuario" required>
            <input type="password" name="contrasena_login" placeholder="Contraseña" required>
            <button type="submit">Iniciar Sesión</button>
        </form>



        <?php if (isset($error_message)): ?>
            <p class="error-message"><?php echo htmlspecialchars($error_message); ?></p>
        <?php endif; ?>
    </div>
</body>
</html>
