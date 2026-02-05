<?php
class Database {
    private $host = "localhost";
    private $db_name = "herramienta_didactica";
    private $username = "root";
    private $password = "";
    public $conn;

    public function getConnection() {
        $this->conn = null;
        try {
            $this->conn = new mysqli($this->host, $this->username, $this->password, $this->db_name);
            if ($this->conn->connect_error) {
                // Registrar internamente y mostrar un mensaje genérico
                error_log('Error de conexión a la base de datos: ' . $this->conn->connect_error);
                throw new Exception('Error de conexión a la base de datos.');
            }
            // Establecer charset correcto
            if (!$this->conn->set_charset('utf8mb4')) {
                error_log('Error estableciendo charset utf8mb4: ' . $this->conn->error);
                // Continuar, pero es recomendable abortar si el charset es crítico
            }
        } catch (Exception $e) {
            error_log('Excepción de conexión a la base de datos: ' . $e->getMessage());
            throw $e;
        }
        return $this->conn;
    }
}
