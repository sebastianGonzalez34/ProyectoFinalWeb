<?php
class Encuesta {
    private $conn;
    private $table_name = "encuestas_satisfaccion";

    public $id_encuesta;
    public $id_ticket;
    public $nivel_satisfaccion;
    public $comentario;
    public $fecha_encuesta;

    public function __construct($db) {
        $this->conn = $db;
    }

    // Crear encuesta
    public function create() {
        $query = "INSERT INTO " . $this->table_name . " 
                  SET id_ticket=:id_ticket, nivel_satisfaccion=:nivel_satisfaccion, 
                  comentario=:comentario";
        
        $stmt = $this->conn->prepare($query);
        
        // Sanitizar
        $this->comentario = htmlspecialchars(strip_tags($this->comentario));
        
        // Vincular valores
        $stmt->bindParam(":id_ticket", $this->id_ticket);
        $stmt->bindParam(":nivel_satisfaccion", $this->nivel_satisfaccion);
        $stmt->bindParam(":comentario", $this->comentario);
        
        if($stmt->execute()) {
            return true;
        }
        return false;
    }

    // Verificar si ya existe encuesta para un ticket
    public function existeEncuesta($id_ticket) {
        $query = "SELECT id_encuesta FROM " . $this->table_name . " 
                  WHERE id_ticket = ? LIMIT 1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $id_ticket);
        $stmt->execute();
        
        return $stmt->rowCount() > 0;
    }

    // Obtener encuesta por ticket
    public function getByTicket($id_ticket) {
        $query = "SELECT * FROM " . $this->table_name . " 
                  WHERE id_ticket = ? LIMIT 1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $id_ticket);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
?>