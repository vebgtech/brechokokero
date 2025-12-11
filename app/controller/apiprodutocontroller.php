<?php
require_once __DIR__ . '/../config/conexao.php';

class ApiProdutoController {
    private $conn;
    
    public function __construct($conn) {
        $this->conn = $conn;
    }
    
    public function getProdutosAleatorios($limit = 6) {
        try {
            $sql = "SELECT * FROM produtos WHERE vendido = 0 ORDER BY RAND()";
            if ($limit > 0) {
                $sql .= " LIMIT " . (int)$limit;
            }
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute();
            $produtos = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return json_encode([
                'success' => true,
                'data' => $produtos,
                'count' => count($produtos)
            ]);
            
        } catch (PDOException $e) {
            error_log("Erro ao buscar produtos: " . $e->getMessage());
            return json_encode([
                'success' => false,
                'error' => 'Erro ao buscar produtos'
            ]);
        }
    }
    
    public function buscarProdutos($idCategoria = 0, $termoBusca = '', $filtroEstado = '', $filtroTamanho = '', $filtroMarca = '') {
        try {
            $sql = "SELECT p.* FROM produtos p WHERE (p.vendido=0 OR (p.vendido=1 AND p.data_venda > DATE_SUB(NOW(), INTERVAL 7 DAY))) ";
            $params = [];

            if ($idCategoria != 0) {
                $sql .= "AND p.idCategoria = ? ";
                $params[] = $idCategoria;
            }
            if (!empty($termoBusca)) {
                $sql .= "AND p.nome LIKE ? ";
                $params[] = "%$termoBusca%";
            }
            if (!empty($filtroEstado)) {
                $sql .= "AND p.estado = ? ";
                $params[] = $filtroEstado;
            }
            if (!empty($filtroTamanho)) {
                $sql .= "AND p.tamanho = ? ";
                $params[] = $filtroTamanho;
            }
            if (!empty($filtroMarca)) {
                $sql .= "AND p.marca = ? ";
                $params[] = $filtroMarca;
            }
            $sql .= "ORDER BY p.idProduto DESC";

            $stmt = $this->conn->prepare($sql);
            if (!empty($params)) {
                $stmt->execute($params);
            } else {
                $stmt->execute();
            }
            
            $produtos = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return json_encode([
                'success' => true,
                'data' => $produtos,
                'count' => count($produtos)
            ]);
            
        } catch (PDOException $e) {
            error_log("Erro ao buscar produtos: " . $e->getMessage());
            return json_encode([
                'success' => false,
                'error' => 'Erro ao buscar produtos'
            ]);
        }
    }
}