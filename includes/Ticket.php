<?php
class Ticket {
    private $conn;
    private $table_name = "tickets";

    public $id_ticket;
    public $id_colaborador;
    public $id_categoria;
    public $id_agente_asignado;
    public $titulo;
    public $descripcion;
    public $estado;
    public $ip_solicitud;
    public $fecha_creacion;
    public $fecha_cierre;
    public $comentario_cierre;

    public function __construct($db) {
        $this->conn = $db;
    }

    // Crear ticket
    public function create() {
        $query = "INSERT INTO " . $this->table_name . " 
                  SET id_colaborador=:id_colaborador, id_categoria=:id_categoria, 
                  titulo=:titulo, descripcion=:descripcion, ip_solicitud=:ip_solicitud";
        
        $stmt = $this->conn->prepare($query);
        
        // Sanitizar usando htmlspecialchars en lugar de Sanitize::cleanInput
        $this->titulo = htmlspecialchars(strip_tags($this->titulo));
        $this->descripcion = htmlspecialchars(strip_tags($this->descripcion));
        
        // Vincular valores
        $stmt->bindParam(":id_colaborador", $this->id_colaborador);
        $stmt->bindParam(":id_categoria", $this->id_categoria);
        $stmt->bindParam(":titulo", $this->titulo);
        $stmt->bindParam(":descripcion", $this->descripcion);
        $stmt->bindParam(":ip_solicitud", $this->ip_solicitud);
        
        if($stmt->execute()) {
            return true;
        }
        return false;
    }

    // Asignar agente a ticket
    public function asignarAgente() {
        $query = "UPDATE " . $this->table_name . " 
                  SET id_agente_asignado=:id_agente_asignado, estado='En proceso' 
                  WHERE id_ticket=:id_ticket";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id_agente_asignado", $this->id_agente_asignado);
        $stmt->bindParam(":id_ticket", $this->id_ticket);
        
        return $stmt->execute();
    }

    // Cerrar ticket
    public function cerrarTicket() {
        $query = "UPDATE " . $this->table_name . " 
                  SET estado='Cerrado', comentario_cierre=:comentario_cierre, 
                  fecha_cierre=NOW(), tiempo_esperado=TIMEDIFF(NOW(), fecha_creacion) 
                  WHERE id_ticket=:id_ticket";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":comentario_cierre", $this->comentario_cierre);
        $stmt->bindParam(":id_ticket", $this->id_ticket);
        
        return $stmt->execute();
    }

    // Leer tickets con filtros y paginación
    public function readAll($from_record_num, $records_per_page, $filters = []) {
        $query = "SELECT t.*, c.primer_nombre, c.primer_apellido, cat.nombre as categoria_nombre, 
                         u.username as agente_asignado
                  FROM " . $this->table_name . " t
                  LEFT JOIN colaboradores c ON t.id_colaborador = c.id_colaborador
                  LEFT JOIN categorias_ticket cat ON t.id_categoria = cat.id_categoria
                  LEFT JOIN usuarios u ON t.id_agente_asignado = u.id_usuario
                  WHERE 1=1";
        
        // Aplicar filtros
        if (!empty($filters['estado'])) {
            $query .= " AND t.estado = :estado";
        }
        if (!empty($filters['categoria'])) {
            $query .= " AND t.id_categoria = :categoria";
        }
        if (!empty($filters['fecha_desde'])) {
            $query .= " AND DATE(t.fecha_creacion) >= :fecha_desde";
        }
        if (!empty($filters['fecha_hasta'])) {
            $query .= " AND DATE(t.fecha_creacion) <= :fecha_hasta";
        }
        
        $query .= " ORDER BY t.fecha_creacion DESC LIMIT ?, ?";
        
        $stmt = $this->conn->prepare($query);
        
        // Vincular filtros
        $param_count = 1;
        if (!empty($filters['estado'])) {
            $stmt->bindParam(':estado', $filters['estado']);
            $param_count++;
        }
        if (!empty($filters['categoria'])) {
            $stmt->bindParam(':categoria', $filters['categoria']);
            $param_count++;
        }
        if (!empty($filters['fecha_desde'])) {
            $stmt->bindParam(':fecha_desde', $filters['fecha_desde']);
            $param_count++;
        }
        if (!empty($filters['fecha_hasta'])) {
            $stmt->bindParam(':fecha_hasta', $filters['fecha_hasta']);
            $param_count++;
        }
        
        $stmt->bindParam($param_count++, $from_record_num, PDO::PARAM_INT);
        $stmt->bindParam($param_count, $records_per_page, PDO::PARAM_INT);
        
        $stmt->execute();
        return $stmt;
    }

    // Contar tickets con filtros
    public function countAll($filters = []) {
        $query = "SELECT COUNT(*) as total_rows FROM " . $this->table_name . " t WHERE 1=1";
        
        if (!empty($filters['estado'])) {
            $query .= " AND t.estado = :estado";
        }
        if (!empty($filters['categoria'])) {
            $query .= " AND t.id_categoria = :categoria";
        }
        if (!empty($filters['fecha_desde'])) {
            $query .= " AND DATE(t.fecha_creacion) >= :fecha_desde";
        }
        if (!empty($filters['fecha_hasta'])) {
            $query .= " AND DATE(t.fecha_creacion) <= :fecha_hasta";
        }
        
        $stmt = $this->conn->prepare($query);
        
        if (!empty($filters['estado'])) {
            $stmt->bindParam(':estado', $filters['estado']);
        }
        if (!empty($filters['categoria'])) {
            $stmt->bindParam(':categoria', $filters['categoria']);
        }
        if (!empty($filters['fecha_desde'])) {
            $stmt->bindParam(':fecha_desde', $filters['fecha_desde']);
        }
        if (!empty($filters['fecha_hasta'])) {
            $stmt->bindParam(':fecha_hasta', $filters['fecha_hasta']);
        }
        
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $row['total_rows'];
    }

    // Leer un ticket por ID
    public function readOne() {
        $query = "SELECT t.*, c.*, cat.nombre as categoria_nombre, u.username as agente_asignado 
                 FROM " . $this->table_name . " t 
                 LEFT JOIN colaboradores c ON t.id_colaborador = c.id_colaborador 
                 LEFT JOIN categorias_ticket cat ON t.id_categoria = cat.id_categoria 
                 LEFT JOIN usuarios u ON t.id_agente_asignado = u.id_usuario 
                 WHERE t.id_ticket = ? LIMIT 1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->id_ticket);
        $stmt->execute();
        
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if($row) {
            $this->id_ticket = $row['id_ticket'];
            $this->id_colaborador = $row['id_colaborador'];
            $this->id_categoria = $row['id_categoria'];
            $this->id_agente_asignado = $row['id_agente_asignado'];
            $this->titulo = $row['titulo'];
            $this->descripcion = $row['descripcion'];
            $this->estado = $row['estado'];
            $this->ip_solicitud = $row['ip_solicitud'];
            $this->fecha_creacion = $row['fecha_creacion'];
            $this->fecha_cierre = $row['fecha_cierre'];
            $this->comentario_cierre = $row['comentario_cierre'];
            return true;
        }
        return false;
    }
}
?>