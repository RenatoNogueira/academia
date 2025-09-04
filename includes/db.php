<?php
class Database
{
    private $db;

    public function __construct()
    {
        // Cria a conexão com o banco de dados
        $this->db = new SQLite3(__DIR__ . '/../database.db');

        // Configurações de segurança recomendadas para SQLite
        $this->db->enableExceptions(true);
        $this->db->exec('PRAGMA journal_mode = WAL;');
        $this->db->exec('PRAGMA secure_delete = ON;');
    }

    public function getConnection()
    {
        return $this->db;
    }
}

// Exemplo de uso
$database = new Database();
$db = $database->getConnection();