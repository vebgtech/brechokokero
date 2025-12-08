<?php
session_start();
require_once '../config/conexao.php';
$conn = Conexao::getConexao();

if (!isset($_SESSION['usuario']) || $_SESSION['usuario'] !== true) {
    header("Location: log.php");
    exit();
}

$stmt = $conn->prepare("SELECT nome, email, telefone FROM usuarios WHERE idUsuario = ?");
$stmt->execute([$_SESSION['usuario_id']]);
$usuario = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$usuario) {
    session_destroy();
    header("Location: log.php");
    exit();
}

if (isset($_SESSION['sucesso_carrinho'])) {
    $sucesso = $_SESSION['sucesso_carrinho'];
    unset($_SESSION['sucesso_carrinho']);
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="../../public/img/logo.png">
    <title>Minha Conta - Brechó Koꓘero</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../../public/css/estilo.css">
</head>
<body>

<!-- Navbar -->
<nav class="navbar navbar-expand-lg">
    <div class="container-fluid">
        <a class="navbar-brand" href="../../index.php">
            <img src="../../public/img/logo.png" alt="Logo" style="height: 80px; width:auto;">
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarContent">
            <span class="navbar-toggler-icon" style="color:#fff;"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarContent">
            <ul class="navbar-nav mx-auto mb-2 mb-lg-0">
                <li class="nav-item"><a class="nav-link" href="../../index.php">Início</a></li>
                <li class="nav-item"><a class="nav-link" href="produtos.php">Produtos</a></li>
                <li class="nav-item"><a class="nav-link" href="faq.php">FAQ</a></li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="dropdownMenu" role="button" data-bs-toggle="dropdown">
                        Categorias
                    </a>
                    <ul class="dropdown-menu" aria-labelledby="dropdownMenu">
                        <li><a class="dropdown-item" href="produtos.php#novidade">Novidade</a></li>
                        <li><a class="dropdown-item" href="produtos.php#todos">Todos</a></li>
                        <li><a class="dropdown-item" href="produtos.php#promocoes">Promoções</a></li>
                        <li>
                            <hr class="dropdown-divider">
                        </li>
                        <li><a class="dropdown-item" href="produtos.php#bermudas-shorts">Bermudas e Shorts</a></li>
                        <li><a class="dropdown-item" href="produtos.php#blazers">Blazers</a></li>
                        <li><a class="dropdown-item" href="produtos.php#blusas-camisas">Blusas e Camisas</a></li>
                        <li><a class="dropdown-item" href="produtos.php#calcas">Calças</a></li>
                        <li><a class="dropdown-item" href="produtos.php#casacos-jaquetas">Casacos e Jaquetas</a></li>
                        <li><a class="dropdown-item" href="produtos.php#conjuntos">Conjuntos</a></li>
                        <li><a class="dropdown-item" href="produtos.php#saias">Saias</a></li>
                        <li><a class="dropdown-item" href="produtos.php#sapatos">Sapatos</a></li>
                        <li><a class="dropdown-item" href="produtos.php#social">Social</a></li>
                        <li><a class="dropdown-item" href="produtos.php#vestidos">Vestidos</a></li>
                    </ul>
                </li>
            </ul>

            <form class="d-flex me-3" role="search" method="GET" action="produtos.php">
                <input class="form-control me-2" type="search" name="busca" placeholder="Buscar produtos..."
                    value="<?php echo htmlspecialchars($termo_busca); ?>" aria-label="Search">
                <button class="btn btn-dark" type="submit">Buscar</button>
            </form>

            <ul class="navbar-nav d-flex flex-row">
                <li class="nav-item me-3">
                    <a class="nav-link" href="minha_conta.php"> <i class="bi bi-person-fill" style="font-size: 1.5rem;"></i> Minha Conta</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#" data-bs-toggle="offcanvas" data-bs-target="#carrinhoOffcanvas" aria-controls="carrinhoOffcanvas">
                    <i class="bi bi-cart-fill" style="font-size: 1.5rem;"></i> Carrinho
                        <span class="badge rounded-pill bg-success">
                            <?php echo isset($_SESSION['carrinho']) ? count($_SESSION['carrinho']) : 0; ?>
                        </span>
                    </a>
                </li>
            </ul>
        </div>
    </div>
</nav>
<!-- NAVBAR END -->

<main class="container">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-body">
                    <h3>Minha Conta</h3>
                    <hr>
                    
                    <?php if (isset($_GET['cadastro_sucesso']) && $_GET['cadastro_sucesso'] === 'true'): ?>
                    <div class="alert alert-success">
                        <i class="bi bi-check-circle-fill me-2"></i>
                        Cadastro realizado com sucesso! Bem-vindo ao Brechó Kokero!
                    </div>
                    <?php endif; ?>
                    
                    <?php if (isset($_GET['login_sucesso']) && $_GET['login_sucesso'] === 'true'): ?>
                    <div class="alert alert-success">
                        <i class="bi bi-check-circle-fill me-2"></i>
                        Login realizado com sucesso!
                    </div>
                    <?php endif; ?>
                    
                    <?php if (isset($sucesso)): ?>
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle-fill me-2"></i>
                        <?php echo htmlspecialchars($sucesso); ?>
                    </div>
                    <?php endif; ?>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <h5>Informações Pessoais</h5>
                            <?php if ($usuario): ?>
                            <p><strong>Nome:</strong> <?php echo htmlspecialchars($usuario['nome']); ?></p>
                            <p><strong>Email:</strong> <?php echo htmlspecialchars($usuario['email']); ?></p>
                            <?php if ($usuario['telefone']): ?>
                            <p><strong>Telefone:</strong> <?php echo htmlspecialchars($usuario['telefone']); ?></p>
                            <?php endif; ?>
                            <a href="editar_perfil.php" class="btn btn-primary">Editar perfil</a>
                            <?php else: ?>
                            <div class="alert alert-warning">
                                Não foi possível carregar suas informações.
                            </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="d-grid gap-2">
                                
                                <a href="produtos.php" class="btn btn-roxo-solid">
                                    <i class="bi bi-bag me-2"></i> Continuar comprando
                                </a>
                                
                                <a href="checkout.php" class="btn btn-azul-bebe-solid">
                                    <i class="bi bi-cart-check me-2"></i> Finalizar compra
                                    <?php if (isset($_SESSION['carrinho']) && count($_SESSION['carrinho']) > 0): ?>
                                    <span class="badge bg-danger ms-2"><?php echo count($_SESSION['carrinho']); ?></span>
                                    <?php endif; ?>
                                </a>
                                
                                <a href="../controller/logout.php" class="btn btn-danger">
                                    <i class="bi bi-box-arrow-right me-2"></i> Sair
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>
<div class="offcanvas offcanvas-end" tabindex="-1" id="carrinhoOffcanvas">
    <div class="offcanvas-header">
        <h5 class="offcanvas-title"><i class="bi bi-bag"></i> Meu Carrinho</h5>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
    </div>
    
    <div class="offcanvas-body">
        <?php if (empty($_SESSION['carrinho'])): ?>
            <p class="text-center text-muted">Seu carrinho está vazio 😢</p>
        <?php else: ?>
            <ul class="list-group mb-3">
                <?php
                $total = 0;
                
                foreach ($_SESSION['carrinho'] as $id) {
                    $stmt = $conn->prepare("SELECT nome, preco, imagem FROM produtos WHERE idProduto=?");
                    $stmt->execute([$id]);
                    $p = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if ($p):
                        $total += $p['preco'];
                ?>
                <li class="list-group-item d-flex justify-content-between align-items-center">
                    <div class="d-flex align-items-center">
                        <img src="../../public/img/<?php echo htmlspecialchars($p['imagem']); ?>" 
                             style="width:50px;height:50px;object-fit:cover;" 
                             class="rounded me-2"
                             onerror="this.src='../../public/img/default.jpg';">
                        <div>
                            <strong><?php echo htmlspecialchars($p['nome']); ?></strong><br>
                            <small>R$ <?php echo number_format($p['preco'], 2, ',', '.'); ?></small>
                        </div>
                    </div>
                    
                    <form method="POST" action="../controller/remover_carrinho.php">
                        <input type="hidden" name="id" value="<?php echo $id; ?>">
                        <button class="btn btn-outline-danger btn-sm"><i class="bi bi-trash"></i></button>
                    </form>
                </li>
                <?php 
                    endif; 
                }
                ?>
            </ul>

            <div class="d-flex justify-content-between mb-3">
                <strong>Total:</strong>
                <span class="text-success fw-bold">R$ <?php echo number_format($total, 2, ',', '.'); ?></span>
            </div>

            <a href="checkout.php" class="btn btn-success w-100">Finalizar Compra</a>
        <?php endif; ?>
    </div>
</div>

<footer class="footer">
    <div class="container">
        <div class="row gy-4">
            
            <div class="col-lg-6 col-md-6 footer-info">
                <a href="../../index.php" class="logo align-items-center">
                    <img src="../../public/img/logo.png" alt="Logo">
                    <span>Brechó Koꓘero</span>
                </a>
                <p>Sua loja online de roupas, estilo e qualidade. Verde, amarelo e preto para realçar sua identidade.</p>
                <div class="social-links d-flex mt-3">
                    <a href="https://wa.me/5511992424158"><i class="bi bi-whatsapp"></i></a>
                    <a href="https://www.instagram.com/brecho.kokero?igsh=aTV4M3YyNmViZXB1"><i class="bi bi-instagram"></i></a>
                </div>
            </div>

            <div class="col-lg-6 col-md-6 footer-links">
                <h4>Links</h4>
                <ul>
                    <li><a href="../../index.php">Início</a></li>
                    <li><a href="produtos.php">Produtos</a></li>
                    <li><a href="faq.php">FAQ</a></li>
                    <li><a href="minha_conta.php">Minha Conta</a></li>
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
<script src="../../public/js/script.js"></script>
</body>
</html>