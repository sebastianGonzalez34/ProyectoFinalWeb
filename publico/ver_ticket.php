<?php
require_once '../includes/config.php';
require_once '../includes/database.php';
require_once '../includes/sanitize.php';

$sanitize = new Sanitize();

// Verificar si es colaborador
if (!isset($_SESSION['user_id']) || ($_SESSION['user_tipo'] ?? '') !== 'colaborador') {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$ticket_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($ticket_id <= 0) {
    header("Location: mis_tickets.php");
    exit();
}

// Obtener información del ticket
$ticket = null;
$errores = [];

try {
    $database = new Database();
    $db = $database->getConnection();
    
    $query = "SELECT t.*, 
                     c.nombre as categoria_nombre,
                     u.username as agente_asignado,
                     col.primer_nombre, col.primer_apellido,
                     CASE 
                         WHEN t.tiempo_esperado = '04:00:00' THEN 1 
                         ELSE 0 
                     END as urgente
              FROM tickets t
              INNER JOIN categorias_ticket c ON t.id_categoria = c.id_categoria
              INNER JOIN colaboradores col ON t.id_colaborador = col.id_colaborador
              LEFT JOIN usuarios u ON t.id_agente_asignado = u.id_usuario
              WHERE t.id_ticket = :id_ticket AND t.id_colaborador = :id_colaborador";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id_ticket', $ticket_id, PDO::PARAM_INT);
    $stmt->bindParam(':id_colaborador', $user_id, PDO::PARAM_INT);
    $stmt->execute();
    
    if ($stmt->rowCount() === 1) {
        $ticket = $stmt->fetch(PDO::FETCH_ASSOC);
    } else {
        $errores[] = "Ticket no encontrado o no tienes permiso para verlo";
    }
    
} catch(PDOException $exception) {
    $errores[] = "Error al cargar el ticket: " . $exception->getMessage();
}

$user_nombre = isset($_SESSION['user_nombre']) ? htmlspecialchars($_SESSION['user_nombre']) : '';
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ver Ticket - HelpDesk</title>
    <link rel="stylesheet" href="../css/styles.css">
</head>
<body>
    <header class="header">
        <div class="container">
            <nav class="navbar">
                <div class="logo">
                    <h2>HelpDesk - Detalle del Ticket</h2>
                </div>
                <ul class="nav-menu">
                    <li><a href="index.php">Inicio</a></li>
                    <li><a href="crear_ticket.php">Crear Ticket</a></li>
                    <li><a href="mis_tickets.php">Mis Tickets</a></li>
                    <li><a href="cambiar_password.php">Cambiar Contraseña</a></li>
                    <li><a href="logout.php" class="btn-logout">Cerrar Sesión</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <main class="container">
        <?php if (!empty($errores)): ?>
            <div class="error-message">
                <ul>
                    <?php foreach ($errores as $error): ?>
                        <li><?php echo $error; ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <div class="text-center">
                <a href="mis_tickets.php" class="btn btn-primary">Volver a Mis Tickets</a>
            </div>
        <?php elseif ($ticket): ?>
            <div class="ticket-header">
    <div class="ticket-title-section">
        <h1>Ticket #<?php echo $ticket['id_ticket']; ?></h1>
        <h2><?php echo htmlspecialchars($ticket['titulo']); ?></h2>
        <?php if ($ticket['urgente'] == 1): ?>
            <span class="badge badge-urgent">URGENTE</span>
        <?php endif; ?>
        
        <!-- AQUÍ VAN LOS BOTONES DEBajo del título -->
        <div class="ticket-header-actions">
            <div class="status-badge-container">
                <span class="badge badge-<?php echo strtolower(str_replace(' ', '-', $ticket['estado'])); ?>">
                    <?php echo $ticket['estado']; ?>
                </span>
            </div>
            
            <div class="ticket-actions">
                <a href="mis_tickets.php" class="btn btn-secondary btn-sm">
                    <span class="btn-icon">←</span> Volver
                </a>
                <?php if ($ticket['estado'] == 'Cerrado'): ?>
                    <button class="btn btn-success btn-sm" onclick="mostrarEncuesta()">
                        <span class="btn-icon">📝</span> Encuesta
                    </button>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

            <div class="ticket-details-grid">
                <!-- Información Principal -->
                <div class="ticket-card ticket-main-info">
                    <h3>Información del Ticket</h3>
                    <div class="info-grid">
                        <div class="info-item">
                            <span class="info-label">Categoría:</span>
                            <span class="info-value"><?php echo htmlspecialchars($ticket['categoria_nombre']); ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Fecha de Creación:</span>
                            <span class="info-value"><?php echo date('d/m/Y H:i', strtotime($ticket['fecha_creacion'])); ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Tiempo Esperado:</span>
                            <span class="info-value"><?php echo $ticket['tiempo_esperado']; ?> horas</span>
                        </div>
                        <?php if ($ticket['fecha_cierre']): ?>
                            <div class="info-item">
                                <span class="info-label">Fecha de Cierre:</span>
                                <span class="info-value"><?php echo date('d/m/Y H:i', strtotime($ticket['fecha_cierre'])); ?></span>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Información del Agente -->
                <div class="ticket-card ticket-agent-info">
                    <h3>Información del Agente</h3>
                    <div class="info-grid">
                        <div class="info-item">
                            <span class="info-label">Agente Asignado:</span>
                            <span class="info-value">
                                <?php echo $ticket['agente_asignado'] ? htmlspecialchars($ticket['agente_asignado']) : 'Sin asignar'; ?>
                            </span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Solicitante:</span>
                            <span class="info-value"><?php echo htmlspecialchars($ticket['primer_nombre'] . ' ' . $ticket['primer_apellido']); ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">IP de Solicitud:</span>
                            <span class="info-value"><?php echo htmlspecialchars($ticket['ip_solicitud']); ?></span>
                        </div>
                    </div>
                </div>

                <!-- Descripción del Problema -->
                <div class="ticket-card ticket-description">
                    <h3>Descripción del Problema</h3>
                    <div class="description-content">
                        <?php echo nl2br(htmlspecialchars($ticket['descripcion'])); ?>
                    </div>
                </div>

                <!-- Comentario de Cierre -->
                <?php if ($ticket['estado'] == 'Cerrado' && !empty($ticket['comentario_cierre'])): ?>
                    <div class="ticket-card ticket-closure">
                        <h3>Comentario de Cierre</h3>
                        <div class="closure-content">
                            <?php echo nl2br(htmlspecialchars($ticket['comentario_cierre'])); ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Encuesta de Satisfacción (oculta inicialmente) -->
            <div id="encuestaContainer" class="ticket-card" style="display: none;">
                <h3>Encuesta de Satisfacción</h3>
                <form id="encuestaForm" method="POST" action="procesar_encuesta.php">
                    <input type="hidden" name="id_ticket" value="<?php echo $ticket['id_ticket']; ?>">
                    
                    <div class="form-group">
                        <label>¿Cómo calificaría la atención recibida?</label>
                        <div class="rating-options">
                            <label class="rating-option">
                                <input type="radio" name="nivel_satisfaccion" value="Conforme" required>
                                <span class="rating-label">Conforme</span>
                            </label>
                            <label class="rating-option">
                                <input type="radio" name="nivel_satisfaccion" value="Inconforme">
                                <span class="rating-label">Inconforme</span>
                            </label>
                            <label class="rating-option">
                                <input type="radio" name="nivel_satisfaccion" value="Solicitud no resuelta">
                                <span class="rating-label">No Resuelta</span>
                            </label>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="comentario">Comentarios adicionales:</label>
                        <textarea id="comentario" name="comentario" rows="3" placeholder="Opcional"></textarea>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">Enviar Encuesta</button>
                        <button type="button" class="btn btn-secondary" onclick="ocultarEncuesta()">Cancelar</button>
                    </div>
                </form>
            </div>

        <?php endif; ?>
    </main>

    <script>
        function mostrarEncuesta() {
            document.getElementById('encuestaContainer').style.display = 'block';
            window.scrollTo({
                top: document.getElementById('encuestaContainer').offsetTop - 20,
                behavior: 'smooth'
            });
        }
        
        function ocultarEncuesta() {
            document.getElementById('encuestaContainer').style.display = 'none';
        }
    </script>
    <?php include_once '../includes/footer.php'; ?>
</body>
</html>