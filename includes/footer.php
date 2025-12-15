<?php
// Verificar si la página está en una subcarpeta para ajustar rutas
$isInSubfolder = strpos($_SERVER['PHP_SELF'], '/') !== false;
$basePath = $isInSubfolder ? '../' : '';
?>
<footer class="footer">
    <div class="footer-content">
        <p>© <?php echo date('Y'); ?> Universidad Tecnológica de Panamá. Todos los derechos reservados.</p>
    </div>
</footer>