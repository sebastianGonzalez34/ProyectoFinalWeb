<?php
require_once '../includes/config.php';
require_once '../includes/database.php';
require_once '../includes/auth.php';
require_once '../includes/sanitize.php';

$database = new Database();
$db = $database->getConnection();
$auth = new Auth($db);
$auth->redirectIfNotLogged('admin');

// Instanciar sanitize
$sanitize = new Sanitize();

// Asignar agente a ticket
if (isset($_POST['asignar_agente'])) {
    $ticket_id = $sanitize->cleanInput($_POST['ticket_id']);
    $agente_id = $sanitize->cleanInput($_POST['agente_id']);
    
    $stmt = $db->prepare("UPDATE tickets SET id_agente_asignado = ?, estado = 'En proceso' WHERE id_ticket = ?");
    $stmt->execute([$agente_id, $ticket_id]);
    $success = "Agente asignado exitosamente";
}

// Cerrar ticket
if (isset($_POST['cerrar_ticket'])) {
    $ticket_id = $sanitize->cleanInput($_POST['ticket_id']);
    $comentario = $sanitize->cleanInput($_POST['comentario_cierre']);
    
    $stmt = $db->prepare("UPDATE tickets SET estado = 'Cerrado', comentario_cierre = ?, fecha_cierre = NOW(), tiempo_esperado = TIMEDIFF(NOW(), fecha_creacion) WHERE id_ticket = ?");
    $stmt->execute([$comentario, $ticket_id]);
    $success = "Ticket cerrado exitosamente";
}

// Obtener agentes para asignar
$agentes = $db->query("SELECT id_usuario, username FROM usuarios WHERE rol = 'agente' AND activo = 1")->fetchAll(PDO::FETCH_ASSOC);

// Ver ticket específico
$ticket_detalle = null;
$encuesta_ticket = null;
if (isset($_GET['action']) && $_GET['action'] == 'view' && isset($_GET['id'])) {
    $ticket_id = $sanitize->cleanInput($_GET['id']);
    
    $stmt = $db->prepare("SELECT t.*, c.*, cat.nombre as categoria_nombre, u.username as agente_asignado 
                         FROM tickets t 
                         LEFT JOIN colaboradores c ON t.id_colaborador = c.id_colaborador 
                         LEFT JOIN categorias_ticket cat ON t.id_categoria = cat.id_categoria 
                         LEFT JOIN usuarios u ON t.id_agente_asignado = u.id_usuario 
                         WHERE t.id_ticket = ?");
    $stmt->execute([$ticket_id]);
    $ticket_detalle = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Obtener encuesta si existe
    if ($ticket_detalle && $ticket_detalle['estado'] == 'Cerrado') {
        $encuesta_stmt = $db->prepare("SELECT * FROM encuestas_satisfaccion WHERE id_ticket = ?");
        $encuesta_stmt->execute([$ticket_id]);
        $encuesta_ticket = $encuesta_stmt->fetch(PDO::FETCH_ASSOC);
    }
}

// Paginación
$records_per_page = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$from_record_num = ($records_per_page * $page) - $records_per_page;

// Obtener tickets
$query = "SELECT t.*, c.primer_nombre, c.primer_apellido, cat.nombre as categoria_nombre, 
                 u.username as agente_asignado 
          FROM tickets t 
          LEFT JOIN colaboradores c ON t.id_colaborador = c.id_colaborador 
          LEFT JOIN categorias_ticket cat ON t.id_categoria = cat.id_categoria 
          LEFT JOIN usuarios u ON t.id_agente_asignado = u.id_usuario 
          ORDER BY 
            CASE t.estado 
                WHEN 'En espera' THEN 1
                WHEN 'En proceso' THEN 2
                WHEN 'Cerrado' THEN 3
                ELSE 4
            END,
            t.fecha_creacion DESC 
          LIMIT ?, ?";
$stmt = $db->prepare($query);
$stmt->bindParam(1, $from_record_num, PDO::PARAM_INT);
$stmt->bindParam(2, $records_per_page, PDO::PARAM_INT);
$stmt->execute();
$tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Total para paginación
$total_rows = $db->query("SELECT COUNT(*) FROM tickets")->fetchColumn();
$total_pages = ceil($total_rows / $records_per_page);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tickets - HelpDesk</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <?php 
    include '../includes/header.php'; 
    mostrarHeader('Tickets');
    ?>

    <div class="dashboard">
        <h1>Gestión de Tickets - Administrador</h1>

        <?php if (isset($success)): ?>
            <div class="success-message"><?php echo $success; ?></div>
        <?php endif; ?>

        <?php if ($ticket_detalle): ?>
            <!-- Detalle del Ticket -->
            <div class="ticket-detail">
                <h2>Detalle del Ticket #<?php echo $ticket_detalle['id_ticket']; ?></h2>
                
                <div class="info-grid">
                    <div class="info-section">
                        <h3>Información del Solicitante</h3>
                        <p><strong>Nombre:</strong> <?php echo htmlspecialchars($ticket_detalle['primer_nombre'] . ' ' . $ticket_detalle['primer_apellido']); ?></p>
                        <p><strong>Cédula:</strong> <?php echo htmlspecialchars($ticket_detalle['identificacion']); ?></p>
                        <p><strong>Email:</strong> <?php echo htmlspecialchars($ticket_detalle['email']); ?></p>
                    </div>
                    
                    <div class="info-section">
                        <h3>Información del Ticket</h3>
                        <p><strong>Categoría:</strong> <?php echo htmlspecialchars($ticket_detalle['categoria_nombre']); ?></p>
                        <p><strong>Estado:</strong> 
                            <span class="badge badge-<?php 
                                echo $ticket_detalle['estado'] == 'Cerrado' ? 'success' : 
                                     ($ticket_detalle['estado'] == 'En proceso' ? 'warning' : 'secondary'); 
                            ?>">
                                <?php echo $ticket_detalle['estado']; ?>
                            </span>
                        </p>
                        <p><strong>Agente Asignado:</strong> <?php echo $ticket_detalle['agente_asignado'] ?: 'Sin asignar'; ?></p>
                        <p><strong>Fecha Creación:</strong> <?php echo date('d/m/Y H:i', strtotime($ticket_detalle['fecha_creacion'])); ?></p>
                        <p><strong>IP Solicitud:</strong> <?php echo htmlspecialchars($ticket_detalle['ip_solicitud']); ?></p>
                    </div>
                </div>

                <div class="description-section">
                    <h3>Descripción del Problema</h3>
                    <p><?php echo nl2br(htmlspecialchars($ticket_detalle['descripcion'])); ?></p>
                </div>

                <?php if ($ticket_detalle['comentario_cierre']): ?>
                <div class="closure-section">
                    <h3>Solución Aplicada</h3>
                    <p><?php echo nl2br(htmlspecialchars($ticket_detalle['comentario_cierre'])); ?></p>
                </div>
                <?php endif; ?>

                <!-- ENCUESTA DE SATISFACCIÓN -->
                <?php if ($ticket_detalle['estado'] == 'Cerrado'): ?>
                <div class="encuesta-container">
                    <div class="encuesta-header">
                        📊 Encuesta de Satisfacción
                    </div>
                    
                    <?php if ($encuesta_ticket): ?>
                        <div class="encuesta-content">
                            <div class="encuesta-item">
                                <strong>Nivel de Satisfacción:</strong>
                                <div class="satisfaccion-<?php echo strtolower(str_replace(' ', '-', $encuesta_ticket['nivel_satisfaccion'])); ?>">
                                    <?php 
                                    $icono = '';
                                    if ($encuesta_ticket['nivel_satisfaccion'] == 'Conforme') {
                                        $icono = '✅';
                                    } elseif ($encuesta_ticket['nivel_satisfaccion'] == 'Inconforme') {
                                        $icono = '❌';
                                    } else {
                                        $icono = '⚠️';
                                    }
                                    echo $icono . ' ' . htmlspecialchars($encuesta_ticket['nivel_satisfaccion']);
                                    ?>
                                </div>
                            </div>
                            
                            <div class="encuesta-item">
                                <strong>Fecha de Encuesta:</strong>
                                <p><?php echo date('d/m/Y H:i', strtotime($encuesta_ticket['fecha_encuesta'])); ?></p>
                            </div>
                        </div>
                        
                        <?php if (!empty($encuesta_ticket['comentario'])): ?>
                        <div class="encuesta-comentario">
                            <strong>Comentario del Usuario:</strong>
                            <p><?php echo nl2br(htmlspecialchars($encuesta_ticket['comentario'])); ?></p>
                        </div>
                        <?php endif; ?>
                    <?php else: ?>
                        <div class="encuesta-empty">
                            <p>📝 El usuario aún no ha completado la encuesta de satisfacción.</p>
                            <small>La encuesta estará disponible una vez que el usuario la complete.</small>
                        </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>

                <!-- Formularios de acción para admin -->
                <?php if ($ticket_detalle['estado'] != 'Cerrado'): ?>
                    <?php if (!$ticket_detalle['id_agente_asignado']): ?>
                        <form method="POST" class="assign-form">
                            <input type="hidden" name="ticket_id" value="<?php echo $ticket_detalle['id_ticket']; ?>">
                            <div class="form-group">
                                <label for="agente_id">Asignar Agente:</label>
                                <select id="agente_id" name="agente_id" required>
                                    <option value="">Seleccionar agente</option>
                                    <?php foreach($agentes as $agente): ?>
                                        <option value="<?php echo $agente['id_usuario']; ?>"><?php echo htmlspecialchars($agente['username']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <button type="submit" name="asignar_agente" class="btn btn-primary">Asignar Agente</button>
                        </form>
                    <?php else: ?>
                        <form method="POST" class="close-form">
                            <input type="hidden" name="ticket_id" value="<?php echo $ticket_detalle['id_ticket']; ?>">
                            <div class="form-group">
                                <label for="comentario_cierre">Comentario de Cierre</label>
                                <textarea id="comentario_cierre" name="comentario_cierre" rows="4" required placeholder="Describa cómo se solucionó el problema..."></textarea>
                            </div>
                            <button type="submit" name="cerrar_ticket" class="btn btn-success">Cerrar Ticket</button>
                        </form>
                    <?php endif; ?>
                <?php endif; ?>

                <a href="tickets.php" class="btn btn-secondary">Volver a la lista</a>
            </div>
        <?php endif; ?>

        <!-- Lista de Tickets -->
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Título</th>
                        <th>Solicitante</th>
                        <th>Categoría</th>
                        <th>Estado</th>
                        <th>Agente</th>
                        <th>Fecha</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($tickets) > 0): ?>
                        <?php foreach($tickets as $ticket): ?>
                        <tr>
                            <td><?php echo $ticket['id_ticket']; ?></td>
                            <td><?php echo htmlspecialchars($ticket['titulo']); ?></td>
                            <td><?php echo htmlspecialchars($ticket['primer_nombre'] . ' ' . $ticket['primer_apellido']); ?></td>
                            <td><?php echo htmlspecialchars($ticket['categoria_nombre']); ?></td>
                            <td>
                                <span class="badge badge-<?php 
                                    echo $ticket['estado'] == 'Cerrado' ? 'success' : 
                                         ($ticket['estado'] == 'En proceso' ? 'warning' : 'secondary'); 
                                ?>">
                                    <?php echo $ticket['estado']; ?>
                                </span>
                            </td>
                            <td><?php echo $ticket['agente_asignado'] ?: 'Sin asignar'; ?></td>
                            <td><?php echo date('d/m/Y H:i', strtotime($ticket['fecha_creacion'])); ?></td>
                            <td class="ticket-actions">
                                <a href="tickets.php?action=view&id=<?php echo $ticket['id_ticket']; ?>" class="btn-view">Ver</a>
                                <?php if (!$ticket['id_agente_asignado'] && $ticket['estado'] != 'Cerrado'): ?>
                                    <a href="tickets.php?action=view&id=<?php echo $ticket['id_ticket']; ?>#asignar" class="btn-assign">Asignar</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" class="empty-state">
                                No hay tickets registrados
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Paginación -->
        <?php if ($total_pages > 1): ?>
        <div class="pagination-container">
            <div class="pagination">
                <?php if ($page > 1): ?>
                    <a href="tickets.php?page=<?php echo $page - 1; ?>" class="page-link">
                        &laquo; Anterior
                    </a>
                <?php endif; ?>
                
                <?php 
                $start_page = max(1, $page - 2);
                $end_page = min($total_pages, $page + 2);
                
                for ($i = $start_page; $i <= $end_page; $i++): ?>
                    <a href="tickets.php?page=<?php echo $i; ?>" 
                       class="page-link <?php echo $i == $page ? 'active' : ''; ?>">
                        <?php echo $i; ?>
                    </a>
                <?php endfor; ?>
                
                <?php if ($page < $total_pages): ?>
                    <a href="tickets.php?page=<?php echo $page + 1; ?>" class="page-link">
                        Siguiente &raquo;
                    </a>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</body>
</html>