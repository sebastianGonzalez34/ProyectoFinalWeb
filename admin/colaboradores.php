<?php
require_once '../includes/config.php';
require_once '../includes/database.php';
require_once '../includes/sanitize.php';
require_once '../includes/auth.php';

$database = new Database();
$db = $database->getConnection();
$auth = new Auth($db);
$auth->redirectIfNotLogged('admin');

// Paginación
$records_per_page = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$page = max(1, $page);
$from_record_num = ($records_per_page * $page) - $records_per_page;

// Búsqueda y filtros
$search = isset($_GET['search']) ? $_GET['search'] : '';
$search = htmlspecialchars(trim($search), ENT_QUOTES, 'UTF-8');
$where = '';
$search_term = '';

if ($search) {
    $where = "WHERE c.identificacion LIKE :search OR c.primer_nombre LIKE :search2 OR c.primer_apellido LIKE :search3 OR c.username LIKE :search4 OR c.email LIKE :search5";
    $search_term = "%$search%";
}

// Obtener colaboradores (SIN foto_perfil)
$query = "SELECT 
            c.id_colaborador, 
            c.primer_nombre, 
            c.segundo_nombre, 
            c.primer_apellido, 
            c.segundo_apellido, 
            c.sexo, 
            c.identificacion, 
            c.username, 
            c.fecha_nacimiento, 
            c.email, 
            c.password,
            c.fecha_registro,
            COUNT(t.id_ticket) as total_tickets 
          FROM colaboradores c 
          LEFT JOIN tickets t ON c.id_colaborador = t.id_colaborador 
          $where 
          GROUP BY c.id_colaborador 
          ORDER BY c.fecha_registro DESC 
          LIMIT :offset, :limit";

$stmt = $db->prepare($query);

if ($search) {
    $stmt->bindValue(':search', $search_term);
    $stmt->bindValue(':search2', $search_term);
    $stmt->bindValue(':search3', $search_term);
    $stmt->bindValue(':search4', $search_term);
    $stmt->bindValue(':search5', $search_term);
    $stmt->bindValue(':offset', $from_record_num, PDO::PARAM_INT);
    $stmt->bindValue(':limit', $records_per_page, PDO::PARAM_INT);
} else {
    $stmt->bindValue(':offset', $from_record_num, PDO::PARAM_INT);
    $stmt->bindValue(':limit', $records_per_page, PDO::PARAM_INT);
}

$stmt->execute();
$colaboradores = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Total para paginación
$count_query = "SELECT COUNT(*) FROM colaboradores c $where";
$count_stmt = $db->prepare($count_query);

if ($search) {
    $count_stmt->bindValue(':search', $search_term);
    $count_stmt->bindValue(':search2', $search_term);
    $count_stmt->bindValue(':search3', $search_term);
    $count_stmt->bindValue(':search4', $search_term);
    $count_stmt->bindValue(':search5', $search_term);
    $count_stmt->execute();
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
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <?php 
    include '../includes/header.php'; 
    mostrarHeader('Colaboradores');
    ?>

    <div class="dashboard">
        <h1>Gestión de Colaboradores</h1>

        <div style="margin-bottom: 2rem;">
            <form method="GET" class="form-inline" style="display: flex; gap: 1rem;">
                <input type="text" name="search" class="form-control" placeholder="Buscar por cédula, nombre, usuario o email..." value="<?php echo htmlspecialchars($search); ?>">
                <button type="submit" class="btn btn-primary">Buscar</button>
                <?php if($search): ?>
                    <a href="colaboradores.php" class="btn btn-secondary">Limpiar</a>
                <?php endif; ?>
            </form>
        </div>

        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Identificación</th>
                        <th>Usuario</th>
                        <th>Nombre Completo</th>
                        <th>Sexo</th>
                        <th>Fecha Nacimiento</th>
                        <th>Email</th>
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
                            <td><?php echo htmlspecialchars($colab['username'] ?? 'N/A'); ?></td>
                            <td>
                                <?php 
                                $nombre_completo = $colab['primer_nombre'];
                                if (!empty($colab['segundo_nombre'])) {
                                    $nombre_completo .= ' ' . $colab['segundo_nombre'];
                                }
                                $nombre_completo .= ' ' . $colab['primer_apellido'];
                                if (!empty($colab['segundo_apellido'])) {
                                    $nombre_completo .= ' ' . $colab['segundo_apellido'];
                                }
                                echo htmlspecialchars($nombre_completo);
                                ?>
                            </td>
                            <td>
                                <?php 
                                $sexo_text = '';
                                switch($colab['sexo']) {
                                    case 'M': $sexo_text = 'Masculino'; break;
                                    case 'F': $sexo_text = 'Femenino'; break;
                                    case 'Otro': $sexo_text = 'Otro'; break;
                                    default: $sexo_text = $colab['sexo'];
                                }
                                echo htmlspecialchars($sexo_text);
                                ?>
                            </td>
                            <td><?php echo date('d/m/Y', strtotime($colab['fecha_nacimiento'])); ?></td>
                            <td><?php echo htmlspecialchars($colab['email'] ?? 'N/A'); ?></td>
                            <td><?php echo $colab['total_tickets']; ?></td>
                            <td><?php echo date('d/m/Y H:i', strtotime($colab['fecha_registro'])); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="9" style="text-align: center; padding: 2rem;">
                                <div style="color: #666;">
                                    <p>No se encontraron colaboradores</p>
                                    <?php if($search): ?>
                                        <p>Intenta con otros términos de búsqueda</p>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php if ($total_pages > 1): ?>
        <div class="pagination-container">
            <div class="pagination">
                <?php if ($page > 1): ?>
                    <a href="colaboradores.php?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>" class="page-link">
                        &laquo; Anterior
                    </a>
                <?php endif; ?>
                
                <?php 
                $start_page = max(1, $page - 2);
                $end_page = min($total_pages, $page + 2);
                
                for ($i = $start_page; $i <= $end_page; $i++): ?>
                    <a href="colaboradores.php?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>" 
                       class="page-link <?php echo $i == $page ? 'active' : ''; ?>">
                        <?php echo $i; ?>
                    </a>
                <?php endfor; ?>
                
                <?php if ($page < $total_pages): ?>
                    <a href="colaboradores.php?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>" class="page-link">
                        Siguiente &raquo;
                    </a>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
    <?php include_once '../includes/footer.php'; ?>
</body>
</html>