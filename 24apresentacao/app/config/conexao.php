<?php
class Conexao extends PDO
{
    private $servidor = "localhost";
    private $usuario = "root";
    private $senha = "";
    private $banco = "brechokokero";
    private static $instancia = null;

    public function __construct()
    {
        $dsn = "mysql:host={$this->servidor};dbname={$this->banco};charset=utf8mb4";
        
        $opcoes = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ];

        try {
            parent::__construct($dsn, $this->usuario, $this->senha, $opcoes);
        } catch (PDOException $e) {
            die("Erro de conexão: " . $e->getMessage());
        }
    }
    
    public static function getConexao()
    {
        if (self::$instancia === null) {
            self::$instancia = new self();
        }
        return self::$instancia;
    }
}
?>