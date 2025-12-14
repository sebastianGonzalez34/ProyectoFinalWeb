<?php
require_once '../includes/config.php';
require_once '../includes/database.php';
require_once '../includes/sanitize.php';

$sanitize = new Sanitize();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitizar todos los campos
    $primer_nombre = $sanitize->cleanInput($_POST['primer_nombre']);
    $segundo_nombre = $sanitize->cleanInput($_POST['segundo_nombre'] ?? '');
    $primer_apellido = $sanitize->cleanInput($_POST['primer_apellido']);
    $segundo_apellido = $sanitize->cleanInput($_POST['segundo_apellido'] ?? '');
    $sexo = $sanitize->cleanInput($_POST['sexo']);
    $identificacion = $sanitize->cleanInput($_POST['identificacion']);

    // ✅ NUEVO: Usuario (username)
    $username = $sanitize->cleanInput($_POST['username'] ?? '');

    $fecha_nacimiento = $sanitize->cleanInput($_POST['fecha_nacimiento']);
    $email = $sanitize->cleanInput($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    $errores = [];

    // Validaciones
    if (empty($primer_nombre)) $errores[] = "El primer nombre es requerido";
    if (empty($primer_apellido)) $errores[] = "El primer apellido es requerido";
    if (empty($sexo)) $errores[] = "El sexo es requerido";
    if (empty($identificacion)) $errores[] = "La identificación es requerida";
    if (empty($fecha_nacimiento)) $errores[] = "La fecha de nacimiento es requerida";

    // ✅ NUEVO: validar username
    if (empty($username)) {
        $errores[] = "El usuario es requerido";
    } else {
        // 4-20 caracteres: letras, números, punto, guion, guion bajo
        if (!preg_match('/^[a-zA-Z0-9._-]{4,20}$/', $username)) {
            $errores[] = "El usuario debe tener 4 a 20 caracteres (letras, números, . _ -)";
        }
    }

    if (!empty($email) && !$sanitize->validateEmail($email)) {
        $errores[] = "El correo electrónico no es válido";
    }

    if (empty($password)) $errores[] = "La contraseña es requerida";
    if (strlen($password) < 8) $errores[] = "La contraseña debe tener al menos 8 caracteres";
    if ($password !== $confirm_password) $errores[] = "Las contraseñas no coinciden";

    // Manejo de archivo (foto)
    $foto_perfil = null;
    if (isset($_FILES['foto_perfil']) && $_FILES['foto_perfil']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['foto_perfil'];
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        $max_size = 2 * 1024 * 1024; // 2MB

        if (!in_array($file['type'], $allowed_types)) {
            $errores[] = "Solo se permiten imágenes JPG, PNG o GIF";
        } elseif ($file['size'] > $max_size) {
            $errores[] = "La imagen no debe exceder los 2MB";
        } else {
            $upload_dir = '../uploads/perfiles/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }

            $file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $file_name = 'perfil_' . $identificacion . '_' . time() . '.' . $file_extension;
            $file_path = $upload_dir . $file_name;

            if (move_uploaded_file($file['tmp_name'], $file_path)) {
                $foto_perfil = $file_name;
            } else {
                $errores[] = "Error al subir la imagen";
            }
        }
    }

    if (empty($errores)) {
        try {
            $database = new Database();
            $db = $database->getConnection();

            // Verificar si identificación ya existe
            $query = "SELECT id_colaborador FROM colaboradores WHERE identificacion = :identificacion";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':identificacion', $identificacion);
            $stmt->execute();

            if ($stmt->rowCount() > 0) {
                $errores[] = "Esta identificación ya está registrada";
            } else {
                // Verificar si email ya existe (si se proporcionó)
                if (!empty($email)) {
                    $query = "SELECT id_colaborador FROM colaboradores WHERE email = :email";
                    $stmt = $db->prepare($query);
                    $stmt->bindParam(':email', $email);
                    $stmt->execute();

                    if ($stmt->rowCount() > 0) {
                        $errores[] = "Este correo electrónico ya está registrado";
                    }
                }
            }

            // ✅ NUEVO: verificar si username ya existe
            if (empty($errores)) {
                $query = "SELECT id_colaborador FROM colaboradores WHERE username = :username";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':username', $username);
                $stmt->execute();

                if ($stmt->rowCount() > 0) {
                    $errores[] = "Este usuario ya está registrado";
                }
            }

            if (empty($errores)) {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);

                // ✅ NUEVO: insertar username también
                $query = "INSERT INTO colaboradores (primer_nombre, segundo_nombre, primer_apellido, segundo_apellido,
                         sexo, identificacion, username, fecha_nacimiento, foto_perfil, email, password)
                         VALUES (:primer_nombre, :segundo_nombre, :primer_apellido, :segundo_apellido,
                         :sexo, :identificacion, :username, :fecha_nacimiento, :foto_perfil, :email, :password)";

                $stmt = $db->prepare($query);

                $stmt->bindParam(':primer_nombre', $primer_nombre);
                $stmt->bindParam(':segundo_nombre', $segundo_nombre);
                $stmt->bindParam(':primer_apellido', $primer_apellido);
                $stmt->bindParam(':segundo_apellido', $segundo_apellido);
                $stmt->bindParam(':sexo', $sexo);
                $stmt->bindParam(':identificacion', $identificacion);
                $stmt->bindParam(':username', $username);
                $stmt->bindParam(':fecha_nacimiento', $fecha_nacimiento);
                $stmt->bindParam(':foto_perfil', $foto_perfil);
                $stmt->bindParam(':email', $email);
                $stmt->bindParam(':password', $hashed_password);

                if ($stmt->execute()) {
                    $user_id = $db->lastInsertId();

                    $_SESSION['user_id'] = $user_id;
                    $_SESSION['user_tipo'] = 'colaborador';
                    $_SESSION['user_email'] = $email;
                    $_SESSION['user_nombre'] = $primer_nombre . ' ' . $primer_apellido;
                    $_SESSION['user_apellido'] = $primer_apellido;
                    $_SESSION['user_identificacion'] = $identificacion;
                    $_SESSION['user_username'] = $username;

                    header("Location: index.php");
                    exit();
                } else {
                    $errores[] = "Error al registrar el colaborador. Por favor, intenta nuevamente.";
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
    <title>Registro de Colaborador - HelpDesk</title>
    <link rel="stylesheet" href="../css/styles.css">
</head>
<body>
<header class="header">
    <div class="container">
        <nav class="navbar">
            <div class="logo">
                <h2>HelpDesk - Registro de Colaborador</h2>
            </div>
            <ul class="nav-menu">
                <li><a href="index.php">Inicio</a></li>
                <li><a href="login.php">Iniciar Sesión</a></li>
            </ul>
        </nav>
    </div>
</header>

<main class="container">
    <div class="form-container">
        <h2>Registro de Colaborador/Estudiante</h2>

        <?php if (!empty($errores)): ?>
            <div class="error-message">
                <ul>
                    <?php foreach ($errores as $error): ?>
                        <li><?php echo $error; ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="POST" action="" enctype="multipart/form-data">
            <div class="form-row">
                <div class="form-group">
                    <label for="primer_nombre">Primer Nombre</label>
                    <input type="text" id="primer_nombre" name="primer_nombre"
                           value="<?php echo isset($_POST['primer_nombre']) ? htmlspecialchars($_POST['primer_nombre']) : ''; ?>"
                           required maxlength="50">
                </div>

                <div class="form-group">
                    <label for="segundo_nombre">Segundo Nombre</label>
                    <input type="text" id="segundo_nombre" name="segundo_nombre"
                           value="<?php echo isset($_POST['segundo_nombre']) ? htmlspecialchars($_POST['segundo_nombre']) : ''; ?>"
                           maxlength="50">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="primer_apellido">Primer Apellido</label>
                    <input type="text" id="primer_apellido" name="primer_apellido"
                           value="<?php echo isset($_POST['primer_apellido']) ? htmlspecialchars($_POST['primer_apellido']) : ''; ?>"
                           required maxlength="50">
                </div>

                <div class="form-group">
                    <label for="segundo_apellido">Segundo Apellido</label>
                    <input type="text" id="segundo_apellido" name="segundo_apellido"
                           value="<?php echo isset($_POST['segundo_apellido']) ? htmlspecialchars($_POST['segundo_apellido']) : ''; ?>"
                           maxlength="50">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="sexo">Sexo</label>
                    <select id="sexo" name="sexo" required>
                        <option value="">Seleccione...</option>
                        <option value="M" <?php echo (isset($_POST['sexo']) && $_POST['sexo'] == 'M') ? 'selected' : ''; ?>>Masculino</option>
                        <option value="F" <?php echo (isset($_POST['sexo']) && $_POST['sexo'] == 'F') ? 'selected' : ''; ?>>Femenino</option>
                        <option value="Otro" <?php echo (isset($_POST['sexo']) && $_POST['sexo'] == 'Otro') ? 'selected' : ''; ?>>Otro</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="identificacion">Identificación</label>
                    <input type="text" id="identificacion" name="identificacion"
                           value="<?php echo isset($_POST['identificacion']) ? htmlspecialchars($_POST['identificacion']) : ''; ?>"
                           required maxlength="20" placeholder="Cédula, pasaporte, etc.">
                </div>
            </div>

            <!-- ✅ NUEVO: Usuario -->
            <div class="form-row">
                <div class="form-group">
                    <label for="username">Usuario</label>
                    <input type="text" id="username" name="username"
                           value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>"
                           required maxlength="20" placeholder="Ej: juan.perez">
                    <small style="display:block;margin-top:0.5rem;color:#666;">
                        4-20 caracteres: letras, números, . _ -
                    </small>
                </div>

                <div class="form-group">
                    <label for="fecha_nacimiento">Fecha de Nacimiento</label>
                    <input type="date" id="fecha_nacimiento" name="fecha_nacimiento"
                           value="<?php echo isset($_POST['fecha_nacimiento']) ? htmlspecialchars($_POST['fecha_nacimiento']) : ''; ?>"
                           required>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="email">Correo Electrónico </label>
                    <input type="email" id="email" name="email"
                           value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
                           maxlength="100" placeholder="ejemplo@dominio.com">
                </div>
            </div>

            <div class="form-group">
                <label for="foto_perfil">Foto de Perfil </label>
                <input type="file" id="foto_perfil" name="foto_perfil"
                       accept="image/jpeg,image/png,image/gif">
                <small style="display: block; margin-top: 0.5rem; color: #666;">Formatos permitidos: JPG, PNG, GIF (Máximo 2MB)</small>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="password">Contraseña (mínimo 8 caracteres)</label>
                    <input type="password" id="password" name="password"
                           required minlength="8" placeholder="">
                </div>

                <div class="form-group">
                    <label for="confirm_password">Confirmar Contraseña </label>
                    <input type="password" id="confirm_password" name="confirm_password"
                           required minlength="8" placeholder="">
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Registrarse</button>
                <a href="index.php" class="btn btn-secondary">Cancelar</a>
                <a href="login.php" class="btn btn-outline">Ya tengo cuenta</a>
            </div>

            <p style="text-align: center; margin-top: 1.5rem; color: #666; font-size: 0.9rem;">
                * Campos obligatorios
            </p>
        </form>
    </div>
</main>
</body>
</html>