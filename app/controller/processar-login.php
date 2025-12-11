<?php
session_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Conectar ao banco de dados
require_once "app/config/conexao.php";

// Verificar se o formulário foi enviado
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Obter dados
    $email = $_POST['email'] ?? '';
    $senha = $_POST['senha'] ?? '';

    // Validar
    if (empty($email) || empty($senha)) {
        header("Location: login?erro=campos_vazios");
        exit();
    }

    try {
        $conexao = Conexao::getConexao();

        // Buscar usuário
        $sql = "SELECT idUsuario, idNivelUsuario, nome, senha FROM usuarios WHERE email = ?";
        $stmt = $conexao->prepare($sql);
        $stmt->execute([$email]);

        if ($stmt->rowCount() == 1) {
            $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

            if (password_verify($senha, $usuario['senha'])) {
                // Login bem sucedido
                $_SESSION["usuario"] = true;
                $_SESSION["idNivelUsuario"] = $usuario['idNivelUsuario'];
                $_SESSION["usuario_id"] = $usuario['idUsuario'];
                $_SESSION["nome"] = $usuario['nome'];
                $_SESSION["email"] = $email;

                if ($usuario['idNivelUsuario']>1){
                    $_SESSION['isAdmin'] = true;  // BOOLEAN true
                    header("Location: admin");
                    
                }else

                header("Location: minha_conta?login_sucesso=true");
                exit();
            } else {
                header("Location: login?erro=senha_invalida");
                exit();
            }
        } else {
            header("Location: login?erro=email_nao_encontrado");
            exit();
        }

    } catch (Exception $e) {
        error_log("Erro login: " . $e->getMessage());
        header("Location: login?erro=erro_banco_dados");
        exit();
    }
} else {
    header("Location: login");
    exit();
}
?>