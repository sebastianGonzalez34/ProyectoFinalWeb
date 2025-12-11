<?php
require_once 'includes/config.php';

session_destroy();
header("Location: index.php");
exit();
?>