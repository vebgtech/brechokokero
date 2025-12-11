<?php
session_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Conectar ao banco de dados
require_once "app/config/conexao.php";

// Verificar se o formulário foi enviado
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Obter dados
    $nome = $_POST['nome'] ?? '';
    $email = $_POST['email'] ?? '';
    $senha = $_POST['senha'] ?? '';
    
    // Validar
    if (empty($nome) || empty($email) || empty($senha)) {
        header("Location: cadastro?erro=campos_vazios");
        exit();
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        header("Location: cadastro?erro=email_invalido");
        exit();
    }

    try {
        $conexao = Conexao::getConexao();
        
        // Verificar email
        $stmt = $conexao->prepare("SELECT idUsuario FROM usuarios WHERE email = ?");
        $stmt->execute([$email]);
        
        if ($stmt->rowCount() > 0) {
            header("Location: cadastro?erro=email_ja_cadastrado");
            exit();
        }
        
        // Criar hash da senha
        $senhaHash = password_hash($senha, PASSWORD_DEFAULT);
        $idNivelUsuario = 1;
        
        // Inserir usuário
        $sql = "INSERT INTO usuarios (nome, email, senha, idNivelUsuario) VALUES (?, ?, ?, ?)";
        $stmt = $conexao->prepare($sql);
        
        if ($stmt->execute([$nome, $email, $senhaHash, $idNivelUsuario])) {
            // Login automático
            $idUsuario = $conexao->lastInsertId();
            
            $_SESSION["usuario"] = true;
            $_SESSION["usuario_id"] = $idUsuario;
            $_SESSION["nome"] = $nome;
            $_SESSION["email"] = $email;
            
            header("Location: minha_conta?cadastro_sucesso=true");
            exit();
        } else {
            header("Location: cadastro?erro=erro_banco_dados");
            exit();
        }
        
    } catch (Exception $e) {
        error_log("Erro cadastro: " . $e->getMessage());
        header("Location: cadastro?erro=erro_banco_dados");
        exit();
    }
} else {
    header("Location: cadastro");
    exit();
}
?>