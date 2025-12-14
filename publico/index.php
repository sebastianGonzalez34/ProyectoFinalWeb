<?php 
require_once '../includes/config.php';
require_once '../includes/database.php';
require_once '../includes/sanitize.php';

$sanitize = new Sanitize();

$usuario_logueado = isset($_SESSION['user_id']);
$nombre_usuario = '';

if ($usuario_logueado) {
    $nombre = $_SESSION['user_nombre'] ?? '';
    $apellido = $_SESSION['user_apellido'] ?? '';
    $nombre_usuario = $sanitize->cleanInput($nombre . ' ' . $apellido);
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Portal Público - HelpDesk</title>
    <link rel="stylesheet" href="../css/styles.css">
</head>
<body>
    <header class="header">
        <div class="container">
            <nav class="navbar">
                <div class="logo">
                    <h2>HelpDesk - Portal Público</h2>
                </div>
                <ul class="nav-menu">
                    <li><a href="index.php">Inicio</a></li>
                    <?php if ($usuario_logueado): ?>
                        <li><a href="crear_ticket.php">Crear Ticket</a></li>
                        <li><a href="mis_tickets.php">Mis Tickets</a></li>
                        <li><a href="cambiar_password.php">Cambiar Contraseña</a></li>
                        <li><a href="logout.php" class="btn btn-logout" style="color: white;">Cerrar Sesión</a></li>
                    <?php else: ?>
                        <li><a href="registro.php" class="btn btn-outline">Registrarse</a></li>
                        <li><a href="login.php" class="btn btn-primary">Iniciar Sesión</a></li>
                    <?php endif; ?>
                </ul>
            </nav>
        </div>
    </header>

    <main class="container">
        <?php if ($usuario_logueado): ?>
            <div class="welcome-message">
                <h2>¡Bienvenido, <?php echo $nombre_usuario; ?>!</h2>
                <p>Has iniciado sesión correctamente. ¿Qué te gustaría hacer?</p>
                <div class="form-actions" style="justify-content: center; margin-top: 2rem;">
                    <a href="crear_ticket.php" class="btn btn-primary">Crear Nuevo Ticket</a>
                    <a href="mis_tickets.php" class="btn btn-secondary">Ver Mis Tickets</a>
                    <a href="cambiar_password.php" class="btn btn-outline" style="background: rgba(255,255,255,0.2); border-color: white;">Cambiar Contraseña</a>
                </div>
            </div>
        <?php else: ?>
            <div class="hero-section" style="text-align: center; padding: 4rem 0;">
                <h1 style="font-size: 2.5rem; margin-bottom: 1rem; color: #2c3e50;">Bienvenido al Sistema de HelpDesk</h1>
                <p style="font-size: 1.2rem; color: #666; margin-bottom: 2rem;">Sistema de gestión de tickets y soporte técnico</p>
                <div class="form-actions" style="justify-content: center;">
                    <a href="registro.php" class="btn btn-primary">Registrarse</a>
                    <a href="login.php" class="btn btn-secondary">Iniciar Sesión</a>
                </div>
            </div>
        <?php endif; ?>

        <div class="news-section">
            <h2>Noticias y Novedades</h2>
            
            <div class="news-grid">
                <div class="news-card">
                    <h3>Importancia de un Sistema HelpDesk</h3>
                    <p>Un sistema de mesa de ayuda centraliza todas las solicitudes de soporte, mejora los tiempos de respuesta y proporciona métricas valiosas para la mejora continua del servicio.</p>
                </div>
                
                <div class="news-card">
                    <h3>Nuevas Funcionalidades</h3>
                    <p>Hemos implementado encuestas de satisfacción para medir la calidad de nuestro servicio y mejorar continuamente.</p>
                </div>
                
                <div class="news-card">
                    <h3>Soporte 24/7</h3>
                    <p>Nuestro equipo de soporte está disponible para atender sus consultas e incidentes en cualquier momento.</p>
                </div>
            </div>
        </div>
    </main>
</body>
</html>