<?php
session_start();
require_once '../config/conexao.php';
$conn = Conexao::getConexao(); 

if (isset($_SESSION['usuario']) && $_SESSION['usuario'] === true) {
    header("Location: minha_conta.php");
    exit();
}
 
$termo_busca = $_GET['busca'] ?? '';
$filtro_estado = $_GET['filtro_estado'] ?? '';
$filtro_tamanho = $_GET['filtro_tamanho'] ?? '';
$filtro_marca = $_GET['filtro_marca'] ?? '';
 
function buscarProdutos($conn, $idCategoria, $termo_busca = '', $filtro_estado = '', $filtro_tamanho = '', $filtro_marca = '')
{
    $sql = "SELECT p.* FROM produtos p WHERE (p.vendido=0 OR (p.vendido=1 AND p.data_venda > DATE_SUB(NOW(), INTERVAL 7 DAY))) ";
    $params = [];

    if ($idCategoria != 0) {  
        $sql .= "AND p.idCategoria = ? ";
        $params[] = $idCategoria;
    }
    if (!empty($termo_busca)) {
        $sql .= "AND p.nome LIKE ? ";
        $params[] = "%$termo_busca%";
    }
    if (!empty($filtro_estado)) {
        $sql .= "AND p.estado = ? ";
        $params[] = $filtro_estado;
    }
    if (!empty($filtro_tamanho)) {
        $sql .= "AND p.tamanho = ? ";
        $params[] = $filtro_tamanho;
    }
    if (!empty($filtro_marca)) {
        $sql .= "AND p.marca = ? ";
        $params[] = $filtro_marca;
    }
    $sql .= "ORDER BY p.idProduto DESC";

    $stmt = $conn->prepare($sql);
    if (!empty($params)) {
        $stmt->execute($params);
    } else {
        $stmt->execute();
    }
    return $stmt;
} 
$estados = $conn->query("SELECT DISTINCT estado FROM produtos WHERE estado IS NOT NULL AND vendido=0 ORDER BY estado")->fetchAll(PDO::FETCH_ASSOC);
$tamanho = $conn->query("SELECT DISTINCT tamanho FROM produtos WHERE tamanho IS NOT NULL AND vendido=0 ORDER BY tamanho")->fetchAll(PDO::FETCH_ASSOC);
$marcas = $conn->query("SELECT DISTINCT marca FROM produtos WHERE marca IS NOT NULL AND vendido=0 ORDER BY marca")->fetchAll(PDO::FETCH_ASSOC);
 
$categorias = [
    1 => "Bermudas e Shorts",
    2 => "Blazers",
    3 => "Blusas e Camisas",
    4 => "Calças",
    5 => "Casacos e Jaquetas",
    6 => "Conjuntos",
    7 => "Saias",
    8 => "Sapatos",
    9 => "Social",
    10 => "Vestidos"
];
 
$stmt_promocao = buscarProdutos($conn, 0, $termo_busca, $filtro_estado, $filtro_tamanho, $filtro_marca);
$produtos_promocao_todos = $stmt_promocao->fetchAll(PDO::FETCH_ASSOC);
$produtos_promocao_filtrados = [];
foreach ($produtos_promocao_todos as $p) {
    if ($p['promocao'] == 1) {
        $produtos_promocao_filtrados[] = $p;
    }
}
$produtos_promocao = $produtos_promocao_filtrados;  
 
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['acao']) && $_POST['acao'] == 'adicionar_carrinho') {
     
    if (!isset($_SESSION['usuario_id']) || empty($_SESSION['usuario_id'])) {
        $_SESSION['erro_carrinho'] = "Você precisa fazer login para adicionar itens ao carrinho.";
        header("Location: produto_detalhes.php?id=" . $idProduto);
        exit();
    }
     
    $idProdutoForm = isset($_POST['id_produto']) ? (int)$_POST['id_produto'] : 0; 
    if ($idProdutoForm > 0) { 
        $stmt = $conn->prepare("SELECT vendido FROM produtos WHERE idProduto = ?");
        $stmt->execute([$idProdutoForm]);
        $produto_check = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($produto_check && $produto_check['vendido'] == 0) {
            if (!isset($_SESSION['carrinho'])) $_SESSION['carrinho'] = [];
            if (!in_array($idProdutoForm, $_SESSION['carrinho'])) {
                $_SESSION['carrinho'][] = $idProdutoForm;
                $_SESSION['sucesso_carrinho'] = "Produto adicionado ao carrinho com sucesso!";
            } else {
                $_SESSION['sucesso_carrinho'] = "Este produto já está no carrinho.";
            }
            
            header("Location: produto_detalhes.php?id=" . $idProdutoForm);
            exit();
        } else {
            $erro = "Produto não disponível para compra.";
        }
    }
}

if (isset($_SESSION['sucesso_carrinho'])) {
    $sucesso = $_SESSION['sucesso_carrinho'];
    unset($_SESSION['sucesso_carrinho']);
}
if (isset($_SESSION['erro_carrinho'])) {
    $erro_carrinho = $_SESSION['erro_carrinho'];
    unset($_SESSION['erro_carrinho']);
}
if (isset($_GET['id'])) {
    $_SESSION['current_product_id'] = (int)$_GET['id'];
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="../../public/img/logo.png">
    <title>Login - Brechó Kokero</title> 
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
      <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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

<!-- Cadastro -->
<section id="contact" class="contact">
    <div class="container" data-aos="fade-up">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <form action="../controller/processar-login.php" method="POST" role="form" class="php-email-form">
                    <h1>Login</h1>
                    <?php if (isset($_SESSION['erro_login'])): ?>
                        <div class="alert alert-danger">
                            <?php 
                            echo htmlspecialchars($_SESSION['erro_login']); 
                            unset($_SESSION['erro_login']);
                            ?>
                        </div>
                    <?php endif; ?>
                    <div class="mb-3">
                        <label for="email" class="form-label">Email:</label>
                        <input type="email" class="form-control" id="email" name="email" 
                               placeholder="Exemplo: user@gmail.com" required>
                    </div>
                    <div class="mb-3">
                        <label for="senha" class="form-label">Senha:</label>
                        <div class="input-group password-field">
                            <input type="password" class="form-control" id="senha" name="senha" required>
                            <button type="button" id="toggleSenha" class="btn btn-outline-secondary"
                                    aria-label="Mostrar senha" 
                                    aria-pressed="false">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>
                    <input type="submit" name="login" id="butao" value="Login" class="btn btn-primary">
                    <div class="text-center p-t-115 mt-3">
                        <span class="txt1"> Não tem conta ainda? </span>
                        <a class="txt2" href="cadastre.php"> Cadastre-se </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</section>
<!-- End Cadastro -->

<!-- Offcanvas/Sidebar do carrinho de compras -->
<div class="offcanvas offcanvas-end" tabindex="-1" id="carrinhoOffcanvas">
    <div class="offcanvas-header">
        <h5 class="offcanvas-title"><i class="bi bi-bag"></i> Meu Carrinho</h5>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
    </div>
    
    <div class="offcanvas-body">
        <?php if (!isset($_SESSION['usuario_id']) || empty($_SESSION['usuario_id'])): ?>
            <div class="text-center py-4">
                <i class="bi bi-person-x" style="font-size: 3rem; color: #6c757d;"></i>
                <p class="mt-3">Você precisa estar logado para ver o carrinho.</p>
                <a href="log.php" class="btn btn-primary mt-2">Fazer Login</a>
            </div>
        <?php elseif (empty($_SESSION['carrinho'])): ?> 
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
            
            <div class="col-lg-6 col-md-3 footer-info">
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
            <div class="col-lg-6 col-md-3 footer-links">
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
        <div class="credentials">
            Desenvolvido com 💛 por <a href="https://vebgtech.talentosdoifsp.gru.br/">VebgTech</a>
        </div>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="../../public/js/script.js"></script>

</body>
</html>