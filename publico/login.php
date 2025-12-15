<?php
require_once '../includes/config.php';
require_once '../includes/database.php';
require_once '../includes/sanitize.php';

$sanitize = new Sanitize();
$errores = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Forzado: SOLO COLABORADOR
    $tipo_usuario = 'colaborador';

    $username = $sanitize->cleanInput($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username)) {
        $errores[] = "El usuario es requerido";
    }

    if (empty($password)) {
        $errores[] = "La contraseña es requerida";
    }

    if (empty($errores)) {
        try {
            $database = new Database();
            $db = $database->getConnection();

            $query = "SELECT id_colaborador, primer_nombre, primer_apellido,
                             email, identificacion, username, password
                      FROM colaboradores
                      WHERE username = :username OR email = :username";

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
                    $_SESSION['user_username'] = $usuario['username'];

                    header("Location: index.php");
                    exit();
                }
            }

            $errores[] = "Usuario o contraseña incorrectos";

        } catch (PDOException $e) {
            $errores[] = "Error de conexión";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>HelpDesk - Iniciar Sesión</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
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

        <form method="POST">

            <!-- SIN TIPO DE USUARIO -->

            <div class="form-group">
                <label for="username">Usuario</label>
                <input type="text" id="username" name="username" required>
            </div>

            <div class="form-group">
                <label for="password">Contraseña</label>
                <input type="password" id="password" name="password" required>
            </div>

            <input type="hidden" name="tipo_usuario" value="colaborador">

            <button type="submit" class="btn btn-primary">
                Iniciar Sesión
            </button>
        </form>

        <!-- 🔁 RESTAURADO EXACTO COMO ANTES -->
        <div class="login-links">
            <p>
                ¿Eres colaborador y no tienes cuenta?
                <a href="registro.php">Regístrate aquí</a>
            </p>
        </div>
    </div>
</main>
<?php include_once '../includes/footer.php'; ?>
</body>
</html>