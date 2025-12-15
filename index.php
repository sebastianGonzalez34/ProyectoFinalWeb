<?php
require_once 'includes/config.php';
require_once 'includes/database.php';
require_once 'includes/auth.php';
require_once 'includes/sanitize.php';

$database = new Database();
$db = $database->getConnection();
$auth = new Auth($db);

if ($_POST) {
    $username = Sanitize::cleanInput($_POST['username']);
    $password = Sanitize::cleanInput($_POST['password']);
    
    if ($auth->login($username, $password)) {
        if ($_SESSION['rol'] == 'admin') {
            header("Location: admin/dashboard.php");
        } else {
            header("Location: agente/dashboard.php");
        }
        exit();
    } else {
        $error = "Usuario o contraseña incorrectos";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Sistema HelpDesk</title>
    <link rel="stylesheet" href="css/styles.css">
</head>
<body>
    <div class="container">
        <div class="form-container">
            <h2 style="text-align: center; margin-bottom: 2rem;">Sistema HelpDesk</h2>
            
            <?php if (isset($error)): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <form method="POST">
                <div class="form-group">
                    <label for="username">Usuario:</label>
                    <input type="text" class="form-control" id="username" name="username" required>
                </div>
                
                <div class="form-group">
                    <label for="password">Contraseña:</label>
                    <input type="password" class="form-control" id="password" name="password" required>
                </div>
                
                <button type="submit" class="btn btn-primary" style="width: 100%;">Iniciar Sesión</button>
            </form>
            
            <p style="text-align: center; margin-top: 1rem;">
                <a href="publico/index.php">Acceso Público</a>
            </p>
        </div>
    </div>
    <?php include_once '../includes/footer.php'; ?>
</body>
</html>