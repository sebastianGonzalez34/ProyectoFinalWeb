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
    <!-- QUITAR EL STYLE TEMPORAL DE DEBUG -->
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
                        <li><a href="logout.php" class="btn btn-logout">Cerrar Sesión</a></li>
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
                <h2>¡Bienvenido, <?php echo htmlspecialchars($nombre_usuario); ?>!</h2>
                <p>Has iniciado sesión correctamente. ¿Qué te gustaría hacer?</p>
                <!-- Versión con íconos pero sin efectos raros -->
<div class="form-actions">
    <a href="crear_ticket.php" class="btn btn-primary">
        <span class="btn-icon">📝</span>
        <span>Crear Nuevo Ticket</span>
    </a>
    <a href="mis_tickets.php" class="btn btn-secondary">
        <span class="btn-icon">📋</span>
        <span>Ver Mis Tickets</span>
    </a>
    <a href="cambiar_password.php" class="btn btn-outline">
        <span class="btn-icon">🔐</span>
        <span>Cambiar Contraseña</span>
    </a>
</div>
</div>
            </div>
        <?php else: ?>
            <div class="hero-section">
                <h1>Bienvenido al Sistema de HelpDesk</h1>
                <p>Sistema de gestión de tickets y soporte técnico</p>
                <div class="form-actions">
    <a href="registro.php" class="btn btn-primary">
        <span class="btn-icon">📝</span>
        <span>Registrarse</span>
    </a>
    <a href="login.php" class="btn btn-secondary">
        <span>Iniciar Sesión</span>
    </a>
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