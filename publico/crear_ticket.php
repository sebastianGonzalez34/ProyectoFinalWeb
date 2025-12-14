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
$ip_solicitud = $sanitize->getClientIP();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titulo = $sanitize->cleanInput($_POST['titulo']);
    $descripcion = $sanitize->cleanInput($_POST['descripcion']);
    $id_categoria = intval($_POST['id_categoria']);
    $urgente = isset($_POST['urgente']) ? 1 : 0;
    
    $errores = [];
    
    // Validaciones
    if (empty($titulo)) {
        $errores[] = "El título es requerido";
    } elseif (strlen($titulo) > 255) {
        $errores[] = "El título no puede exceder los 255 caracteres";
    }
    
    if (empty($descripcion)) {
        $errores[] = "La descripción es requerida";
    }
    
    if ($id_categoria <= 0) {
        $errores[] = "Debe seleccionar una categoría válida";
    }
    
    if (empty($errores)) {
        try {
            $database = new Database();
            $db = $database->getConnection();
            
            // Verificar que la categoría existe
            $query = "SELECT id_categoria FROM categorias_ticket WHERE id_categoria = :id_categoria";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':id_categoria', $id_categoria, PDO::PARAM_INT);
            $stmt->execute();
            
            if ($stmt->rowCount() === 0) {
                $errores[] = "La categoría seleccionada no existe";
            } else {
                // Calcular tiempo esperado basado en urgencia
                $tiempo_esperado = $urgente ? '04:00:00' : '24:00:00'; // 4 horas si es urgente, 24 horas si no
                
                $query = "INSERT INTO tickets (id_colaborador, id_categoria, titulo, descripcion, estado, ip_solicitud, tiempo_esperado) 
                         VALUES (:id_colaborador, :id_categoria, :titulo, :descripcion, 'En espera', :ip_solicitud, :tiempo_esperado)";
                
                $stmt = $db->prepare($query);
                
                $stmt->bindParam(':id_colaborador', $user_id, PDO::PARAM_INT);
                $stmt->bindParam(':id_categoria', $id_categoria, PDO::PARAM_INT);
                $stmt->bindParam(':titulo', $titulo);
                $stmt->bindParam(':descripcion', $descripcion);
                $stmt->bindParam(':ip_solicitud', $ip_solicitud);
                $stmt->bindParam(':tiempo_esperado', $tiempo_esperado);
                
                if ($stmt->execute()) {
                    $ticket_id = $db->lastInsertId();
                    header("Location: ver_ticket.php?id=" . $ticket_id);
                    exit();
                } else {
                    $errores[] = "Error al crear el ticket. Por favor, intenta nuevamente.";
                }
            }
        } catch(PDOException $exception) {
            $errores[] = "Error de base de datos: " . $exception->getMessage();
        }
    }
}

// Obtener categorías para el select
$categorias = [];
try {
    $database = new Database();
    $db = $database->getConnection();
    $query = "SELECT id_categoria, nombre FROM categorias_ticket ORDER BY nombre";
    $stmt = $db->query($query);
    $categorias = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $exception) {
    $errores[] = "Error al cargar las categorías: " . $exception->getMessage();
}

$user_nombre = isset($_SESSION['user_nombre']) ? htmlspecialchars($_SESSION['user_nombre']) : '';
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crear Ticket - HelpDesk</title>
    <link rel="stylesheet" href="/css/styles.css">
</head>
<body>
    <header class="header">
        <div class="container">
            <nav class="navbar">
                <div class="logo">
                    <h2>HelpDesk - Crear Ticket</h2>
                </div>
                <ul class="nav-menu">
                    <li><a href="index.php">Inicio</a></li>
                    <li><a href="mis_tickets.php">Mis Tickets</a></li>
                    <li><a href="cambiar_password.php">Cambiar Contraseña</a></li>
                    <li><a href="logout.php" class="btn-logout">Cerrar Sesión</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <main class="container">
        <div class="form-container">
            <h2>Crear Nuevo Ticket</h2>
            <p class="user-info-text">
                Colaborador: <strong><?php echo $user_nombre; ?></strong>
            </p>
            
            <?php if (!empty($errores)): ?>
                <div class="error-message">
                    <ul>
                        <?php foreach ($errores as $error): ?>
                            <li><?php echo $error; ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label for="titulo">Título del Ticket *</label>
                    <input type="text" id="titulo" name="titulo" 
                           value="<?php echo isset($_POST['titulo']) ? htmlspecialchars($_POST['titulo']) : ''; ?>" 
                           required maxlength="255" placeholder="Describa brevemente el problema">
                </div>
                
                <div class="form-group">
                    <label for="descripcion">Descripción Detallada *</label>
                    <textarea id="descripcion" name="descripcion" rows="6" 
                              required placeholder="Describa el problema en detalle, incluyendo pasos para reproducirlo, mensajes de error, etc."><?php echo isset($_POST['descripcion']) ? htmlspecialchars($_POST['descripcion']) : ''; ?></textarea>
                </div>
                
                <div class="form-group">
                    <label for="id_categoria">Categoría *</label>
                    <select id="id_categoria" name="id_categoria" required>
                        <option value="">Seleccione una categoría</option>
                        <?php foreach ($categorias as $categoria): ?>
                            <option value="<?php echo $categoria['id_categoria']; ?>" 
                                <?php echo (isset($_POST['id_categoria']) && $_POST['id_categoria'] == $categoria['id_categoria']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($categoria['nombre']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="urgent-toggle">
                    <div>
                        <span class="urgent-label">¿Es urgente?</span>
                        <div class="urgent-note">
                            Los tickets urgentes tienen un tiempo de respuesta esperado de 4 horas
                        </div>
                    </div>
                    <label class="toggle-switch">
                        <input type="checkbox" name="urgente" value="1" <?php echo (isset($_POST['urgente'])) ? 'checked' : ''; ?>>
                        <span class="toggle-slider"></span>
                    </label>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Crear Ticket</button>
                    <a href="index.php" class="btn btn-secondary">Cancelar</a>
                    <a href="mis_tickets.php" class="btn btn-outline">Ver Mis Tickets</a>
                </div>
            </form>
        </div>
    </main>
</body>
</html>