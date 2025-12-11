<?php
class ProdutoBusca {
    private $conn;
    private $termo_busca;
    private $filtro_estado;
    private $filtro_tamanho;
    private $filtro_marca;
    private $idCategoria;

    public function __construct($conn, $params = []) {
        $this->conn = $conn;
        $this->termo_busca = $params['busca'] ?? '';
        $this->filtro_estado = $params['filtro_estado'] ?? '';
        $this->filtro_tamanho = $params['filtro_tamanho'] ?? '';
        $this->filtro_marca = $params['filtro_marca'] ?? '';
        $this->idCategoria = $params['idCategoria'] ?? 0;
    }

    public function buscar() {
        $sql = "SELECT p.* FROM produtos p WHERE p.vendido = 0 ";
        $params = [];

        if ($this->idCategoria != 0) {
            $sql .= "AND p.idCategoria = ? ";
            $params[] = $this->idCategoria;
        }
        
        if (!empty($this->termo_busca)) {
            $sql .= "AND (p.nome LIKE ? OR p.descricao LIKE ? OR p.marca LIKE ?) ";
            $searchTerm = "%{$this->termo_busca}%";
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }
        
        if (!empty($this->filtro_estado)) {
            $sql .= "AND p.estado = ? ";
            $params[] = $this->filtro_estado;
        }
        
        if (!empty($this->filtro_tamanho)) {
            $sql .= "AND p.tamanho = ? ";
            $params[] = $this->filtro_tamanho;
        }
        
        if (!empty($this->filtro_marca)) {
            $sql .= "AND p.marca = ? ";
            $params[] = $this->filtro_marca;
        }
        
        $sql .= "ORDER BY p.idProduto DESC";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function buscarPromocoes() {
        $produtos = $this->buscar();
        return array_filter($produtos, function($produto) {
            return $produto['promocao'] == 1;
        });
    }

    public function getTermoBusca() {
        return $this->termo_busca;
    }

    public function getFiltros() {
        return [
            'estado' => $this->filtro_estado,
            'tamanho' => $this->filtro_tamanho,
            'marca' => $this->filtro_marca
        ];
    }
}
?>