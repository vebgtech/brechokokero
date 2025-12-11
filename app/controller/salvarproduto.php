<?php
// Inclui a nova classe de conexão PDO
require_once 'app/config/conexao.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Obtém a conexão PDO usando Singleton
    $conexao = Conexao::getConexao();
    
    // Sanitiza os dados do formulário
    $nome = filter_input(INPUT_POST, 'nome', FILTER_SANITIZE_STRING);
    $marca = filter_input(INPUT_POST, 'marca', FILTER_SANITIZE_STRING);
    $tamanho = filter_input(INPUT_POST, 'tamanho', FILTER_SANITIZE_STRING);
    $cor = filter_input(INPUT_POST, 'cor', FILTER_SANITIZE_STRING);
    $genero = filter_input(INPUT_POST, 'genero', FILTER_SANITIZE_STRING);
    $descricao = filter_input(INPUT_POST, 'descricao', FILTER_SANITIZE_STRING);
    $idCategoria = filter_input(INPUT_POST, 'idCategoria', FILTER_SANITIZE_NUMBER_INT);
    
    try {
        $sql = "INSERT INTO produtos (nome, marca, tamanho, cor, genero, descricao, idCategoria) 
                VALUES (:nome, :marca, :tamanho, :cor, :genero, :descricao, :idCategoria)";
        
        $stmt = $conexao->prepare($sql);
        
        // Bind dos parâmetros nomeados
        $stmt->bindParam(':nome', $nome);
        $stmt->bindParam(':marca', $marca);
        $stmt->bindParam(':tamanho', $tamanho);
        $stmt->bindParam(':cor', $cor);
        $stmt->bindParam(':genero', $genero);
        $stmt->bindParam(':descricao', $descricao);
        $stmt->bindParam(':idCategoria', $idCategoria, PDO::PARAM_INT);
        
        if ($stmt->execute()) {
            header("Location: admin");
            exit;
        } else {
            echo "Erro ao salvar produto.";
        }
        
    } catch (PDOException $e) {
        // Em ambiente de desenvolvimento, mostre o erro
        echo "Erro ao salvar produto: " . $e->getMessage();
        
        // Em produção, faça apenas log
        // error_log("Erro ao salvar produto: " . $e->getMessage());
        // echo "Erro ao processar o formulário. Tente novamente.";
    }
}
?>