<?php
require_once '../includes/config.php';
require_once '../includes/database.php';
require_once '../includes/sanitize.php';

$database = new Database();
$db = $database->getConnection();

if ($_POST) {
    try {
        // Verificar identificación
        $stmt = $db->prepare("SELECT id_colaborador FROM colaboradores WHERE identificacion = ?");
        $stmt->execute([Sanitize::cleanInput($_POST['identificacion'])]);
        $colaborador = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($colaborador) {
            // Actualizar contraseña
            $new_password = password_hash(Sanitize::cleanInput($_POST['nueva_password']), PASSWORD_DEFAULT);
            $stmt = $db->prepare("UPDATE colaboradores SET password = ? WHERE id_colaborador = ?");
            $stmt->execute([$new_password, $colaborador['id_colaborador']]);
            
            $success = "Contraseña actualizada exitosamente";
        } else {
            $error = "No se encontró un usuario con esa identificación";
        }
    } catch (Exception $e) {
        $error = "Error al cambiar la contraseña: " . $e->getMessage();
    }
}
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
    <?php include '../includes/header.php'; ?>
    
    <div class="container">
        <div class="form-container">
            <h2>Cambiar Contraseña</h2>
            
            <?php if (isset($success)): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <?php if (isset($error)): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-group">
                    <label for="identificacion">Cédula/Identificación *</label>
                    <input type="text" class="form-control" id="identificacion" name="identificacion" required>
                </div>

                <div class="form-group">
                    <label for="nueva_password">Nueva Contraseña *</label>
                    <input type="password" class="form-control" id="nueva_password" name="nueva_password" required minlength="6">
                </div>

                <div class="form-group">
                    <label for="confirmar_password">Confirmar Contraseña *</label>
                    <input type="password" class="form-control" id="confirmar_password" name="confirmar_password" required>
                </div>

                <button type="submit" class="btn btn-primary">Cambiar Contraseña</button>
                <a href="index.php" class="btn" style="background: #6c757d; color: white; margin-left: 1rem;">Cancelar</a>
            </form>
        </div>
    </div>

    <script>
        document.querySelector('form').addEventListener('submit', function(e) {
            const password = document.getElementById('nueva_password').value;
            const confirmPassword = document.getElementById('confirmar_password').value;
            
            if (password !== confirmPassword) {
                e.preventDefault();
                alert('Las contraseñas no coinciden');
            }
        });
    </script>
</body>
</html>