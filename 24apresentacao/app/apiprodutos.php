<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once 'config/conexao.php';

// Classe simples para API de produtos
class ApiProdutos {
    private $conn;
    
    public function __construct($conn) {
        $this->conn = $conn;
    }
    
    public function getProdutosAleatorios($limit = 6) {
        try {
            $sql = "SELECT * FROM produtos WHERE vendido = 0 ORDER BY RAND() LIMIT ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$limit]);
            $produtos = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return [
                'success' => true,
                'data' => $produtos,
                'count' => count($produtos)
            ];
            
        } catch (PDOException $e) {
            error_log("Erro ao buscar produtos: " . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Erro ao buscar produtos'
            ];
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
            
            return [
                'success' => true,
                'data' => $produtos,
                'count' => count($produtos)
            ];
            
        } catch (PDOException $e) {
            error_log("Erro ao buscar produtos: " . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Erro ao buscar produtos'
            ];
        }
    }
}

// Processar requisição
$conn = Conexao::getConexao();
$api = new ApiProdutos($conn);

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'random':
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 6;
        $resultado = $api->getProdutosAleatorios($limit);
        echo json_encode($resultado);
        break;
        
    case 'search':
        $idCategoria = isset($_GET['categoria']) ? (int)$_GET['categoria'] : 0;
        $termoBusca = $_GET['busca'] ?? '';
        $filtroEstado = $_GET['estado'] ?? '';
        $filtroTamanho = $_GET['tamanho'] ?? '';
        $filtroMarca = $_GET['marca'] ?? '';
        
        $resultado = $api->buscarProdutos($idCategoria, $termoBusca, $filtroEstado, $filtroTamanho, $filtroMarca);
        echo json_encode($resultado);
        break;
        
    default:
        echo json_encode([
            'success' => false,
            'error' => 'Ação não especificada'
        ]);
}