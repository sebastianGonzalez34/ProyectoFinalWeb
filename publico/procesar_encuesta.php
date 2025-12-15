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
$errores = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_ticket = intval($_POST['id_ticket']);
    $nivel_satisfaccion = $sanitize->cleanInput($_POST['nivel_satisfaccion']);
    $comentario = $sanitize->cleanInput($_POST['comentario'] ?? '');
    
    // Validaciones
    if ($id_ticket <= 0) {
        $errores[] = "Ticket inválido";
    }
    
    $niveles_permitidos = ['Conforme', 'Inconforme', 'Solicitud no resuelta'];
    if (empty($nivel_satisfaccion) || !in_array($nivel_satisfaccion, $niveles_permitidos)) {
        $errores[] = "Nivel de satisfacción inválido";
    }
    
    if (empty($errores)) {
        try {
            $database = new Database();
            $db = $database->getConnection();
            
            // Verificar que el ticket existe y pertenece al usuario
            $query = "SELECT id_ticket FROM tickets 
                     WHERE id_ticket = :id_ticket 
                     AND id_colaborador = :id_colaborador
                     AND estado = 'Cerrado'";
            
            $stmt = $db->prepare($query);
            $stmt->bindParam(':id_ticket', $id_ticket, PDO::PARAM_INT);
            $stmt->bindParam(':id_colaborador', $user_id, PDO::PARAM_INT);
            $stmt->execute();
            
            if ($stmt->rowCount() === 1) {
                // Verificar si ya hay una encuesta para este ticket
                $query = "SELECT id_encuesta FROM encuestas_satisfaccion 
                         WHERE id_ticket = :id_ticket";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':id_ticket', $id_ticket, PDO::PARAM_INT);
                $stmt->execute();
                
                if ($stmt->rowCount() > 0) {
                    $errores[] = "Ya has enviado una encuesta para este ticket";
                } else {
                    // Insertar la encuesta
                    $query = "INSERT INTO encuestas_satisfaccion 
                             (id_ticket, nivel_satisfaccion, comentario) 
                             VALUES (:id_ticket, :nivel_satisfaccion, :comentario)";
                    
                    $stmt = $db->prepare($query);
                    $stmt->bindParam(':id_ticket', $id_ticket, PDO::PARAM_INT);
                    $stmt->bindParam(':nivel_satisfaccion', $nivel_satisfaccion);
                    $stmt->bindParam(':comentario', $comentario);
                    
                    if ($stmt->execute()) {
                        $success = true;
                    } else {
                        $errores[] = "Error al guardar la encuesta";
                    }
                }
            } else {
                $errores[] = "Ticket no encontrado o no está cerrado";
            }
            
        } catch(PDOException $exception) {
            $errores[] = "Error de base de datos: " . $exception->getMessage();
        }
    }
}

$user_nombre = isset($_SESSION['user_nombre']) ? htmlspecialchars($_SESSION['user_nombre']) : '';
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Encuesta de Satisfacción - HelpDesk</title>
    <link rel="stylesheet" href="../css/styles.css">
</head>
<body>
    <header class="header">
        <div class="container">
            <nav class="navbar">
                <div class="logo">
                    <h2>HelpDesk - Encuesta de Satisfacción</h2>
                </div>
                <ul class="nav-menu">
                    <li><a href="index.php">Inicio</a></li>
                    <li><a href="mis_tickets.php">Mis Tickets</a></li>
                    <li><a href="logout.php" class="btn btn-logout">Cerrar Sesión</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <main class="container">
        <?php if ($success): ?>
            <div class="survey-result survey-success">
                <div class="survey-icon">✅</div>
                <h1>¡Encuesta Enviada!</h1>
                <p>Gracias por tu retroalimentación, <strong><?php echo $user_nombre; ?></strong></p>
                <p>Tu opinión es muy importante para mejorar nuestro servicio.</p>
                
                <div class="survey-actions">
                    <a href="ver_ticket.php?id=<?php echo $id_ticket; ?>" class="btn btn-primary">
                        Volver al Ticket
                    </a>
                    <a href="mis_tickets.php" class="btn btn-outline">
                        Ver Todos Mis Tickets
                    </a>
                    <a href="index.php" class="btn btn-secondary">
                        Volver al Inicio
                    </a>
                </div>
            </div>
            
        <?php elseif (!empty($errores)): ?>
            <div class="survey-result survey-error">
                <div class="survey-icon">❌</div>
                <h1>Error al Procesar Encuesta</h1>
                
                <div class="error-message">
                    <ul>
                        <?php foreach ($errores as $error): ?>
                            <li><?php echo $error; ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                
                <div class="survey-actions">
                    <?php if (isset($id_ticket) && $id_ticket > 0): ?>
                        <a href="ver_ticket.php?id=<?php echo $id_ticket; ?>" class="btn btn-primary">
                            Volver al Ticket
                        </a>
                    <?php endif; ?>
                    <a href="mis_tickets.php" class="btn btn-outline">
                        Ver Mis Tickets
                    </a>
                    <a href="index.php" class="btn btn-secondary">
                        Volver al Inicio
                    </a>
                </div>
            </div>
            
        <?php else: ?>
            <div class="survey-result">
                <div class="survey-icon">📋</div>
                <h1>Encuesta de Satisfacción</h1>
                <p>Colaborador: <strong><?php echo $user_nombre; ?></strong></p>
                <p>No hay datos de encuesta para procesar.</p>
                
                <div class="survey-actions">
                    <a href="mis_tickets.php" class="btn btn-primary">
                        Ver Mis Tickets
                    </a>
                    <a href="index.php" class="btn btn-secondary">
                        Volver al Inicio
                    </a>
                </div>
            </div>
        <?php endif; ?>
    </main>
    <?php include_once '../includes/footer.php'; ?>
</body>
</html>