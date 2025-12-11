<?php
// app/model/produto_dao.php

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

    // ==================== CRUD BÁSICO ====================
    
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
            WHERE idProduto=?";

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
        $sql = "SELECT imagem FROM produtos WHERE idProduto=?"; // REMOVIDO: AND vendido=0
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$idProduto]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        // Exclui do banco
        $sql = "DELETE FROM produtos WHERE idProduto=?"; // REMOVIDO: AND vendido=0
        $stmt = $this->conn->prepare($sql);
        $result = $stmt->execute([$idProduto]);

        return [
            'result' => $result,
            'imagem' => $row['imagem'] ?? null
        ];
    } catch (PDOException $e) {
        error_log("Erro ao deletar produto: " . $e->getMessage());
        throw new Exception("Erro ao excluir produto: " . $e->getMessage());
    }
}

    // ==================== MÉTODOS PARA O ADMINCONTROLLER ====================
    
    /**
     * Alias para criar (compatível com AdminController)
     */
    public function salvar(Produto $produto)
    {
        return $this->criar($produto);
    }

    /**
     * Alias para deletar (compatível com AdminController)
     */
    public function excluir($id)
    {
        $result = $this->deletar($id);
        return $result['result'] ?? false;
    }

    /**
     * Alias para listar (compatível com AdminController)
     */
    public function listarTodos($filtros = [])
    {
        return $this->listar($filtros);
    }

    /**
     * Listar categorias no formato do AdminController
     */
    public function listarCategorias()
    {
        $categorias = $this->getCategorias();
        $categoriasFormatadas = [];
        
        foreach ($categorias as $cat) {
            $categoriasFormatadas[] = [
                'id' => $cat['idCategoria'],
                'nome' => $cat['descricao']
            ];
        }
        
        return $categoriasFormatadas;
    }

    /**
     * Buscar ID da categoria pelo nome - VERSÃO MELHORADA
     */
    public function getCategoriaIdPorNome($nomeCategoria)
    {
        error_log("=== BUSCANDO CATEGORIA ===");
        error_log("Nome recebido: '" . $nomeCategoria . "'");
        error_log("Tipo: " . gettype($nomeCategoria));
        error_log("Comprimento: " . strlen($nomeCategoria));
        
        // Mostrar caracteres hexadecimais para debug
        error_log("Bytes em hex: " . bin2hex($nomeCategoria));
        
        $categorias = $this->getCategorias();
        error_log("Total de categorias: " . count($categorias));
        
        // Método 1: Busca exata (case-sensitive)
        foreach ($categorias as $cat) {
            $catNome = $cat['descricao'];
            error_log("Comparando com: '" . $catNome . "'");
            error_log("São iguais? " . ($catNome === $nomeCategoria ? 'SIM' : 'NÃO'));
            
            if ($catNome === $nomeCategoria) {
                error_log("✅ Encontrada (exato) - ID: " . $cat['idCategoria']);
                return $cat['idCategoria'];
            }
        }
        
        // Método 2: Busca case-insensitive (remove espaços)
        error_log("--- Tentando busca case-insensitive ---");
        $nomeCategoriaTrim = trim($nomeCategoria);
        
        foreach ($categorias as $cat) {
            $catNomeTrim = trim($cat['descricao']);
            
            if (strcasecmp($catNomeTrim, $nomeCategoriaTrim) === 0) {
                error_log("✅ Encontrada (case-insensitive) - ID: " . $cat['idCategoria']);
                return $cat['idCategoria'];
            }
        }
        
        // Método 3: Busca sem acentos e case-insensitive
        error_log("--- Tentando busca sem acentos ---");
        $nomeSemAcentos = $this->removerAcentos($nomeCategoriaTrim);
        error_log("Nome sem acentos: '" . $nomeSemAcentos . "'");
        
        foreach ($categorias as $cat) {
            $catNomeSemAcentos = $this->removerAcentos(trim($cat['descricao']));
            error_log("Comparando: '" . $catNomeSemAcentos . "' com '" . $nomeSemAcentos . "'");
            
            if (strcasecmp($catNomeSemAcentos, $nomeSemAcentos) === 0) {
                error_log("✅ Encontrada (sem acentos) - ID: " . $cat['idCategoria']);
                return $cat['idCategoria'];
            }
        }
        
        // Método 4: Busca parcial
        error_log("--- Tentando busca parcial ---");
        foreach ($categorias as $cat) {
            $catNomeLower = strtolower(trim($cat['descricao']));
            $nomeCategoriaLower = strtolower($nomeCategoriaTrim);
            
            if (strpos($catNomeLower, $nomeCategoriaLower) !== false || 
                strpos($nomeCategoriaLower, $catNomeLower) !== false) {
                error_log("✅ Encontrada (parcial) - ID: " . $cat['idCategoria']);
                return $cat['idCategoria'];
            }
        }
        
        error_log("❌ Categoria não encontrada: " . $nomeCategoria);
        
        // Listar todas as categorias para debug
        error_log("Categorias disponíveis:");
        foreach ($categorias as $cat) {
            error_log("  - '" . $cat['descricao'] . "' (ID: " . $cat['idCategoria'] . ")");
        }
        
        return null;
    }
    
    /**
     * Método auxiliar para remover acentos
     */
    private function removerAcentos($string)
    {
        if (!is_string($string)) {
            return $string;
        }
        
        // Converter para UTF-8 se necessário
        if (!mb_detect_encoding($string, 'UTF-8', true)) {
            $string = utf8_encode($string);
        }
        
        $acentos = array(
            'á', 'à', 'ã', 'â', 'ä', 'é', 'è', 'ê', 'ë', 'í', 'ì', 'î', 'ï',
            'ó', 'ò', 'õ', 'ô', 'ö', 'ú', 'ù', 'û', 'ü', 'ç', 'ñ',
            'Á', 'À', 'Ã', 'Â', 'Ä', 'É', 'È', 'Ê', 'Ë', 'Í', 'Ì', 'Î', 'Ï',
            'Ó', 'Ò', 'Õ', 'Ô', 'Ö', 'Ú', 'Ù', 'Û', 'Ü', 'Ç', 'Ñ'
        );
        
        $semAcentos = array(
            'a', 'a', 'a', 'a', 'a', 'e', 'e', 'e', 'e', 'i', 'i', 'i', 'i',
            'o', 'o', 'o', 'o', 'o', 'u', 'u', 'u', 'u', 'c', 'n',
            'A', 'A', 'A', 'A', 'A', 'E', 'E', 'E', 'E', 'I', 'I', 'I', 'I',
            'O', 'O', 'O', 'O', 'O', 'U', 'U', 'U', 'U', 'C', 'N'
        );
        
        return str_replace($acentos, $semAcentos, $string);
    }

    /**
     * Versão alternativa do getCategoriaIdPorNome que é mais flexível
     */
    public function getCategoriaIdPorNomeFlexivel($nomeCategoria)
    {
        // Primeiro tenta o método normal
        $categoriaId = $this->getCategoriaIdPorNome($nomeCategoria);
        
        if ($categoriaId) {
            return $categoriaId;
        }
        
        // Se não encontrou, tenta mapeamentos comuns
        $mapeamentos = [
            'Calcas' => 'Calças',
            'Calca' => 'Calças',
            'calças' => 'Calças',
            'calcas' => 'Calças',
            'Calcas ' => 'Calças',
            ' Calças' => 'Calças',
            'Calças ' => 'Calças'
        ];
        
        if (isset($mapeamentos[$nomeCategoria])) {
            error_log("🔧 Aplicando mapeamento: '" . $nomeCategoria . "' -> '" . $mapeamentos[$nomeCategoria] . "'");
            return $this->getCategoriaIdPorNome($mapeamentos[$nomeCategoria]);
        }
        
        return null;
    }

    /**
     * Listar estados disponíveis
     */
    public function listarEstados()
    {
        return ['Novo', 'Semi-novo', 'Usado'];
    }

    /**
     * Listar tamanhos disponíveis
     */
    public function listarTamanhos()
    {
        $sql = "SELECT DISTINCT tamanho FROM produtos WHERE tamanho IS NOT NULL AND tamanho != '' ORDER BY tamanho";
        try {
            $stmt = $this->conn->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_COLUMN);
        } catch (PDOException $e) {
            error_log("Erro ao listar tamanhos: " . $e->getMessage());
            return ['PP', 'P', 'M', 'G', 'GG', 'XG', 'XXG', 'Único'];
        }
    }

    /**
     * Listar marcas disponíveis
     */
    public function listarMarcas()
    {
        return $this->getOpcoesFiltro('marca', true);
    }

    // ==================== MÉTODOS ESPECÍFICOS ====================
    
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

    // ==================== BUSCAS ====================
    
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

    public function buscarPorIdComCategoria($idProduto)
    {
        $sql = "SELECT p.*, c.descricao as nome_categoria 
                FROM produtos p 
                LEFT JOIN categorias c ON p.idCategoria = c.idCategoria 
                WHERE p.idProduto = ?";

        try {
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$idProduto]);
            $array = $stmt->fetch(PDO::FETCH_ASSOC);
            return $this->arrayParaObjeto($array);
        } catch (PDOException $e) {
            error_log("Erro ao buscar produto com categoria: " . $e->getMessage());
            return null;
        }
    }

    public function listar($filtros = [])
{
    $query = "SELECT p.*, c.descricao as nome_categoria 
              FROM produtos p 
              LEFT JOIN categorias c ON p.idCategoria = c.idCategoria 
              WHERE 1=1";
    
    $params = [];

    // NOVA LÓGICA: Mostrar todos por padrão, filtrar apenas se especificado
    if (isset($filtros['vendido']) && ($filtros['vendido'] === '0' || $filtros['vendido'] === '1')) {
        $query .= " AND p.vendido = ?";
        $params[] = (int)$filtros['vendido'];
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

    // Filtro por nome (LIKE)
    if (!empty($filtros['nome'])) {
        $query .= " AND p.nome LIKE ?";
        $params[] = '%' . $filtros['nome'] . '%';
    }

    $query .= " ORDER BY p.idProduto DESC";

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

    // ==================== MÉTODOS PARA FILTROS ====================
    
    public function getOpcoesFiltro($campo, $incluirVendidos = false)
{
    $sql = "SELECT DISTINCT $campo FROM produtos WHERE $campo IS NOT NULL AND $campo != ''";
    
    $sql .= " ORDER BY $campo";

    try {
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    } catch (PDOException $e) {
        error_log("Erro ao buscar opções: " . $e->getMessage());
        return [];
    }
}
    // ==================== CATEGORIAS ====================
    
    public function getCategorias()
    {
        $sql = "SELECT idCategoria, descricao FROM categorias ORDER BY idCategoria";

        try {
            $stmt = $this->conn->prepare($sql);
            $stmt->execute();
            $categorias = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Fallback se a tabela não existir ou estiver vazia
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
            
            // Debug: mostrar exatamente o que tem no banco
            error_log("=== CATEGORIAS DO BANCO ===");
            foreach ($categorias as $cat) {
                error_log("ID: " . $cat['idCategoria'] . " | Nome: '" . $cat['descricao'] . "'");
                error_log("  Bytes hex: " . bin2hex($cat['descricao']));
                error_log("  Comprimento: " . strlen($cat['descricao']));
            }
            
            return $categorias;
        } catch (PDOException $e) {
            error_log("Erro ao buscar categorias: " . $e->getMessage());
            // Retorna o array padrão mesmo com erro
            return [
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
    }

    // ==================== MÉTODOS ESPECÍFICOS DE BUSCA ====================
    
    public function buscarPorCategoria($idCategoria, $limit = null)
    {
        $sql = "SELECT p.*, c.descricao as nome_categoria 
                FROM produtos p 
                LEFT JOIN categorias c ON p.idCategoria = c.idCategoria 
                WHERE p.idCategoria = ? AND p.vendido = 0";
        
        if ($limit) {
            $sql .= " LIMIT " . (int)$limit;
        }

        try {
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$idCategoria]);
            
            $produtos = [];
            while ($array = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $produtos[] = $this->arrayParaObjeto($array);
            }
            return $produtos;
        } catch (PDOException $e) {
            error_log("Erro ao buscar por categoria: " . $e->getMessage());
            return [];
        }
    }

    public function buscarPromocoes($limit = null)
    {
        $sql = "SELECT p.*, c.descricao as nome_categoria 
                FROM produtos p 
                LEFT JOIN categorias c ON p.idCategoria = c.idCategoria 
                WHERE p.promocao = 1 AND p.vendido = 0";
        
        if ($limit) {
            $sql .= " LIMIT " . (int)$limit;
        }

        try {
            $stmt = $this->conn->query($sql);
            
            $produtos = [];
            while ($array = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $produtos[] = $this->arrayParaObjeto($array);
            }
            return $produtos;
        } catch (PDOException $e) {
            error_log("Erro ao buscar promoções: " . $e->getMessage());
            return [];
        }
    }

    // ==================== MÉTODOS ESTATÍSTICOS ====================
    
    public function contarTotal()
    {
        $sql = "SELECT COUNT(*) as total FROM produtos";
        
        try {
            $stmt = $this->conn->query($sql);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result['total'] ?? 0;
        } catch (PDOException $e) {
            error_log("Erro ao contar total: " . $e->getMessage());
            return 0;
        }
    }

    public function contarVendidos()
    {
        $sql = "SELECT COUNT(*) as total FROM produtos WHERE vendido = 1";
        
        try {
            $stmt = $this->conn->query($sql);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result['total'] ?? 0;
        } catch (PDOException $e) {
            error_log("Erro ao contar vendidos: " . $e->getMessage());
            return 0;
        }
    }

    public function contarEmPromocao()
    {
        $sql = "SELECT COUNT(*) as total FROM produtos WHERE promocao = 1";
        
        try {
            $stmt = $this->conn->query($sql);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result['total'] ?? 0;
        } catch (PDOException $e) {
            error_log("Erro ao contar promoções: " . $e->getMessage());
            return 0;
        }
    }

    public function getValorTotalEstoque()
    {
        $sql = "SELECT SUM(preco * estoque) as valor_total 
                FROM produtos 
                WHERE vendido = 0";
        
        try {
            $stmt = $this->conn->query($sql);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result['valor_total'] ?? 0;
        } catch (PDOException $e) {
            error_log("Erro ao calcular valor total: " . $e->getMessage());
            return 0;
        }
    }

    // ==================== MÉTODOS PARA VALIDAÇÃO ====================
    
    public function validarDados($dados)
    {
        $erros = [];
        
        if (empty($dados['nome'])) {
            $erros[] = 'Nome do produto é obrigatório';
        }
        
        if (empty($dados['estado'])) {
            $erros[] = 'Estado do produto é obrigatório';
        }
        
        if (empty($dados['idCategoria']) && empty($dados['categoria'])) {
            $erros[] = 'Categoria é obrigatória';
        }
        
        if (empty($dados['preco']) || $dados['preco'] <= 0) {
            $erros[] = 'Preço deve ser maior que zero';
        }
        
        return $erros;
    }

    // ==================== MÉTODOS ADICIONAIS ====================
    
    public function atualizarEstoque($idProduto, $quantidade)
    {
        $sql = "UPDATE produtos SET estoque = ? WHERE idProduto = ?";
        
        try {
            $stmt = $this->conn->prepare($sql);
            return $stmt->execute([$quantidade, $idProduto]);
        } catch (PDOException $e) {
            error_log("Erro ao atualizar estoque: " . $e->getMessage());
            throw new Exception("Erro ao atualizar estoque: " . $e->getMessage());
        }
    }

    /**
     * Buscar produtos por estado
     */
    public function buscarPorEstado($estado, $limit = null)
    {
        $sql = "SELECT p.*, c.descricao as nome_categoria 
                FROM produtos p 
                LEFT JOIN categorias c ON p.idCategoria = c.idCategoria 
                WHERE p.estado = ? AND p.vendido = 0";
        
        if ($limit) {
            $sql .= " LIMIT " . (int)$limit;
        }

        try {
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$estado]);
            
            $produtos = [];
            while ($array = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $produtos[] = $this->arrayParaObjeto($array);
            }
            return $produtos;
        } catch (PDOException $e) {
            error_log("Erro ao buscar por estado: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Buscar produtos por marca
     */
    public function buscarPorMarca($marca, $limit = null)
    {
        $sql = "SELECT p.*, c.descricao as nome_categoria 
                FROM produtos p 
                LEFT JOIN categorias c ON p.idCategoria = c.idCategoria 
                WHERE p.marca = ? AND p.vendido = 0";
        
        if ($limit) {
            $sql .= " LIMIT " . (int)$limit;
        }

        try {
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$marca]);
            
            $produtos = [];
            while ($array = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $produtos[] = $this->arrayParaObjeto($array);
            }
            return $produtos;
        } catch (PDOException $e) {
            error_log("Erro ao buscar por marca: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Buscar produtos por nome (LIKE)
     */
    public function buscarPorNome($nome, $limit = null)
    {
        $sql = "SELECT p.*, c.descricao as nome_categoria 
                FROM produtos p 
                LEFT JOIN categorias c ON p.idCategoria = c.idCategoria 
                WHERE p.nome LIKE ? AND p.vendido = 0";
        
        if ($limit) {
            $sql .= " LIMIT " . (int)$limit;
        }

        try {
            $stmt = $this->conn->prepare($sql);
            $stmt->execute(['%' . $nome . '%']);
            
            $produtos = [];
            while ($array = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $produtos[] = $this->arrayParaObjeto($array);
            }
            return $produtos;
        } catch (PDOException $e) {
            error_log("Erro ao buscar por nome: " . $e->getMessage());
            return [];
        }
    }
}