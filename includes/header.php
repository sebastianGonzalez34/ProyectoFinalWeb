<?php
function mostrarHeader($pagina_actual = '') {
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema HelpDesk - <?php echo $pagina_actual; ?></title>
    <link rel="stylesheet" href="../css/styles.css">
</head>
<body>
    <header class="header">
        <div class="container">
            <nav class="navbar">
                <div class="logo">
                    <h2>Sistema HelpDesk</h2>
                </div>
                <ul class="nav-menu">
                    <?php if(isset($_SESSION['rol'])): ?>
                        <li><a href="dashboard.php" class="<?php echo $pagina_actual=='Dashboard'?'active':''; ?>">Dashboard</a></li>
                        <?php if($_SESSION['rol'] == 'admin'): ?>
                            <li><a href="usuarios.php" class="<?php echo $pagina_actual=='Usuarios'?'active':''; ?>">Usuarios</a></li>
                            <li><a href="colaboradores.php" class="<?php echo $pagina_actual=='Colaboradores'?'active':''; ?>">Colaboradores</a></li>
                            <li><a href="reportes.php" class="<?php echo $pagina_actual=='Reportes'?'active':''; ?>">Reportes</a></li>
                        <?php endif; ?>
                        <li><a href="tickets.php" class="<?php echo $pagina_actual=='Tickets'?'active':''; ?>">Tickets</a></li>
                        <li><a href="../logout.php">Cerrar Sesión (<?php echo $_SESSION['username']; ?>)</a></li>
                    <?php else: ?>
                        <li><a href="index.php">Inicio</a></li>
                        <li><a href="crear_ticket.php">Crear Ticket</a></li>
                        <li><a href="cambiar_password.php">Cambiar Contraseña</a></li>
                    <?php endif; ?>
                </ul>
            </nav>
        </div>
    </header>
    <main class="container">
<?php
}
?>