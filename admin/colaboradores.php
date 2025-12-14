<?php
require_once '../includes/config.php';
require_once '../includes/database.php';
require_once '../includes/auth.php';

$database = new Database();
$db = $database->getConnection();
$auth = new Auth($db);
$auth->redirectIfNotLogged('admin');

// Paginación
$records_per_page = 10;
$page = isset($_GET['page']) ? $_GET['page'] : 1;
$from_record_num = ($records_per_page * $page) - $records_per_page;

// Búsqueda y filtros
$search = isset($_GET['search']) ? Sanitize::cleanInput($_GET['search']) : '';
$where = '';
$params = [];

if ($search) {
    $where = "WHERE c.identificacion LIKE ? OR c.primer_nombre LIKE ? OR c.primer_apellido LIKE ?";
    $search_term = "%$search%";
    $params = [$search_term, $search_term, $search_term];
}

// Obtener colaboradores
$query = "SELECT c.*, COUNT(t.id_ticket) as total_tickets 
          FROM colaboradores c 
          LEFT JOIN tickets t ON c.id_colaborador = t.id_colaborador 
          $where 
          GROUP BY c.id_colaborador 
          ORDER BY c.fecha_registro DESC 
          LIMIT ?, ?";
$stmt = $db->prepare($query);

if ($search) {
    $stmt->bindParam(1, $search_term);
    $stmt->bindParam(2, $search_term);
    $stmt->bindParam(3, $search_term);
    $stmt->bindParam(4, $from_record_num, PDO::PARAM_INT);
    $stmt->bindParam(5, $records_per_page, PDO::PARAM_INT);
} else {
    $stmt->bindParam(1, $from_record_num, PDO::PARAM_INT);
    $stmt->bindParam(2, $records_per_page, PDO::PARAM_INT);
}

$stmt->execute();
$colaboradores = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Total para paginación
$count_query = "SELECT COUNT(*) FROM colaboradores c $where";
$count_stmt = $db->prepare($count_query);
if ($search) {
    $count_stmt->execute([$search_term, $search_term, $search_term]);
} else {
    $count_stmt->execute();
}
$total_rows = $count_stmt->fetchColumn();
$total_pages = ceil($total_rows / $records_per_page);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Colaboradores - HelpDesk</title>
    <link rel="stylesheet" href="../css/styles.css">
</head>
<body>
    <?php 
    include '../includes/header.php'; 
    mostrarHeader('Colaboradores');
    ?>

    <div class="dashboard">
        <h1>Gestión de Colaboradores</h1>

        <!-- Buscador -->
        <div style="margin-bottom: 2rem;">
            <form method="GET" class="form-inline" style="display: flex; gap: 1rem;">
                <input type="text" name="search" class="form-control" placeholder="Buscar por cédula o nombre..." value="<?php echo htmlspecialchars($search); ?>">
                <button type="submit" class="btn btn-primary">Buscar</button>
                <?php if($search): ?>
                    <a href="colaboradores.php" class="btn btn-secondary">Limpiar</a>
                <?php endif; ?>
            </form>
        </div>

        <!-- Tabla de colaboradores -->
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Identificación</th>
                        <th>Nombre Completo</th>
                        <th>Sexo</th>
                        <th>Fecha Nacimiento</th>
                        <th>Total Tickets</th>
                        <th>Fecha Registro</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($colaboradores) > 0): ?>
                        <?php foreach($colaboradores as $colab): ?>
                        <tr>
                            <td><?php echo $colab['id_colaborador']; ?></td>
                            <td><?php echo htmlspecialchars($colab['identificacion']); ?></td>
                            <td><?php echo htmlspecialchars($colab['primer_nombre'] . ' ' . ($colab['segundo_nombre'] ? $colab['segundo_nombre'] . ' ' : '') . $colab['primer_apellido'] . ' ' . ($colab['segundo_apellido'] ? $colab['segundo_apellido'] : '')); ?></td>
                            <td><?php echo $colab['sexo']; ?></td>
                            <td><?php echo date('d/m/Y', strtotime($colab['fecha_nacimiento'])); ?></td>
                            <td><?php echo $colab['total_tickets']; ?></td>
                            <td><?php echo date('d/m/Y H:i', strtotime($colab['fecha_registro'])); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" style="text-align: center;">No se encontraron colaboradores</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Paginación -->
        <?php if ($total_pages > 1): ?>
        <ul class="pagination">
            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <li class="<?php echo $i == $page ? 'active' : ''; ?>">
                    <a href="colaboradores.php?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>"><?php echo $i; ?></a>
                </li>
            <?php endfor; ?>
        </ul>
        <?php endif; ?>
    </div>
</body>
</html>