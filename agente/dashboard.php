<?php
require_once '../includes/config.php';
require_once '../includes/database.php';
require_once '../includes/auth.php';

$database = new Database();
$db = $database->getConnection();
$auth = new Auth($db);
$auth->redirectIfNotLogged('agente');

$user_id = $_SESSION['user_id'];

// Estadísticas del agente
$tickets_asignados = $db->prepare("SELECT COUNT(*) FROM tickets WHERE id_agente_asignado = ? AND estado != 'Cerrado'");
$tickets_asignados->execute([$user_id]);
$total_asignados = $tickets_asignados->fetchColumn();

$tickets_cerrados = $db->prepare("SELECT COUNT(*) FROM tickets WHERE id_agente_asignado = ? AND estado = 'Cerrado'");
$tickets_cerrados->execute([$user_id]);
$total_cerrados = $tickets_cerrados->fetchColumn();

// Tickets asignados recientes
$tickets_recientes = $db->prepare("SELECT t.*, c.primer_nombre, c.primer_apellido, cat.nombre as categoria 
                                  FROM tickets t 
                                  LEFT JOIN colaboradores c ON t.id_colaborador = c.id_colaborador 
                                  LEFT JOIN categorias_ticket cat ON t.id_categoria = cat.id_categoria 
                                  WHERE t.id_agente_asignado = ? 
                                  ORDER BY t.fecha_creacion DESC LIMIT 5");
$tickets_recientes->execute([$user_id]);
$tickets = $tickets_recientes->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Agente - HelpDesk</title>
    <link rel="stylesheet" href="../css/styles.css">
</head>
<body>
    <?php 
    include '../includes/header.php'; 
    mostrarHeader('Dashboard');
    ?>

    <div class="dashboard">
        <h1>Dashboard - Agente de TI</h1>
        
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?php echo $total_asignados; ?></div>
                <div class="stat-label">Tickets Asignados</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $total_cerrados; ?></div>
                <div class="stat-label">Tickets Cerrados</div>
            </div>
        </div>

        <div class="recent-tickets" style="background: white; padding: 2rem; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); margin-top: 2rem;">
            <h2>Mis Tickets Asignados</h2>
            
            <?php if (count($tickets) > 0): ?>
                <table class="table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Título</th>
                            <th>Solicitante</th>
                            <th>Categoría</th>
                            <th>Estado</th>
                            <th>Fecha</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($tickets as $ticket): ?>
                        <tr>
                            <td><?php echo $ticket['id_ticket']; ?></td>
                            <td><?php echo htmlspecialchars($ticket['titulo']); ?></td>
                            <td><?php echo htmlspecialchars($ticket['primer_nombre'] . ' ' . $ticket['primer_apellido']); ?></td>
                            <td><?php echo htmlspecialchars($ticket['categoria']); ?></td>
                            <td>
                                <span class="badge badge-<?php 
                                    echo $ticket['estado'] == 'Cerrado' ? 'success' : 
                                         ($ticket['estado'] == 'En proceso' ? 'warning' : 'secondary'); 
                                ?>">
                                    <?php echo $ticket['estado']; ?>
                                </span>
                            </td>
                            <td><?php echo date('d/m/Y H:i', strtotime($ticket['fecha_creacion'])); ?></td>
                            <td>
                                <a href="tickets.php?action=view&id=<?php echo $ticket['id_ticket']; ?>" class="btn btn-primary btn-sm">Gestionar</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>No tienes tickets asignados.</p>
            <?php endif; ?>
            
            <div style="margin-top: 1rem;">
                <a href="tickets.php" class="btn btn-primary">Ver Todos los Tickets</a>
            </div>
        </div>
    </div>
    <?php include_once '../includes/footer.php'; ?>
</body>
</html>