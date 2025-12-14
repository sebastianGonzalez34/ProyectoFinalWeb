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
$user_nombre = isset($_SESSION['user_nombre']) ? htmlspecialchars($_SESSION['user_nombre']) : '';

// Obtener tickets del colaborador
$tickets = [];
$estadisticas = [
    'total' => 0,
    'en_espera' => 0,
    'en_proceso' => 0,
    'cerrados' => 0,
    'urgentes' => 0
];

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Consulta para obtener los tickets
    $query = "SELECT t.id_ticket, t.titulo, t.descripcion, t.estado, 
                     t.fecha_creacion, t.fecha_cierre, t.tiempo_esperado,
                     c.nombre as categoria_nombre,
                     u.username as agente_asignado,
                     CASE 
                         WHEN t.tiempo_esperado = '04:00:00' THEN 1 
                         ELSE 0 
                     END as urgente
              FROM tickets t
              LEFT JOIN categorias_ticket c ON t.id_categoria = c.id_categoria
              LEFT JOIN usuarios u ON t.id_agente_asignado = u.id_usuario
              WHERE t.id_colaborador = :id_colaborador
              ORDER BY t.fecha_creacion DESC";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id_colaborador', $user_id, PDO::PARAM_INT);
    $stmt->execute();
    
    $tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Calcular estadísticas
    $estadisticas['total'] = count($tickets);
    foreach ($tickets as $ticket) {
        switch ($ticket['estado']) {
            case 'En espera':
                $estadisticas['en_espera']++;
                break;
            case 'En proceso':
                $estadisticas['en_proceso']++;
                break;
            case 'Cerrado':
                $estadisticas['cerrados']++;
                break;
        }
        
        if ($ticket['urgente'] == 1) {
            $estadisticas['urgentes']++;
        }
    }
    
} catch(PDOException $exception) {
    $error = "Error al cargar los tickets: " . $exception->getMessage();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mis Tickets - HelpDesk</title>
    <link rel="stylesheet" href="/css/styles.css">
</head>
<body>
    <header class="header">
        <div class="container">
            <nav class="navbar">
                <div class="logo">
                    <h2>HelpDesk - Mis Tickets</h2>
                </div>
                <ul class="nav-menu">
                    <li><a href="index.php">Inicio</a></li>
                    <li><a href="crear_ticket.php">Crear Ticket</a></li>
                    <li><a href="cambiar_password.php">Cambiar Contraseña</a></li>
                    <li><a href="logout.php" class="btn btn-logout">Cerrar Sesión</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <main class="container">
        <div class="page-header">
            <h1>Mis Tickets</h1>
            <p class="user-info-text">
                Colaborador: <strong><?php echo $user_nombre; ?></strong>
            </p>
        </div>

        <!-- Estadísticas EN HORIZONTAL -->
        <div class="stats-horizontal">
            <div class="stat-item">
                <div class="stat-number"><?php echo $estadisticas['total']; ?></div>
                <div class="stat-label">Total de Tickets</div>
            </div>
            
            <div class="stat-item stat-item-waiting">
                <div class="stat-number"><?php echo $estadisticas['en_espera']; ?></div>
                <div class="stat-label">En Espera</div>
            </div>
            
            <div class="stat-item stat-item-process">
                <div class="stat-number"><?php echo $estadisticas['en_proceso']; ?></div>
                <div class="stat-label">En Proceso</div>
            </div>
            
            <div class="stat-item stat-item-closed">
                <div class="stat-number"><?php echo $estadisticas['cerrados']; ?></div>
                <div class="stat-label">Cerrados</div>
            </div>
            
            <div class="stat-item stat-item-urgent">
                <div class="stat-number"><?php echo $estadisticas['urgentes']; ?></div>
                <div class="stat-label">Urgentes</div>
            </div>
        </div>

        <!-- Acciones -->
        <div class="action-buttons">
            <a href="crear_ticket.php" class="btn btn-primary">
                <span class="btn-icon">➕</span> Crear Nuevo Ticket
            </a>
            <a href="index.php" class="btn btn-secondary">Volver al Inicio</a>
        </div>

        <!-- Lista de Tickets -->
        <div class="tickets-section">
            <h2>Tickets Recientes</h2>
            
            <?php if (isset($error)): ?>
                <div class="error-message">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <?php if (empty($tickets)): ?>
                <div class="empty-state">
                    <div class="empty-icon">📋</div>
                    <h3>No tienes tickets aún</h3>
                    <p>¡Crea tu primer ticket para comenzar!</p>
                    <a href="crear_ticket.php" class="btn btn-primary">Crear Primer Ticket</a>
                </div>
            <?php else: ?>
                <!-- Tabla de tickets -->
                <div class="tickets-table-container">
                    <table class="tickets-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Título</th>
                                <th>Categoría</th>
                                <th>Estado</th>
                                <th>Fecha Creación</th>
                                <th>Agente</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($tickets as $ticket): ?>
                                <tr>
                                    <td class="ticket-id">#<?php echo $ticket['id_ticket']; ?></td>
                                    <td class="ticket-title">
                                        <strong><?php echo htmlspecialchars($ticket['titulo']); ?></strong>
                                        <?php if ($ticket['urgente'] == 1): ?>
                                            <span class="urgent-badge">URGENTE</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($ticket['categoria_nombre']); ?></td>
                                    <td>
                                        <span class="status-badge status-<?php echo strtolower(str_replace(' ', '-', $ticket['estado'])); ?>">
                                            <?php echo $ticket['estado']; ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('d/m/Y H:i', strtotime($ticket['fecha_creacion'])); ?></td>
                                    <td>
                                        <?php echo $ticket['agente_asignado'] ? htmlspecialchars($ticket['agente_asignado']) : 'Sin asignar'; ?>
                                    </td>
                                    <td class="ticket-actions">
                                        <a href="ver_ticket.php?id=<?php echo $ticket['id_ticket']; ?>" class="btn-view">
                                            Ver
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Filtros -->
                <div class="ticket-filters">
                    <h3>Filtrar por:</h3>
                    <div class="filter-buttons">
                        <button class="filter-btn active" data-filter="all">Todos (<?php echo $estadisticas['total']; ?>)</button>
                        <button class="filter-btn" data-filter="en_espera">En Espera (<?php echo $estadisticas['en_espera']; ?>)</button>
                        <button class="filter-btn" data-filter="en_proceso">En Proceso (<?php echo $estadisticas['en_proceso']; ?>)</button>
                        <button class="filter-btn" data-filter="cerrados">Cerrados (<?php echo $estadisticas['cerrados']; ?>)</button>
                        <button class="filter-btn" data-filter="urgentes">Urgentes (<?php echo $estadisticas['urgentes']; ?>)</button>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </main>
    
    <script>
        // Filtrado de tickets
        document.addEventListener('DOMContentLoaded', function() {
            const filterButtons = document.querySelectorAll('.filter-btn');
            
            filterButtons.forEach(button => {
                button.addEventListener('click', function() {
                    // Remover clase active de todos los botones
                    filterButtons.forEach(btn => btn.classList.remove('active'));
                    
                    // Agregar clase active al botón clickeado
                    this.classList.add('active');
                    
                    const filter = this.getAttribute('data-filter');
                    const rows = document.querySelectorAll('.tickets-table tbody tr');
                    
                    rows.forEach(row => {
                        let showRow = false;
                        
                        switch(filter) {
                            case 'all':
                                showRow = true;
                                break;
                            case 'en_espera':
                                showRow = row.querySelector('.status-en-espera') !== null;
                                break;
                            case 'en_proceso':
                                showRow = row.querySelector('.status-en-proceso') !== null;
                                break;
                            case 'cerrados':
                                showRow = row.querySelector('.status-cerrado') !== null;
                                break;
                            case 'urgentes':
                                showRow = row.querySelector('.urgent-badge') !== null;
                                break;
                        }
                        
                        row.style.display = showRow ? '' : 'none';
                    });
                });
            });
        });
    </script>
</body>
</html>