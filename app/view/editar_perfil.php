<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

if (!isset($_SESSION['usuario']) || $_SESSION['usuario'] !== true) {
    header("Location: login");
    exit();
}

require_once 'app/config/conexao.php';
require_once 'app/model/carrinho.php';
require_once 'app/view/carrinho_view.php';
require_once 'app/controller/carrinho_controller.php';
$conn = Conexao::getConexao();

$carrinhoController = new CarrinhoController($conn);
$carrinho = $carrinhoController->getCarrinho();
$carrinhoView = new CarrinhoView($carrinho);
echo $carrinhoView->renderizarOffcanvas();

$stmt = $conn->prepare("SELECT nome, email, telefone, senha FROM usuarios WHERE idUsuario = ?");
$stmt->execute([$_SESSION['usuario_id']]);
$usuario = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$usuario) {
    header("Location: minha_conta");
    exit();
}

$sucesso = '';
$erro = '';
$campos = [];
$sucesso_senha = '';
$erro_senha = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['atualizar_perfil'])) {
        $nome = trim($_POST['nome'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $telefone = trim($_POST['telefone'] ?? '');
        $erros = [];
        
        if (empty($nome)) {
            $erros[] = "O nome é obrigatório.";
        }
        
        if (empty($email)) {
            $erros[] = "O email é obrigatório.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $erros[] = "Email inválido.";
        }

        if ($email !== $usuario['email']) {
            $stmt_check = $conn->prepare("SELECT idUsuario FROM usuarios WHERE email = ?");
            $stmt_check->execute([$email]);
            if ($stmt_check->rowCount() > 0) {
                $erros[] = "Este email já está cadastrado.";
            }
        }

        if (empty($erros)) {
            try {
                $stmt_update = $conn->prepare("
                    UPDATE usuarios 
                    SET nome = ?, email = ?, telefone = ? 
                    WHERE idUsuario = ?
                ");
                
                $stmt_update->execute([$nome, $email, $telefone, $_SESSION['usuario_id']]);

                $_SESSION['nome'] = $nome;
                $_SESSION['email'] = $email;
                $usuario['nome'] = $nome;
                $usuario['email'] = $email;
                $usuario['telefone'] = $telefone;
                
                $sucesso = "Perfil atualizado com sucesso!";
                
            } catch (Exception $e) {
                $erro = "Erro ao atualizar perfil: " . $e->getMessage();
            }
        } else {
            $erro = implode("<br>", $erros);
        }

        $campos = [
            'nome' => $nome,
            'email' => $email,
            'telefone' => $telefone
        ];
    }

    if (isset($_POST['alterar_senha'])) {
        $senha_atual = $_POST['senha_atual'] ?? '';
        $nova_senha = $_POST['nova_senha'] ?? '';
        $confirmar_senha = $_POST['confirmar_senha'] ?? '';
        $erros_senha = [];
        
        if (empty($senha_atual)) {
            $erros_senha[] = "A senha atual é obrigatória.";
        }
        
        if ($nova_senha !== $confirmar_senha) {
            $erros_senha[] = "As senhas não conferem.";
        }

        if (empty($erros_senha) && !password_verify($senha_atual, $usuario['senha'])) {
            $erros_senha[] = "Senha atual incorreta.";
        }

        if (empty($erros_senha)) {
            try {
                $nova_senha_hash = password_hash($nova_senha, PASSWORD_DEFAULT);
                
                $stmt_senha = $conn->prepare("
                    UPDATE usuarios 
                    SET senha = ? 
                    WHERE idUsuario = ?
                ");
                
                $stmt_senha->execute([$nova_senha_hash, $_SESSION['usuario_id']]);
                
                $sucesso_senha = "Senha alterada com sucesso!";
                
                $_POST['senha_atual'] = '';
                $_POST['nova_senha'] = '';
                $_POST['confirmar_senha'] = '';
                
            } catch (Exception $e) {
                $erro_senha = "Erro ao alterar senha: " . $e->getMessage();
            }
        } else {
            $erro_senha = implode("<br>", $erros_senha);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="public/img/logo.png">
    <title>Editar Perfil - Brechó Koꓘero</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link rel="stylesheet" href="public/css/estilo.css">
</head>
<body>
<nav class="navbar navbar-expand-lg">
  <div class="container-fluid">
    <a class="navbar-brand" href="home">
      <img src="public/img/logo.png" alt="Logo" style="height: 80px; width:auto;">
    </a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarContent">
      <span class="navbar-toggler-icon" style="color:#fff;"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarContent">
      <ul class="navbar-nav mx-auto mb-2 mb-lg-0">
        <li class="nav-item"><a class="nav-link active" href="home">Início</a></li>
        <li class="nav-item"><a class="nav-link" href="produtos">Produtos</a></li>
        <li class="nav-item"><a class="nav-link" href="faq">FAQ</a></li>
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle" href="#" id="dropdownMenu" role="button" data-bs-toggle="dropdown">
            Categorias
          </a>
          <ul class="dropdown-menu" aria-labelledby="dropdownMenu">
            <li><a class="dropdown-item" href="produtos#novidade">Novidade</a></li>
            <li><a class="dropdown-item" href="produtos#todos">Todos</a></li>
            <li><a class="dropdown-item" href="produtos#promocoes">Promoções</a></li>
            <li>
              <hr class="dropdown-divider">
            </li>
            <li><a class="dropdown-item" href="produtos#bermudas-shorts">Bermudas e Shorts</a></li>
            <li><a class="dropdown-item" href="produtos#blazers">Blazers</a></li>
            <li><a class="dropdown-item" href="produtos#blusas-camisas">Blusas e Camisas</a></li>
            <li><a class="dropdown-item" href="produtos#calcas">Calças</a></li>
            <li><a class="dropdown-item" href="produtos#casacos-jaquetas">Casacos e Jaquetas</a></li>
            <li><a class="dropdown-item" href="produtos#conjuntos">Conjuntos</a></li>
            <li><a class="dropdown-item" href="produtos#saias">Saias</a></li>
            <li><a class="dropdown-item" href="produtos#sapatos">Sapatos</a></li>
            <li><a class="dropdown-item" href="produtos#social">Social</a></li>
            <li><a class="dropdown-item" href="produtos#vestidos">Vestidos</a></li>
          </ul>
        </li>
      </ul>

      <form class="d-flex me-3" role="search" method="GET" action="produtos">
        <input class="form-control me-2" type="search" name="busca" placeholder="Buscar produtos..."
          value="<?php echo htmlspecialchars($termo_busca); ?>" aria-label="Search">
        <button class="btn btn-dark" type="submit">Buscar</button>
      </form>

      <ul class="navbar-nav d-flex flex-row">
        <li class="nav-item me-3">
          <a class="nav-link" href="minha_conta"> <i class="bi bi-person-fill" style="font-size: 1.5rem;"></i> Minha Conta</a>
        </li>
                    <li class="nav-item">
                        <?php echo $carrinhoView->renderizarBotaoCarrinho(); ?>
                    </li>
      </ul>
    </div>
  </div>
</nav>

<div class="container py-5">
    <div class="edit-profile-container">
        
        <div class="mb-4">
            <a href="minha_conta" class="back-link">
                <i class="bi bi-arrow-left me-1"></i> Voltar para Minha Conta
            </a>
            <h1 class="mt-3 text-success">
                <i class="bi bi-pencil-square me-2"></i>Editar Perfil
            </h1>
            <p>Atualize suas informações pessoais e senha</p>
        </div>
        
        <?php if ($sucesso): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i>
            <?php echo $sucesso; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>
        
        <?php if ($erro): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>
            <?php echo $erro; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>
        
        <div class="row">
            <div class="col-md-6">
                <div class="card h-100">
                    <div class="card-body">
                        <h5 class="section-title">
                            <i class="bi bi-person-badge me-2"></i> Informações do Perfil
                        </h5>
                        
                        <form method="POST" action="">
                            <div class="mb-3">
                                <label for="nome" class="form-label">
                                    <i class="bi bi-person me-1"></i> Nome completo
                                </label>
                                <input type="text" 
                                       class="form-control" 
                                       id="nome" 
                                       name="nome" 
                                       value="<?php echo htmlspecialchars($campos['nome'] ?? $usuario['nome']); ?>"
                                       required
                                       placeholder="Seu nome completo">
                                <div class="form-text">Seu nome será exibido em seu perfil.</div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="email" class="form-label">
                                    <i class="bi bi-envelope me-1"></i> Email
                                </label>
                                <input type="email" 
                                       class="form-control" 
                                       id="email" 
                                       name="email" 
                                       value="<?php echo htmlspecialchars($campos['email'] ?? $usuario['email']); ?>"
                                       required
                                       placeholder="seu@email.com">
                                <div class="form-text">Seu email para login e comunicações.</div>
                            </div>
                            
                            <div class="mb-4">
                                <label for="telefone" class="form-label">
                                    <i class="bi bi-telephone me-1"></i> Telefone (opcional)
                                </label>
                                <input type="tel" 
                                       class="form-control" 
                                       id="telefone" 
                                       name="telefone" 
                                       value="<?php echo htmlspecialchars($campos['telefone'] ?? $usuario['telefone'] ?? ''); ?>"
                                       placeholder="(11) 99999-9999">
                                <div class="form-text">Para contato sobre pedidos e promoções.</div>
                            </div>
                            
                            <button type="submit" name="atualizar_perfil" class="btn btn-success w-100">
                                <i class="bi bi-check-circle me-1"></i> Salvar alterações do perfil
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-md-6 mt-4 mt-md-0">
                <div class="card h-100">
                    <div class="card-body">
                        <h5 class="section-title">
                            <i class="bi bi-shield-lock me-2"></i> Alterar Senha
                        </h5>
                
                        <?php if ($sucesso_senha): ?>
                        <div class="alert alert-success alert-dismissible fade show mb-3" role="alert">
                            <i class="bi bi-check-circle-fill me-2"></i>
                            <?php echo $sucesso_senha; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($erro_senha): ?>
                        <div class="alert alert-danger alert-dismissible fade show mb-3" role="alert">
                            <i class="bi bi-exclamation-triangle-fill me-2"></i>
                            <?php echo $erro_senha; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                        <?php endif; ?>
                        
                        <form method="POST" action="">
                            <div class="mb-3">
                                <label for="senha_atual" class="form-label">
                                    <i class="bi bi-key me-1"></i> Senha atual
                                </label>
                                <div class="password-container">
                                    <input type="password" 
                                           class="form-control" 
                                           id="senha_atual" 
                                           name="senha_atual" 
                                           required
                                           placeholder="Digite sua senha atual"
                                           value="<?php echo htmlspecialchars($_POST['senha_atual'] ?? ''); ?>">
                                    <i class="bi bi-eye password-toggle" data-target="senha_atual"></i>
                                </div>
                                <div class="form-text">Para segurança, confirme sua senha atual.</div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="nova_senha" class="form-label">
                                    <i class="bi bi-key-fill me-1"></i> Nova senha
                                </label>
                                <div class="password-container">
                                    <input type="password" 
                                           class="form-control" 
                                           id="nova_senha" 
                                           name="nova_senha" 
                                           required
                                           placeholder="Digite a nova senha"
                                           value="<?php echo htmlspecialchars($_POST['nova_senha'] ?? ''); ?>">
                                    <i class="bi bi-eye password-toggle" data-target="nova_senha"></i>
                                </div>
                            </div>
                            
                            <div class="mb-4">
                                <label for="confirmar_senha" class="form-label">
                                    <i class="bi bi-key-fill me-1"></i> Confirmar nova senha
                                </label>
                                <div class="password-container">
                                    <input type="password" 
                                           class="form-control" 
                                           id="confirmar_senha" 
                                           name="confirmar_senha" 
                                           required
                                           placeholder="Confirme a nova senha"
                                           value="<?php echo htmlspecialchars($_POST['confirmar_senha'] ?? ''); ?>">
                                    <i class="bi bi-eye password-toggle" data-target="confirmar_senha"></i>
                                </div>
                            </div>
                            
                            <button type="submit" name="alterar_senha" class="btn btn-warning w-100">
                                <i class="bi bi-lock me-1"></i> Alterar senha
                            </button>
                        </form>
                        
                        <div class="mt-4">
                            <h6><i class="bi bi-lightbulb me-2 text-warning"></i> Dicas para uma senha segura:</h6>
                            <ul class="list-unstyled text-muted small">
                                <li class="mb-1">
                                    <i class="bi bi-check-circle me-1"></i>
                                    Use no mínimo 6 caracteres
                                </li>
                                <li class="mb-1">
                                    <i class="bi bi-check-circle me-1"></i>
                                    Combine letras, números e símbolos
                                </li>
                                <li class="mb-1">
                                    <i class="bi bi-check-circle me-1"></i>
                                    Não use informações pessoais
                                </li>
                                <li>
                                    <i class="bi bi-check-circle me-1"></i>
                                    Altere sua senha periodicamente
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card mt-4">
            <div class="card-body">
                <h6><i class="bi bi-info-circle me-2 text-info"></i> Informações importantes</h6>
                <ul class="list-unstyled text-muted small">
                    <li class="mb-2">
                        <i class="bi bi-check-circle-fill text-success me-1"></i>
                        Seu email será usado para login no site
                    </li>
                    <li class="mb-2">
                        <i class="bi bi-check-circle-fill text-success me-1"></i>
                        Mantenha seus dados atualizados para melhor atendimento
                    </li>
                    <li class="mb-2">
                        <i class="bi bi-check-circle-fill text-success me-1"></i>
                        Após alterar sua senha, você precisará usá-la no próximo login
                    </li>
                    <li>
                        <i class="bi bi-check-circle-fill text-success me-1"></i>
                        Para sua segurança, faça logout ao terminar de usar o site
                    </li>
                </ul>
            </div>
        </div>
        
    </div>
</div>

<footer class="footer">
    <div class="container">
        <div class="row gy-4">
            <div class="col-lg-6 col-md-3 footer-info">
                <a href="home" class="logo align-items-center">
                    <img src="public/img/logo.png" alt="Logo">
                    <span>Brechó Koꓘero</span>
                </a>
                <p>Sua loja online de roupas, estilo e qualidade. Verde, amarelo e preto para realçar sua identidade.</p>
                <div class="social-links d-flex mt-3">
                    <a href="https://wa.me/5511992424158"><i class="bi bi-whatsapp"></i></a>
                    <a href="https://www.instagram.com/brecho.kokero?igsh=aTV4M3YyNmViZXB1"><i class="bi bi-instagram"></i></a>
                </div>
            </div>
            <div class="col-lg-6 col-md-3 footer-links">
                <h4>Links</h4>
                <ul>
                    <li><a href="home">Início</a></li>
                    <li><a href="produtos">Produtos</a></li>
                    <li><a href="faq">FAQ</a></li>
                    <li><a href="minha_conta">Minha Conta</a></li>
                </ul>
            </div>
        </div>
    </div>
    <div class="container mt-4">
        <div class="copyright">
            &copy; 2025 <strong><span>Brechó Koꓘero</span></strong>. Todos os direitos reservados.
        </div>
        <div class="credits">
            Desenvolvido com 💛 por <a href="https://vebgtech.talentosdoifsp.gru.br/">VebgTech</a>
        </div>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="public/js/script.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const telefoneInput = document.getElementById('telefone');
    
    if (telefoneInput) {
        telefoneInput.addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            
            if (value.length > 11) {
                value = value.substring(0, 11);
            }
            
            let formattedValue = '';
            if (value.length > 0) {
                formattedValue = '(' + value.substring(0, 2);
            }
            if (value.length > 2) {
                formattedValue += ') ' + value.substring(2, 7);
            }
            if (value.length > 7) {
                formattedValue += '-' + value.substring(7, 11);
            }
            
            e.target.value = formattedValue;
        });
    }
    document.querySelectorAll('.password-toggle').forEach(toggle => {
        toggle.addEventListener('click', function() {
            const targetId = this.getAttribute('data-target');
            const input = document.getElementById(targetId);
            const icon = this;
            
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('bi-eye');
                icon.classList.add('bi-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('bi-eye-slash');
                icon.classList.add('bi-eye');
            }
        });
    });
    const novaSenhaInput = document.getElementById('nova_senha');
    const confirmarSenhaInput = document.getElementById('confirmar_senha');
    
    function validarSenhas() {
        if (novaSenhaInput.value && confirmarSenhaInput.value) {
            if (novaSenhaInput.value !== confirmarSenhaInput.value) {
                confirmarSenhaInput.classList.add('is-invalid');
                confirmarSenhaInput.classList.remove('is-valid');
            } else {
                confirmarSenhaInput.classList.remove('is-invalid');
                confirmarSenhaInput.classList.add('is-valid');
            }
        }
    }
    
    if (novaSenhaInput && confirmarSenhaInput) {
        novaSenhaInput.addEventListener('input', validarSenhas);
        confirmarSenhaInput.addEventListener('input', validarSenhas);
    }
    document.getElementById('nome')?.focus();
});
</script>

</body>
</html>