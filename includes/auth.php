<?php
class Auth {
    private $conn;
    private $table_name = "usuarios";

    public function __construct($db) {
        $this->conn = $db;
    }

    public function login($username, $password) {
        $query = "SELECT id_usuario, username, password, rol FROM " . $this->table_name . " 
                  WHERE username = :username AND activo = 1 LIMIT 1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':username', $username);
        $stmt->execute();

        if ($stmt->rowCount() == 1) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if (password_verify($password, $row['password'])) {
                $_SESSION['user_id'] = $row['id_usuario'];
                $_SESSION['username'] = $row['username'];
                $_SESSION['rol'] = $row['rol'];
                return true;
            }
        }
        return false;
    }

    public function isLoggedIn() {
        return isset($_SESSION['user_id']);
    }

    public function redirectIfNotLogged($role = null) {
        if (!$this->isLoggedIn()) {
            header("Location: ../index.php");
            exit();
        }
        if ($role && $_SESSION['rol'] != $role) {
            header("Location: unauthorized.php"); // CREAR ESTE ARCHIVO
            exit();
        }
    }

    public function logout() {
        session_destroy();
        header("Location: ../index.php");
        exit();
    }
}
?>