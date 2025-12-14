<?php
class Usuario {
    private $conn;
    private $table_name = "usuarios";

    public $id_usuario;
    public $username;
    public $password;
    public $email;
    public $rol;
    public $activo;

    public function __construct($db) {
        $this->conn = $db;
    }

    // Crear usuario
    public function create() {
        $query = "INSERT INTO " . $this->table_name . " 
                  SET username=:username, password=:password, email=:email, rol=:rol, activo=:activo";
        
        $stmt = $this->conn->prepare($query);
        
        // Sanitizar
        $this->username = htmlspecialchars(strip_tags($this->username));
        $this->email = htmlspecialchars(strip_tags($this->email));
        $this->password = password_hash($this->password, PASSWORD_DEFAULT);
        
        // Vincular valores
        $stmt->bindParam(":username", $this->username);
        $stmt->bindParam(":password", $this->password);
        $stmt->bindParam(":email", $this->email);
        $stmt->bindParam(":rol", $this->rol);
        $stmt->bindParam(":activo", $this->activo);
        
        if($stmt->execute()) {
            return true;
        }
        return false;
    }

    // Leer usuarios con paginación
    public function readAll($from_record_num, $records_per_page) {
        $query = "SELECT id_usuario, username, email, rol, activo, fecha_creacion 
                  FROM " . $this->table_name . " 
                  ORDER BY id_usuario DESC 
                  LIMIT ?, ?";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $from_record_num, PDO::PARAM_INT);
        $stmt->bindParam(2, $records_per_page, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt;
    }

    // Contar total de usuarios
    public function countAll() {
        $query = "SELECT COUNT(*) as total_rows FROM " . $this->table_name;
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $row['total_rows'];
    }

    // Leer un usuario
    public function readOne() {
        $query = "SELECT username, email, rol, activo 
                  FROM " . $this->table_name . " 
                  WHERE id_usuario = ? LIMIT 1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->id_usuario);
        $stmt->execute();
        
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if($row) {
            $this->username = $row['username'];
            $this->email = $row['email'];
            $this->rol = $row['rol'];
            $this->activo = $row['activo'];
            return true;
        }
        return false;
    }

    // Actualizar usuario
    public function update() {
        $query = "UPDATE " . $this->table_name . " 
                  SET username=:username, email=:email, rol=:rol, activo=:activo";
        
        // Si se proporciona nueva contraseña
        if(!empty($this->password)) {
            $query .= ", password=:password";
        }
        
        $query .= " WHERE id_usuario=:id_usuario";
        
        $stmt = $this->conn->prepare($query);
        
        // Sanitizar
        $this->username = htmlspecialchars(strip_tags($this->username));
        $this->email = htmlspecialchars(strip_tags($this->email));
        
        // Vincular valores
        $stmt->bindParam(":username", $this->username);
        $stmt->bindParam(":email", $this->email);
        $stmt->bindParam(":rol", $this->rol);
        $stmt->bindParam(":activo", $this->activo);
        $stmt->bindParam(":id_usuario", $this->id_usuario);
        
        // Password opcional
        if(!empty($this->password)) {
            $this->password = password_hash($this->password, PASSWORD_DEFAULT);
            $stmt->bindParam(":password", $this->password);
        }
        
        if($stmt->execute()) {
            return true;
        }
        return false;
    }

    // Eliminar usuario
    public function delete() {
        $query = "DELETE FROM " . $this->table_name . " WHERE id_usuario = ?";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->id_usuario);
        
        if($stmt->execute()) {
            return true;
        }
        return false;
    }

    // Verificar si username existe
    public function usernameExists() {
        $query = "SELECT id_usuario FROM " . $this->table_name . " WHERE username = ? LIMIT 1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->username);
        $stmt->execute();
        
        if($stmt->rowCount() > 0) {
            return true;
        }
        return false;
    }
}
?>