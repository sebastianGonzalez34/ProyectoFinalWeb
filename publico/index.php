<?php
require_once '../includes/config.php';
require_once '../includes/database.php';
require_once '../includes/sanitize.php';

$sanitize = new Sanitize();

$usuario_logueado = isset($_SESSION['user_id']);
$nombre_usuario = '';
$foto_perfil = null;

if ($usuario_logueado) {
    $nombre = $_SESSION['user_nombre'] ?? '';
    $apellido = $_SESSION['user_apellido'] ?? '';
    $nombre_usuario = $sanitize->cleanInput($nombre . ' ' . $apellido);
    
    // Obtener identificación del usuario para buscar la foto
    $identificacion = $_SESSION['user_identificacion'] ?? '';
    
    if ($identificacion) {
        // Buscar archivos de foto de perfil que coincidan con el patrón
        $upload_dir = '../uploads/perfiles/';
        if (is_dir($upload_dir)) {
            // Buscar archivos que empiecen con 'perfil_[identificacion]_'
            $pattern = $upload_dir . 'perfil_' . $identificacion . '_*.{jpg,jpeg,png,gif}';
            $files = glob($pattern, GLOB_BRACE);
            
            if (!empty($files)) {
                // Tomar el archivo más reciente (último del array)
                $foto_perfil = basename(end($files));
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Portal Público - HelpDesk</title>
    <!-- CAMBIO IMPORTANTE: href="../css/style.css" en lugar de styles.css -->
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
                        <li><a href="logout.php" class="btn btn-logout">Cerrar Sesión</a></li>
                    <?php else: ?>
                        <li><a href="registro.php" class="btn btn-outline">Registrarse</a></li>
                        <li><a href="login.php" class="btn btn-primary">Login Colaborador</a></li>
                        <li>
                            <a href="../index.php" class="btn btn-secondary">
                                Admin / Agente
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </nav>
        </div>
    </header>

    <main class="container">
        <?php if ($usuario_logueado): ?>
            <div class="user-profile-container">
                <?php if ($foto_perfil): ?>
                    <img src="../uploads/perfiles/<?php echo htmlspecialchars($foto_perfil); ?>" 
                         alt="Foto de perfil" 
                         class="user-profile-picture">
                <?php else: ?>
                    <div class="default-profile-picture">
                        <?php 
                        // Mostrar iniciales del nombre
                        $iniciales = '';
                        if (!empty($nombre)) {
                            $iniciales .= strtoupper(substr($nombre, 0, 1));
                        }
                        if (!empty($apellido)) {
                            $iniciales .= strtoupper(substr($apellido, 0, 1));
                        }
                        echo $iniciales ?: '👤';
                        ?>
                    </div>
                <?php endif; ?>
                
                <div class="user-profile-info">
                    <h2>¡Bienvenido, <?php echo htmlspecialchars($nombre_usuario); ?>!</h2>
                    <p>Has iniciado sesión correctamente. ¿Qué te gustaría hacer?</p>
                    
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
                        <span>Login Colaborador</span>
                    </a>
                </div>
            </div>
        <?php endif; ?>
        
        <div class="news-section">
            <h2>Noticias y Recursos de Soporte Técnico</h2>
            
            <div class="news-grid">
                <!-- NOTICIA 1 -->
                <div class="news-card">
                    <h3>¿Qué es un Sistema HelpDesk?</h3>
                    <div class="video-container">
                        <iframe src="https://www.youtube.com/embed/a2MLbyoylv8" 
                                title="¿Qué es un HelpDesk?"
                                frameborder="0"
                                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                                allowfullscreen>
                        </iframe>
                    </div>
                    <p>
                        Un sistema HelpDesk permite gestionar solicitudes de soporte de forma
                        organizada, mejorar los tiempos de respuesta y aumentar la satisfacción
                        de los usuarios.
                    </p>
                </div>

                <!-- NOTICIA 2 -->
                <div class="news-card">
                    <h3>Buenas Prácticas en Soporte Técnico</h3>
                    <div class="video-container">
                        <iframe src="https://www.youtube.com/embed/AHMkPvlxgA4?si=KT9wUeNA43fiy-Tl" 
                                title="Buenas prácticas de soporte técnico"
                                frameborder="0"
                                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                                allowfullscreen>
                        </iframe>
                    </div>
                    <p>
                        Implementar buenas prácticas en soporte técnico ayuda a reducir errores,
                        priorizar incidencias y ofrecer un servicio más eficiente.
                    </p>
                </div>

                <!-- NOTICIA 3 -->
                <div class="news-card">
                    <h3>Gestión de Tickets y Atención al Cliente</h3>
                    <div class="video-container">
                        <iframe src="https://www.youtube.com/embed/INfobgPvcrU?si=G4ehxxvwMCYDrY3E" 
                                title="Gestión de tickets de soporte"
                                frameborder="0"
                                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                                allowfullscreen>
                        </iframe>
                    </div>
                    <p>
                        La correcta gestión de tickets mejora la comunicación con los usuarios
                        y permite dar seguimiento a cada solicitud hasta su resolución.
                    </p>
                </div>
            </div>
        </div>
    </main>
</body>
</html>