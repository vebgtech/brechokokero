<?php
require_once 'app/config/conexao.php';

try {
    $conexao = Conexao::getConexao();
    $stmt = $conexao->prepare("SELECT idProduto, imagem FROM produtos WHERE vendido=1 AND data_venda < DATE_SUB(NOW(), INTERVAL 7 DAY)");
    $stmt->execute();
    
    $produtosExcluidos = 0;
    $imagensExcluidas = 0;
    
    while ($row = $stmt->fetch()) {
        $imagem_nome = $row['imagem'];
        
        if ($imagem_nome != 'default.jpg') {
            $imagem_path = "public/img/" . $imagem_nome;
            if (file_exists($imagem_path)) {
                if (unlink($imagem_path)) {
                    $imagensExcluidas++;
                }
            }
        }
        $deleteStmt = $conexao->prepare("DELETE FROM produtos WHERE idProduto = ?");
        $deleteStmt->execute([$row['idProduto']]);
        $produtosExcluidos++;
    }
    error_log("Limpeza automática: $produtosExcluidos produtos excluídos, $imagensExcluidas imagens removidas.");
    
} catch (PDOException $e) {
    error_log("Erro na limpeza automática: " . $e->getMessage());
}
?>