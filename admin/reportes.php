<?php
require_once '../includes/config.php';
require_once '../includes/database.php';
require_once '../includes/auth.php';

$database = new Database();
$db = $database->getConnection();
$auth = new Auth($db);
$auth->redirectIfNotLogged('admin');

// Filtros
$fecha_desde = isset($_GET['fecha_desde']) ? $_GET['fecha_desde'] : date('Y-m-01');
$fecha_hasta = isset($_GET['fecha_hasta']) ? $_GET['fecha_hasta'] : date('Y-m-d');
$categoria = isset($_GET['categoria']) ? $_GET['categoria'] : '';
$estado = isset($_GET['estado']) ? $_GET['estado'] : '';

// Estadísticas
$stats_query = "SELECT 
    COUNT(*) as total_tickets,
    COUNT(CASE WHEN estado = 'Cerrado' THEN 1 END) as cerrados,
    COUNT(CASE WHEN estado = 'En proceso' THEN 1 END) as en_proceso,
    COUNT(CASE WHEN estado = 'En espera' THEN 1 END) as en_espera,
    AVG(TIME_TO_SEC(tiempo_esperado)) as tiempo_promedio_sec
    FROM tickets 
    WHERE DATE(fecha_creacion) BETWEEN ? AND ?";
$stats_params = [$fecha_desde, $fecha_hasta];

if ($categoria) {
    $stats_query .= " AND id_categoria = ?";
    $stats_params[] = $categoria;
}
if ($estado) {
    $stats_query .= " AND estado = ?";
    $stats_params[] = $estado;
}

$stats_stmt = $db->prepare($stats_query);
$stats_stmt->execute($stats_params);
$estadisticas = $stats_stmt->fetch(PDO::FETCH_ASSOC);

// Tickets por categoría
$cat_query = "SELECT cat.nombre, COUNT(t.id_ticket) as total 
              FROM categorias_ticket cat 
              LEFT JOIN tickets t ON cat.id_categoria = t.id_categoria 
                AND DATE(t.fecha_creacion) BETWEEN ? AND ?
              GROUP BY cat.id_categoria";
$cat_stmt = $db->prepare($cat_query);
$cat_stmt->execute([$fecha_desde, $fecha_hasta]);
$tickets_por_categoria = $cat_stmt->fetchAll(PDO::FETCH_ASSOC);

// Obtener categorías para filtro
$categorias = $db->query("SELECT * FROM categorias_ticket")->fetchAll(PDO::FETCH_ASSOC);

// Exportar a Excel
if (isset($_GET['export'])) {
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment; filename="reporte_tickets_' . date('Y-m-d') . '.xls"');
    
    $export_query = "SELECT t.*, c.primer_nombre, c.primer_apellido, c.identificacion, cat.nombre as categoria_nombre,
                    u.username as agente_asignado, TIMESTAMPDIFF(HOUR, t.fecha_creacion, t.fecha_cierre) as horas_resolucion
                    FROM tickets t
                    LEFT JOIN colaboradores c ON t.id_colaborador = c.id_colaborador
                    LEFT JOIN categorias_ticket cat ON t.id_categoria = cat.id_categoria
                    LEFT JOIN usuarios u ON t.id_agente_asignado = u.id_usuario
                    WHERE DATE(t.fecha_creacion) BETWEEN ? AND ?";
    $export_params = [$fecha_desde, $fecha_hasta];
    
    if ($categoria) {
        $export_query .= " AND t.id_categoria = ?";
        $export_params[] = $categoria;
    }
    if ($estado) {
        $export_query .= " AND t.estado = ?";
        $export_params[] = $estado;
    }
    
    $export_stmt = $db->prepare($export_query);
    $export_stmt->execute($export_params);
    $tickets_export = $export_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "ID\tTítulo\tSolicitante\tCédula\tCategoría\tEstado\tAgente\tFecha Creación\tFecha Cierre\tHoras Resolución\n";
    foreach ($tickets_export as $ticket) {
        echo $ticket['id_ticket'] . "\t";
        echo $ticket['titulo'] . "\t";
        echo $ticket['primer_nombre'] . ' ' . $ticket['primer_apellido'] . "\t";
        echo $ticket['identificacion'] . "\t";
        echo $ticket['categoria_nombre'] . "\t";
        echo $ticket['estado'] . "\t";
        echo $ticket['agente_asignado'] . "\t";
        echo $ticket['fecha_creacion'] . "\t";
        echo $ticket['fecha_cierre'] . "\t";
        echo $ticket['horas_resolucion'] . "\n";
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reportes - HelpDesk</title>
    <link rel="stylesheet" href="../css/styles.css">
</head>
<body>
    <?php 
    include '../includes/header.php'; 
    mostrarHeader('Reportes');
    ?>

    <div class="dashboard">
        <h1>Reportes y Estadísticas</h1>

        <!-- Filtros -->
        <div class="filters" style="background: white; padding: 1.5rem; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); margin-bottom: 2rem;">
            <form method="GET" class="form-inline" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
                <div class="form-group">
                    <label for="fecha_desde">Fecha Desde</label>
                    <input type="date" class="form-control" id="fecha_desde" name="fecha_desde" value="<?php echo $fecha_desde; ?>">
                </div>
                
                <div class="form-group">
                    <label for="fecha_hasta">Fecha Hasta</label>
                    <input type="date" class="form-control" id="fecha_hasta" name="fecha_hasta" value="<?php echo $fecha_hasta; ?>">
                </div>
                
                <div class="form-group">
                    <label for="categoria">Categoría</label>
                    <select class="form-control" id="categoria" name="categoria">
                        <option value="">Todas</option>
                        <?php foreach($categorias as $cat): ?>
                            <option value="<?php echo $cat['id_categoria']; ?>" <?php echo $categoria == $cat['id_categoria'] ? 'selected' : ''; ?>>
                                <?php echo $cat['nombre']; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="estado">Estado</label>
                    <select class="form-control" id="estado" name="estado">
                        <option value="">Todos</option>
                        <option value="En espera" <?php echo $estado == 'En espera' ? 'selected' : ''; ?>>En espera</option>
                        <option value="En proceso" <?php echo $estado == 'En proceso' ? 'selected' : ''; ?>>En proceso</option>
                        <option value="Cerrado" <?php echo $estado == 'Cerrado' ? 'selected' : ''; ?>>Cerrado</option>
                    </select>
                </div>
                
                <div class="form-group" style="display: flex; align-items: end; gap: 1rem;">
                    <button type="submit" class="btn btn-primary">Filtrar</button>
                    <a href="reportes.php" class="btn btn-secondary">Limpiar</a>
                    <a href="reportes.php?<?php echo http_build_query($_GET); ?>&export=1" class="btn btn-success">Exportar Excel</a>
                </div>
            </form>
        </div>

        <!-- Estadísticas -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?php echo $estadisticas['total_tickets']; ?></div>
                <div class="stat-label">Total Tickets</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $estadisticas['cerrados']; ?></div>
                <div class="stat-label">Cerrados</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $estadisticas['en_proceso']; ?></div>
                <div class="stat-label">En Proceso</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $estadisticas['en_espera']; ?></div>
                <div class="stat-label">En Espera</div>
            </div>
            <div class="stat-card">
                <div class="stat-number">
                    <?php 
                    if ($estadisticas['tiempo_promedio_sec']) {
                        $horas = floor($estadisticas['tiempo_promedio_sec'] / 3600);
                        $minutos = floor(($estadisticas['tiempo_promedio_sec'] % 3600) / 60);
                        echo $horas . 'h ' . $minutos . 'm';
                    } else {
                        echo 'N/A';
                    }
                    ?>
                </div>
                <div class="stat-label">Tiempo Promedio</div>
            </div>
        </div>

        <!-- Gráfico de categorías -->
        <div style="background: white; padding: 2rem; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); margin-top: 2rem;">
            <h2>Tickets por Categoría</h2>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
                <?php foreach($tickets_por_categoria as $cat): ?>
                <div style="text-align: center; padding: 1rem; border: 1px solid #ddd; border-radius: 8px;">
                    <h3 style="color: #667eea; margin-bottom: 0.5rem;"><?php echo $cat['total']; ?></h3>
                    <p style="color: #666;"><?php echo $cat['nombre']; ?></p>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</body>
</html>