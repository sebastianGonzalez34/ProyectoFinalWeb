<?php
require_once '../includes/config.php';
require_once '../includes/database.php';
require_once '../includes/sanitize.php';

$database = new Database();
$db = $database->getConnection();

// Obtener categorías
$categorias = $db->query("SELECT * FROM categorias_ticket")->fetchAll(PDO::FETCH_ASSOC);

if ($_POST) {
    try {
        // Buscar colaborador por cédula
        $stmt = $db->prepare("SELECT id_colaborador FROM colaboradores WHERE identificacion = ?");
        $stmt->execute([Sanitize::cleanInput($_POST['identificacion'])]);
        $colaborador = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$colaborador) {
            // Crear nuevo colaborador
            $stmt = $db->prepare("INSERT INTO colaboradores (primer_nombre, segundo_nombre, primer_apellido, segundo_apellido, sexo, identificacion, fecha_nacimiento, email, password) 
                                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $password_default = password_hash('123456', PASSWORD_DEFAULT);
            $stmt->execute([
                Sanitize::cleanInput($_POST['primer_nombre']),
                Sanitize::cleanInput($_POST['segundo_nombre']),
                Sanitize::cleanInput($_POST['primer_apellido']),
                Sanitize::cleanInput($_POST['segundo_apellido']),
                Sanitize::cleanInput($_POST['sexo']),
                Sanitize::cleanInput($_POST['identificacion']),
                Sanitize::cleanInput($_POST['fecha_nacimiento']),
                Sanitize::cleanInput($_POST['email']),
                $password_default
            ]);
            $id_colaborador = $db->lastInsertId();
        } else {
            $id_colaborador = $colaborador['id_colaborador'];
        }

        // Crear ticket
        $stmt = $db->prepare("INSERT INTO tickets (id_colaborador, id_categoria, titulo, descripcion, ip_solicitud) 
                             VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([
            $id_colaborador,
            Sanitize::cleanInput($_POST['categoria']),
            Sanitize::cleanInput($_POST['titulo']),
            Sanitize::cleanInput($_POST['descripcion']),
            Sanitize::getClientIP()
        ]);

        $success = "Ticket creado exitosamente. Número de ticket: " . $db->lastInsertId();
    } catch (Exception $e) {
        $error = "Error al crear el ticket: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crear Ticket - HelpDesk</title>
    <link rel="stylesheet" href="../css/styles.css">
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <div class="container">
        <div class="form-container">
            <h2>Crear Nuevo Ticket</h2>
            
            <?php if (isset($success)): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <?php if (isset($error)): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>

            <form method="POST">
                <h3 style="margin-bottom: 1rem;">Datos Personales</h3>
                
                <div class="form-group">
                    <label for="identificacion">Cédula/Identificación *</label>
                    <input type="text" class="form-control" id="identificacion" name="identificacion" required>
                </div>

                <div class="form-row" style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <div class="form-group">
                        <label for="primer_nombre">Primer Nombre *</label>
                        <input type="text" class="form-control" id="primer_nombre" name="primer_nombre" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="segundo_nombre">Segundo Nombre</label>
                        <input type="text" class="form-control" id="segundo_nombre" name="segundo_nombre">
                    </div>
                </div>

                <div class="form-row" style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <div class="form-group">
                        <label for="primer_apellido">Primer Apellido *</label>
                        <input type="text" class="form-control" id="primer_apellido" name="primer_apellido" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="segundo_apellido">Segundo Apellido</label>
                        <input type="text" class="form-control" id="segundo_apellido" name="segundo_apellido">
                    </div>
                </div>

                <div class="form-row" style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <div class="form-group">
                        <label for="sexo">Sexo *</label>
                        <select class="form-control" id="sexo" name="sexo" required>
                            <option value="">Seleccionar</option>
                            <option value="M">Masculino</option>
                            <option value="F">Femenino</option>
                            <option value="Otro">Otro</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="fecha_nacimiento">Fecha de Nacimiento *</label>
                        <input type="date" class="form-control" id="fecha_nacimiento" name="fecha_nacimiento" required>
                    </div>
                </div>

                <div class="form-group">
                    <label for="email">Correo Electrónico *</label>
                    <input type="email" class="form-control" id="email" name="email" required>
                </div>

                <h3 style="margin: 2rem 0 1rem 0;">Información del Ticket</h3>

                <div class="form-group">
                    <label for="categoria">Categoría *</label>
                    <select class="form-control" id="categoria" name="categoria" required>
                        <option value="">Seleccionar categoría</option>
                        <?php foreach($categorias as $cat): ?>
                            <option value="<?php echo $cat['id_categoria']; ?>"><?php echo $cat['nombre']; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="titulo">Título del Ticket *</label>
                    <input type="text" class="form-control" id="titulo" name="titulo" required>
                </div>

                <div class="form-group">
                    <label for="descripcion">Descripción Detallada *</label>
                    <textarea class="form-control" id="descripcion" name="descripcion" rows="5" required></textarea>
                </div>

                <button type="submit" class="btn btn-primary">Crear Ticket</button>
                <a href="index.php" class="btn" style="background: #6c757d; color: white; margin-left: 1rem;">Cancelar</a>
            </form>
        </div>
    </div>
</body>
</html>