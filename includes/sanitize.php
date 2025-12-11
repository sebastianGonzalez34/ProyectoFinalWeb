<?php
class Sanitize {
    public static function cleanInput($data) {
        if (is_array($data)) {
            return array_map([self::class, 'cleanInput'], $data);
        }
        
        $data = trim($data);
        $data = stripslashes($data);
        $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
        return $data;
    }

    public static function validateEmail($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL);
    }

    public static function validateDate($date, $format = 'Y-m-d') {
        $d = DateTime::createFromFormat($format, $date);
        return $d && $d->format($format) === $date;
    }

    public static function getClientIP() {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            return $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            return $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            return $_SERVER['REMOTE_ADDR'];
        }
    }

    public static function sanitizeSQL($data, $connection) {
        if (is_array($data)) {
            return array_map(function($item) use ($connection) {
                return self::sanitizeSQL($item, $connection);
            }, $data);
        }
        
        $data = self::cleanInput($data);
        // Si tienes una conexión MySQLi, puedes usar mysqli_real_escape_string
        // return mysqli_real_escape_string($connection, $data);
        return $data; // Para PDO, usamos parámetros preparados en su lugar
    }
}
?>