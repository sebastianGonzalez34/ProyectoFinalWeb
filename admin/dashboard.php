<?php
require_once '../includes/config.php';
require_once '../includes/database.php';
require_once '../includes/auth.php';

$database = new Database();
$db = $database->getConnection();
$auth = new Auth($db);
$auth->redirectIfNotLogged('admin');

// Estadísticas
$total_tickets = $db->query("SELECT COUNT(*) FROM tickets")->fetchColumn();
$tickets_abiertos = $db->query("SELECT COUNT(*) FROM tickets WHERE estado != 'Cerrado'")->fetchColumn();
$tickets_cerrados = $db->query("SELECT COUNT(*) FROM tickets WHERE estado = 'Cerrado'")->fetchColumn();
$total_usuarios = $db->query("SELECT COUNT(*) FROM usuarios")->fetchColumn();
$total_colaboradores = $db->query("SELECT COUNT(*) FROM colaboradores")->fetchColumn();

// Tickets recientes
$tickets_recientes = $db->query("SELECT t.*, c.primer_nombre, c.primer_apellido, cat.nombre as categoria 
                                FROM tickets t 
                                LEFT JOIN colaboradores c ON t.id_colaborador = c.id_colaborador 
                                LEFT JOIN categorias_ticket cat ON t.id_categoria = cat.id_categoria 
                                ORDER BY t.fecha_creacion DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - HelpDesk</title>
    <link rel="stylesheet" href="../css/styles.css">
</head>
<body>
    <?php 
    include '../includes/header.php'; 
    mostrarHeader('Dashboard');
    ?>

    <div class="dashboard">
        <h1>Dashboard - Administrador</h1>
        
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?php echo $total_tickets; ?></div>
                <div class="stat-label">Total Tickets</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $tickets_abiertos; ?></div>
                <div class="stat-label">Tickets Abiertos</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $tickets_cerrados; ?></div>
                <div class="stat-label">Tickets Cerrados</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $total_usuarios; ?></div>
                <div class="stat-label">Usuarios Sistema</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $total_colaboradores; ?></div>
                <div class="stat-label">Colaboradores</div>
            </div>
        </div>

        <div class="recent-tickets" style="background: white; padding: 2rem; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); margin-top: 2rem;">
            <h2>Tickets Recientes</h2>
            
            <?php if (count($tickets_recientes) > 0): ?>
                <table class="table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Título</th>
                            <th>Solicitante</th>
                            <th>Categoría</th>
                            <th>Estado</th>
                            <th>Fecha</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($tickets_recientes as $ticket): ?>
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
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>No hay tickets recientes.</p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>