<?php
require_once '../includes/config.php';
require_once '../includes/database.php';
require_once '../includes/sanitize.php';

session_start();

// Verificar si el usuario está logueado
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$sanitize = new Sanitize();
$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    $errores = [];
    
    if (empty($current_password)) {
        $errores[] = "La contraseña actual es requerida";
    }
    
    if (empty($new_password)) {
        $errores[] = "La nueva contraseña es requerida";
    } elseif (strlen($new_password) < 8) {
        $errores[] = "La nueva contraseña debe tener al menos 8 caracteres";
    }
    
    if ($new_password !== $confirm_password) {
        $errores[] = "Las nuevas contraseñas no coinciden";
    }
    
    if (empty($errores)) {
        try {
            $database = new Database();
            $db = $database->getConnection();
            
            // Obtener la contraseña actual del usuario
            $query = "SELECT password FROM usuarios WHERE id = :id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':id', $user_id, PDO::PARAM_INT);
            $stmt->execute();
            
            if ($stmt->rowCount() === 1) {
                $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
                
                // Verificar contraseña actual
                if (password_verify($current_password, $usuario['password'])) {
                    // Actualizar contraseña
                    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                    
                    $query = "UPDATE usuarios SET password = :password WHERE id = :id";
                    $stmt = $db->prepare($query);
                    $stmt->bindParam(':password', $hashed_password);
                    $stmt->bindParam(':id', $user_id, PDO::PARAM_INT);
                    
                    if ($stmt->execute()) {
                        $mensaje_exito = "Contraseña actualizada exitosamente";
                    } else {
                        $errores[] = "Error al actualizar la contraseña";
                    }
                } else {
                    $errores[] = "La contraseña actual es incorrecta";
                }
            } else {
                $errores[] = "Usuario no encontrado";
            }
        } catch(PDOException $exception) {
            $errores[] = "Error de base de datos: " . $exception->getMessage();
        }
    }
}

$nombre_usuario = $sanitize->cleanInput($_SESSION['user_nombre'] . ' ' . $_SESSION['user_apellido']);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cambiar Contraseña - HelpDesk</title>
    <link rel="stylesheet" href="../css/styles.css">
</head>
<body>
    <header class="header">
        <div class="container">
            <nav class="navbar">
                <div class="logo">
                    <h2>HelpDesk - Cambiar Contraseña</h2>
                </div>
                <ul class="nav-menu">
                    <li><a href="index.php">Inicio</a></li>
                    <li><a href="crear_ticket.php">Crear Ticket</a></li>
                    <li><a href="logout.php">Cerrar Sesión</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <div class="container">
        <div class="form-container" style="max-width: 600px; margin: 3rem auto;">
            <h2 style="text-align: center; margin-bottom: 2rem;">Cambiar Contraseña</h2>
            <p style="text-align: center; margin-bottom: 2rem; color: #666;">
                Usuario: <strong><?php echo $nombre_usuario; ?></strong>
            </p>
            
            <?php if (!empty($errores)): ?>
                <div class="error-message" style="background: #f8d7da; color: #721c24; padding: 1rem; border-radius: 5px; margin-bottom: 1.5rem;">
                    <ul style="margin: 0; padding-left: 1.5rem;">
                        <?php foreach ($errores as $error): ?>
                            <li><?php echo $error; ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            
            <?php if (isset($mensaje_exito)): ?>
                <div class="success-message" style="background: #d4edda; color: #155724; padding: 1rem; border-radius: 5px; margin-bottom: 1.5rem; text-align: center;">
                    <?php echo $mensaje_exito; ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="" style="background: white; padding: 2rem; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
                <div class="form-group" style="margin-bottom: 1.5rem;">
                    <label for="current_password" style="display: block; margin-bottom: 0.5rem; font-weight: 600;">Contraseña Actual:</label>
                    <input type="password" id="current_password" name="current_password" 
                           required style="width: 100%; padding: 0.75rem; border: 1px solid #ddd; border-radius: 5px;">
                </div>
                
                <div class="form-group" style="margin-bottom: 1.5rem;">
                    <label for="new_password" style="display: block; margin-bottom: 0.5rem; font-weight: 600;">Nueva Contraseña (mínimo 8 caracteres):</label>
                    <input type="password" id="new_password" name="new_password" 
                           required minlength="8" style="width: 100%; padding: 0.75rem; border: 1px solid #ddd; border-radius: 5px;">
                </div>
                
                <div class="form-group" style="margin-bottom: 2rem;">
                    <label for="confirm_password" style="display: block; margin-bottom: 0.5rem; font-weight: 600;">Confirmar Nueva Contraseña:</label>
                    <input type="password" id="confirm_password" name="confirm_password" 
                           required minlength="8" style="width: 100%; padding: 0.75rem; border: 1px solid #ddd; border-radius: 5px;">
                </div>
                
                <div class="form-actions" style="display: flex; gap: 1rem;">
                    <button type="submit" class="btn btn-primary" style="flex: 1; padding: 0.75rem; border: none; border-radius: 5px; background: #007bff; color: white; font-weight: 600; cursor: pointer;">Cambiar Contraseña</button>
                    <a href="index.php" class="btn btn-secondary" style="flex: 1; padding: 0.75rem; border: 1px solid #ddd; border-radius: 5px; background: white; color: #333; text-align: center; text-decoration: none; font-weight: 600;">Cancelar</a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>