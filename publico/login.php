<?php
require_once '../includes/config.php';
require_once '../includes/database.php';
require_once '../includes/sanitize.php';

$sanitize = new Sanitize();

// Si ya está logueado, redirigir al index
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $sanitize->cleanInput($_POST['username']);
    $password = $_POST['password'] ?? '';
    $tipo_usuario = $sanitize->cleanInput($_POST['tipo_usuario']);
    
    $errores = [];
    
    if (empty($username)) {
        $errores[] = "El usuario/identificación es requerido";
    }
    
    if (empty($password)) {
        $errores[] = "La contraseña es requerida";
    }
    
    if (empty($tipo_usuario)) {
        $errores[] = "Debe seleccionar un tipo de usuario";
    }
    
    if (empty($errores)) {
        try {
            $database = new Database();
            $db = $database->getConnection();
            
            if ($tipo_usuario === 'colaborador') {
                // Login para colaboradores (estudiantes)
                $query = "SELECT id_colaborador, primer_nombre, primer_apellido, email, identificacion, password 
                         FROM colaboradores WHERE identificacion = :username OR email = :username";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':username', $username);
                $stmt->execute();
                
                if ($stmt->rowCount() === 1) {
                    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if (password_verify($password, $usuario['password'])) {
                        $_SESSION['user_id'] = $usuario['id_colaborador'];
                        $_SESSION['user_tipo'] = 'colaborador';
                        $_SESSION['user_email'] = $usuario['email'];
                        $_SESSION['user_nombre'] = $usuario['primer_nombre'];
                        $_SESSION['user_apellido'] = $usuario['primer_apellido'];
                        $_SESSION['user_identificacion'] = $usuario['identificacion'];
                        
                        header("Location: index.php");
                        exit();
                    } else {
                        $errores[] = "Credenciales incorrectas";
                    }
                } else {
                    $errores[] = "Credenciales incorrectas";
                }
            } elseif ($tipo_usuario === 'personal') {
                // Login para personal (admin/agentes)
                $query = "SELECT id_usuario, username, email, rol, password 
                         FROM usuarios WHERE username = :username AND activo = 1";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':username', $username);
                $stmt->execute();
                
                if ($stmt->rowCount() === 1) {
                    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if (password_verify($password, $usuario['password'])) {
                        $_SESSION['user_id'] = $usuario['id_usuario'];
                        $_SESSION['user_tipo'] = 'personal';
                        $_SESSION['user_rol'] = $usuario['rol'];
                        $_SESSION['user_email'] = $usuario['email'];
                        $_SESSION['user_nombre'] = $usuario['username'];
                        
                        // Redirigir según el rol
                        if ($usuario['rol'] === 'admin') {
                            header("Location: ../admin/index.php");
                        } else {
                            header("Location: ../agente/index.php");
                        }
                        exit();
                    } else {
                        $errores[] = "Credenciales incorrectas";
                    }
                } else {
                    $errores[] = "Credenciales incorrectas";
                }
            }
        } catch(PDOException $exception) {
            $errores[] = "Error de base de datos: " . $exception->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión - HelpDesk</title>
    <link rel="stylesheet" href="../css/styles.css">
</head>
<body>
    <header class="header">
        <div class="container">
            <nav class="navbar">
                <div class="logo">
                    <h2>HelpDesk - Iniciar Sesión</h2>
                </div>
                <ul class="nav-menu">
                    <li><a href="index.php">Inicio</a></li>
                    <li><a href="registro.php">Registro Colaborador</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <main class="container">
        <div class="form-container">
            <h2>Iniciar Sesión</h2>
            
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
                    <label for="tipo_usuario">Tipo de Usuario *</label>
                    <select id="tipo_usuario" name="tipo_usuario" required>
                        <option value="">Seleccione...</option>
                        <option value="colaborador" <?php echo (isset($_POST['tipo_usuario']) && $_POST['tipo_usuario'] == 'colaborador') ? 'selected' : ''; ?>>Colaborador/Estudiante</option>
                        <option value="personal" <?php echo (isset($_POST['tipo_usuario']) && $_POST['tipo_usuario'] == 'personal') ? 'selected' : ''; ?>>Personal (Admin/Agente)</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="username">Usuario/Identificación *</label>
                    <input type="text" id="username" name="username" 
                           value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>" 
                           required placeholder="Ingrese su usuario o identificación">
                </div>
                
                <div class="form-group">
                    <label for="password">Contraseña *</label>
                    <input type="password" id="password" name="password" 
                           required placeholder="Ingrese su contraseña">
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Iniciar Sesión</button>
                    
                    <div style="text-align: center; margin-top: 1rem;">
                        <a href="registro.php" class="btn-link" style="color: #007bff; text-decoration: none;">
                            ¿Eres colaborador y no tienes cuenta? Regístrate aquí
                        </a>
                    </div>
                    
                    <div style="text-align: center; margin-top: 0.5rem;">
                        <a href="cambiar_password.php" class="btn-link" style="color: #6c757d; text-decoration: none;">
                            ¿Olvidaste tu contraseña?
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </main>
</body>
</html>