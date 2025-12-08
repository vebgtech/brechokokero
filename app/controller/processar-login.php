<?php
session_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Conectar ao banco de dados
require_once "../config/conexao.php";

// Verificar se o formulário foi enviado
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Obter dados
    $email = $_POST['email'] ?? '';
    $senha = $_POST['senha'] ?? '';

    // Validar
    if (empty($email) || empty($senha)) {
        header("Location: ../view/log.php?erro=campos_vazios");
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
                    header("Location: ../view/admin/admin.php");
                }else

                header("Location: ../view/minha_conta.php?login_sucesso=true");
                exit();
            } else {
                header("Location: ../view/log.php?erro=senha_invalida");
                exit();
            }
        } else {
            header("Location: ../view/log.php?erro=email_nao_encontrado");
            exit();
        }

    } catch (Exception $e) {
        error_log("Erro login: " . $e->getMessage());
        header("Location: ../view/log.php?erro=erro_banco_dados");
        exit();
    }
} else {
    header("Location: ../view/log.php");
    exit();
}
?>