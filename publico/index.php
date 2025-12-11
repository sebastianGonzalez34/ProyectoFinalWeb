<?php require_once '../includes/config.php'; ?>
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
                    <li><a href="crear_ticket.php">Crear Ticket</a></li>
                    <li><a href="cambiar_password.php">Cambiar Contraseña</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <div class="container">
        <div class="hero-section" style="text-align: center; padding: 4rem 0;">
            <h1>Bienvenido al Sistema de HelpDesk</h1>
            <p>Sistema de gestión de tickets y soporte técnico</p>
            <div style="margin-top: 2rem;">
                <a href="crear_ticket.php" class="btn btn-primary">Crear Nuevo Ticket</a>
            </div>
        </div>

        <div class="news-section" style="margin: 3rem 0;">
            <h2 style="text-align: center; margin-bottom: 2rem;">Noticias y Novedades</h2>
            
            <div class="news-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 2rem;">
                <div class="news-card" style="background: white; padding: 1.5rem; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
                    <h3>Importancia de un Sistema HelpDesk</h3>
                    <p>Un sistema de mesa de ayuda centraliza todas las solicitudes de soporte, mejora los tiempos de respuesta y proporciona métricas valiosas para la mejora continua del servicio.</p>
                </div>
                
                <div class="news-card" style="background: white; padding: 1.5rem; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
                    <h3>Nuevas Funcionalidades</h3>
                    <p>Hemos implementado encuestas de satisfacción para medir la calidad de nuestro servicio y mejorar continuamente.</p>
                </div>
                
                <div class="news-card" style="background: white; padding: 1.5rem; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
                    <h3>Soporte 24/7</h3>
                    <p>Nuestro equipo de soporte está disponible para atender sus consultas e incidentes en cualquier momento.</p>
                </div>
            </div>
        </div>
    </div>
</body>
</html>