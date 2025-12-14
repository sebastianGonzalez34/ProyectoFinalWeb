<?php
require_once '../includes/config.php';
require_once '../includes/database.php';
require_once '../includes/auth.php';
require_once '../includes/sanitize.php';

$database = new Database();
$db = $database->getConnection();
$auth = new Auth($db);
$auth->redirectIfNotLogged('admin');

// Instanciar usuario
require_once '../includes/Usuario.php';
$usuario = new Usuario($db);

// Crear usuario
if ($_POST && isset($_POST['crear'])) {
    $usuario->username = $_POST['username'];
    $usuario->password = $_POST['password'];
    $usuario->email = $_POST['email'];
    $usuario->rol = $_POST['rol'];
    $usuario->activo = isset($_POST['activo']) ? 1 : 0;
    
    if ($usuario->create()) {
        $success = "Usuario creado exitosamente";
    } else {
        $error = "Error al crear el usuario";
    }
}

// Actualizar usuario
if ($_POST && isset($_POST['actualizar'])) {
    $usuario->id_usuario = $_POST['id_usuario'];
    $usuario->username = $_POST['username'];
    $usuario->email = $_POST['email'];
    $usuario->rol = $_POST['rol'];
    $usuario->activo = isset($_POST['activo']) ? 1 : 0;
    
    if (!empty($_POST['password'])) {
        $usuario->password = $_POST['password'];
    }
    
    if ($usuario->update()) {
        $success = "Usuario actualizado exitosamente";
    } else {
        $error = "Error al actualizar el usuario";
    }
}

// Eliminar usuario
if (isset($_GET['delete'])) {
    $usuario->id_usuario = $_GET['delete'];
    if ($usuario->delete()) {
        $success = "Usuario eliminado exitosamente";
    } else {
        $error = "Error al eliminar el usuario";
    }
}

// Obtener usuario para editar
$usuario_editar = null;
if (isset($_GET['edit'])) {
    $usuario->id_usuario = $_GET['edit'];
    if ($usuario->readOne()) {
        $usuario_editar = $usuario;
    }
}

// Paginación
$page = isset($_GET['page']) ? $_GET['page'] : 1;
$records_per_page = 10;
$from_record_num = ($records_per_page * $page) - $records_per_page;

// Obtener usuarios
$stmt = $usuario->readAll($from_record_num, $records_per_page);
$usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Total para paginación
$total_rows = $usuario->countAll();
$total_pages = ceil($total_rows / $records_per_page);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Usuarios - HelpDesk</title>
    <link rel="stylesheet" href="../css/styles.css">
</head>
<body>
    <?php 
    include '../includes/header.php'; 
    mostrarHeader('Usuarios');
    ?>

    <div class="dashboard">
        <h1>Gestión de Usuarios</h1>

        <?php if (isset($success)): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <!-- Formulario de usuario -->
        <div class="form-container" style="margin-bottom: 2rem;">
            <h2><?php echo $usuario_editar ? 'Editar Usuario' : 'Crear Nuevo Usuario'; ?></h2>
            
            <form method="POST">
                <?php if ($usuario_editar): ?>
                    <input type="hidden" name="id_usuario" value="<?php echo $usuario_editar->id_usuario; ?>">
                <?php endif; ?>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="username">Usuario *</label>
                        <input type="text" class="form-control" id="username" name="username" 
                               value="<?php echo $usuario_editar ? $usuario_editar->username : ''; ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email *</label>
                        <input type="email" class="form-control" id="email" name="email" 
                               value="<?php echo $usuario_editar ? $usuario_editar->email : ''; ?>" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="password"><?php echo $usuario_editar ? 'Nueva Contraseña' : 'Contraseña *'; ?></label>
                        <input type="password" class="form-control" id="password" name="password" 
                               <?php echo $usuario_editar ? '' : 'required'; ?> minlength="6">
                    </div>
                    
                    <div class="form-group">
                        <label for="rol">Rol *</label>
                        <select class="form-control" id="rol" name="rol" required>
                            <option value="">Seleccionar rol</option>
                            <option value="admin" <?php echo ($usuario_editar && $usuario_editar->rol == 'admin') ? 'selected' : ''; ?>>Administrador</option>
                            <option value="agente" <?php echo ($usuario_editar && $usuario_editar->rol == 'agente') ? 'selected' : ''; ?>>Agente de TI</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label>
                        <input type="checkbox" name="activo" value="1" 
                               <?php echo ($usuario_editar && $usuario_editar->activo) ? 'checked' : ''; ?>> 
                        Usuario activo
                    </label>
                </div>

                <button type="submit" name="<?php echo $usuario_editar ? 'actualizar' : 'crear'; ?>" 
                        class="btn btn-primary">
                    <?php echo $usuario_editar ? 'Actualizar Usuario' : 'Crear Usuario'; ?>
                </button>
                
                <?php if ($usuario_editar): ?>
                    <a href="usuarios.php" class="btn btn-secondary">Cancelar</a>
                <?php endif; ?>
            </form>
        </div>

        <!-- Lista de usuarios -->
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Usuario</th>
                        <th>Email</th>
                        <th>Rol</th>
                        <th>Estado</th>
                        <th>Fecha Creación</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($usuarios) > 0): ?>
                        <?php foreach($usuarios as $user): ?>
                        <tr>
                            <td><?php echo $user['id_usuario']; ?></td>
                            <td><?php echo htmlspecialchars($user['username']); ?></td>
                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                            <td>
                                <span class="badge badge-<?php echo $user['rol'] == 'admin' ? 'warning' : 'info'; ?>">
                                    <?php echo $user['rol']; ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge badge-<?php echo $user['activo'] ? 'success' : 'danger'; ?>">
                                    <?php echo $user['activo'] ? 'Activo' : 'Inactivo'; ?>
                                </span>
                            </td>
                            <td><?php echo date('d/m/Y H:i', strtotime($user['fecha_creacion'])); ?></td>
                            <td>
                                <a href="usuarios.php?edit=<?php echo $user['id_usuario']; ?>" class="btn btn-primary btn-sm">Editar</a>
                                <a href="usuarios.php?delete=<?php echo $user['id_usuario']; ?>" 
                                   class="btn btn-danger btn-sm" 
                                   onclick="return confirmarEliminacion('¿Está seguro de eliminar este usuario?')">Eliminar</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" style="text-align: center;">No hay usuarios registrados</td>
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
                    <a href="usuarios.php?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                </li>
            <?php endfor; ?>
        </ul>
        <?php endif; ?>
    </div>

    <script src="../js/scripts.js"></script>
</body>
</html>