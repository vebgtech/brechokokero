<?php
// app/model/ProdutoDAO.php

require_once 'produto.php';

class ProdutoDAO
{
    private $conn;

    public function __construct($conn)
    {
        $this->conn = $conn;
    }

    private function arrayParaObjeto($array)
    {
        if (!$array) return null;

        return new Produto(
            $array['nome'] ?? '',
            $array['marca'] ?? 'Sem Marca',
            $array['tamanho'] ?? '',
            $array['estado'] ?? '',
            $array['idCategoria'] ?? 0,
            $array['preco'] ?? 0.0,
            $array['imagem'] ?? 'default.jpg',
            (bool)($array['promocao'] ?? false),
            $array['estoque'] ?? 1,
            $array['descricao'] ?? '',
            (bool)($array['vendido'] ?? false),
            $array['data_venda'] ?? null,
            $array['idProduto'] ?? null
        );
    }

    public function criar(Produto $produto)
    {
        $sql = "INSERT INTO produtos (nome, marca, tamanho, idCategoria, descricao, preco, promocao, imagem, estoque, estado, vendido, data_venda) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0, NULL)";

        try {
            $stmt = $this->conn->prepare($sql);
            $result = $stmt->execute([
                $produto->getNome(),
                $produto->getMarca(),
                $produto->getTamanho(),
                $produto->getIdCategoria(),
                $produto->getDescricao(),
                $produto->getPreco(),
                $produto->isPromocao() ? 1 : 0,
                $produto->getImagem(),
                $produto->getEstoque(),
                $produto->getEstado()
            ]);

            if ($result) {
                $produto->setIdProduto($this->conn->lastInsertId());
                return true;
            }
            return false;
        } catch (PDOException $e) {
            error_log("Erro ao criar produto: " . $e->getMessage());
            throw new Exception("Erro ao cadastrar produto: " . $e->getMessage());
        }
    }

    public function atualizar(Produto $produto)
    {
        $sql = "UPDATE produtos SET nome=?, marca=?, tamanho=?, idCategoria=?, descricao=?, preco=?, promocao=?, imagem=?, estoque=?, estado=? 
                WHERE idProduto=? AND vendido=0";

        try {
            $stmt = $this->conn->prepare($sql);
            return $stmt->execute([
                $produto->getNome(),
                $produto->getMarca(),
                $produto->getTamanho(),
                $produto->getIdCategoria(),
                $produto->getDescricao(),
                $produto->getPreco(),
                $produto->isPromocao() ? 1 : 0,
                $produto->getImagem(),
                $produto->getEstoque(),
                $produto->getEstado(),
                $produto->getIdProduto()
            ]);
        } catch (PDOException $e) {
            error_log("Erro ao atualizar produto: " . $e->getMessage());
            throw new Exception("Erro ao editar produto: " . $e->getMessage());
        }
    }

    public function deletar($idProduto)
    {
        try {
            // Busca imagem para excluir arquivo
            $sql = "SELECT imagem FROM produtos WHERE idProduto=? AND vendido=0";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$idProduto]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            // Exclui do banco
            $sql = "DELETE FROM produtos WHERE idProduto=? AND vendido=0";
            $stmt = $this->conn->prepare($sql);
            $result = $stmt->execute([$idProduto]);

            return ['result' => $result, 'imagem' => $row['imagem'] ?? null];
        } catch (PDOException $e) {
            error_log("Erro ao deletar produto: " . $e->getMessage());
            throw new Exception("Erro ao excluir produto: " . $e->getMessage());
        }
    }

    public function marcarComoVendido($idProduto)
    {
        $sql = "UPDATE produtos SET vendido=1, data_venda=NOW() WHERE idProduto=? AND vendido=0";

        try {
            $stmt = $this->conn->prepare($sql);
            return $stmt->execute([$idProduto]);
        } catch (PDOException $e) {
            error_log("Erro ao marcar como vendido: " . $e->getMessage());
            throw new Exception("Erro ao marcar produto como vendido: " . $e->getMessage());
        }
    }

    public function buscarPorId($idProduto)
    {
        $sql = "SELECT * FROM produtos WHERE idProduto = ?";

        try {
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$idProduto]);
            $array = $stmt->fetch(PDO::FETCH_ASSOC);
            return $this->arrayParaObjeto($array);
        } catch (PDOException $e) {
            error_log("Erro ao buscar produto: " . $e->getMessage());
            return null;
        }
    }

    public function listar($filtros = [])
    {
        $query = "SELECT p.* FROM produtos p WHERE 1=1";
        $params = [];

        if (!isset($filtros['incluir_vendidos']) || !$filtros['incluir_vendidos']) {
            $query .= " AND vendido=0";
        }

        if (!empty($filtros['estado'])) {
            $query .= " AND p.estado = ?";
            $params[] = $filtros['estado'];
        }
        if (!empty($filtros['tamanho'])) {
            $query .= " AND p.tamanho = ?";
            $params[] = $filtros['tamanho'];
        }
        if (!empty($filtros['marca'])) {
            $query .= " AND p.marca = ?";
            $params[] = $filtros['marca'];
        }
        if (!empty($filtros['categoria'])) {
            $query .= " AND p.idCategoria = ?";
            $params[] = $filtros['categoria'];
        }

        $query .= " ORDER BY idProduto DESC";

        try {
            $stmt = $this->conn->prepare($query);
            $stmt->execute($params);

            $produtos = [];
            while ($array = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $produtos[] = $this->arrayParaObjeto($array);
            }
            return $produtos;
        } catch (PDOException $e) {
            error_log("Erro na consulta: " . $e->getMessage());
            return [];
        }
    }

    public function getOpcoesFiltro($campo, $incluirVendidos = false)
    { // ← Adicione parâmetro
        $sql = "SELECT DISTINCT $campo FROM produtos WHERE $campo IS NOT NULL";

        if (!$incluirVendidos) {
            $sql .= " AND vendido=0"; // ← Condicional
        }

        $sql .= " ORDER BY $campo";

        try {
            $stmt = $this->conn->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Erro ao buscar opções: " . $e->getMessage());
            return [];
        }
    }

    public function getCategorias()
    {
        $sql = "SELECT idCategoria, descricao FROM categorias ORDER BY idCategoria";

        try {
            $stmt = $this->conn->prepare($sql);
            $stmt->execute();
            $categorias = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (empty($categorias)) {
                $categorias = [
                    ['idCategoria' => 1, 'descricao' => 'Bermudas e Shorts'],
                    ['idCategoria' => 2, 'descricao' => 'Blazers'],
                    ['idCategoria' => 3, 'descricao' => 'Blusas e Camisas'],
                    ['idCategoria' => 4, 'descricao' => 'Calças'],
                    ['idCategoria' => 5, 'descricao' => 'Casacos e Jaquetas'],
                    ['idCategoria' => 6, 'descricao' => 'Conjuntos'],
                    ['idCategoria' => 7, 'descricao' => 'Saias'],
                    ['idCategoria' => 8, 'descricao' => 'Sapatos'],
                    ['idCategoria' => 9, 'descricao' => 'Social'],
                    ['idCategoria' => 10, 'descricao' => 'Vestidos']
                ];
            }
            return $categorias;
        } catch (PDOException $e) {
            error_log("Erro ao buscar categorias: " . $e->getMessage());
            return [];
        }
    }
}
