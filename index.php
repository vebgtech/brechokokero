<?php
session_start();
require_once('app/config/conexao.php');
$conn = Conexao::getConexao();

$isAdmin = ($_SESSION['idNivelUsuario'] ?? 0) >= 2;

// Busca e filtros
$termo_busca = $_GET['busca'] ?? '';
$filtro_estado = $_GET['filtro_estado'] ?? '';
$filtro_tamanho = $_GET['filtro_tamanho'] ?? '';
$filtro_marca = $_GET['filtro_marca'] ?? '';

// Função para buscar produtos por categoria (com filtros, incluindo vendidos recentes)
function buscarProdutos($conn, $idCategoria, $termo_busca = '', $filtro_estado = '', $filtro_tamanho = '', $filtro_marca = '')
{
    $sql = "SELECT p.* FROM produtos p WHERE (p.vendido=0 OR (p.vendido=1 AND p.data_venda > DATE_SUB(NOW(), INTERVAL 7 DAY))) ";
    $params = [];

    if ($idCategoria != 0) { // 0 para "Todos"
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

// Opções para filtros (dinâmicas, excluindo vendidos)
$estados = $conn->query("SELECT DISTINCT estado FROM produtos WHERE estado IS NOT NULL AND vendido=0 ORDER BY estado")->fetchAll(PDO::FETCH_ASSOC);
$tamanho = $conn->query("SELECT DISTINCT tamanho FROM produtos WHERE tamanho IS NOT NULL AND vendido=0 ORDER BY tamanho")->fetchAll(PDO::FETCH_ASSOC);
$marcas = $conn->query("SELECT DISTINCT marca FROM produtos WHERE marca IS NOT NULL AND vendido=0 ORDER BY marca")->fetchAll(PDO::FETCH_ASSOC);

// Array com as novas categorias
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

// Para promoções: Buscar com filtros
$stmt_promocao = buscarProdutos($conn, 0, $termo_busca, $filtro_estado, $filtro_tamanho, $filtro_marca);
$produtos_promocao_todos = $stmt_promocao->fetchAll(PDO::FETCH_ASSOC);
$produtos_promocao_filtrados = [];
foreach ($produtos_promocao_todos as $p) {
    if ($p['promocao'] == 1) {
        $produtos_promocao_filtrados[] = $p;
    }
}
$produtos_promocao = $produtos_promocao_filtrados; // Array filtrado

// --- Adicionar ao carrinho ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['acao']) && $_POST['acao'] == 'adicionar_carrinho') {
    
    // VERIFICA SE O USUÁRIO ESTÁ LOGADO
    if (!isset($_SESSION['usuario_id']) || empty($_SESSION['usuario_id'])) {
        $_SESSION['erro_carrinho'] = "Você precisa fazer login para adicionar itens ao carrinho.";
        header("Location: index.php?id=" . $idProduto);
        exit();
    }
    
    // Obtém o ID do produto do formulário
    $idProdutoForm = isset($_POST['id_produto']) ? (int)$_POST['id_produto'] : 0;
    
    // Verifica se o produto existe e não foi vendido
    if ($idProdutoForm > 0) {
        // Buscar informações do produto
        $stmt = $conn->prepare("SELECT vendido FROM produtos WHERE idProduto = ?");
        $stmt->execute([$idProdutoForm]);
        $produto_check = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($produto_check && $produto_check['vendido'] == 0) {
            // Inicializa o array do carrinho na sessão se ainda não existir.
            if (!isset($_SESSION['carrinho'])) $_SESSION['carrinho'] = [];

            // Verifica se o produto (identificado pelo ID) já está no carrinho.
            if (!in_array($idProdutoForm, $_SESSION['carrinho'])) {
                // Adiciona o ID do produto ao array do carrinho.
                $_SESSION['carrinho'][] = $idProdutoForm;
                // Define uma mensagem de sucesso.
                $_SESSION['sucesso_carrinho'] = "Produto adicionado ao carrinho com sucesso!";
            } else {
                // Define uma mensagem se o produto já estiver no carrinho.
                $_SESSION['sucesso_carrinho'] = "Este produto já está no carrinho.";
            }
            
            // Redireciona para a mesma página atual mantendo o ID
            header("Location: produto_detalhes.php?id=" . $idProdutoForm);
            exit();
        } else {
            $erro = "Produto não disponível para compra.";
        }
    }
}

// CORREÇÃO: Recupera mensagem da sessão e remove
if (isset($_SESSION['sucesso_carrinho'])) {
    $sucesso = $_SESSION['sucesso_carrinho'];
    unset($_SESSION['sucesso_carrinho']);
}

// ADICIONE ESTA PARTE PARA MENSAGENS DE ERRO
if (isset($_SESSION['erro_carrinho'])) {
    $erro_carrinho = $_SESSION['erro_carrinho'];
    unset($_SESSION['erro_carrinho']);
}

// CORREÇÃO: Limpa o ID da sessão se estiver acessando uma URL nova com ID
if (isset($_GET['id'])) {
    $_SESSION['current_product_id'] = (int)$_GET['id'];
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="icon" type="image/png" href="public/img/logo.png">
  <title>Brechó Koꓘero</title> 
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">

  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
  <link rel="stylesheet" href="public/css/estilo.css">
</head>
<body>

<!-- ===========================
     NAVBAR PRINCIPAL
=========================== -->
<nav class="navbar navbar-expand-lg">
    <div class="container-fluid">
        <a class="navbar-brand" href="index.php">
            <img src="public/img/logo.png" alt="Logo" style="height: 80px; width:auto;">
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarContent">
            <span class="navbar-toggler-icon" style="color:#fff;"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarContent">
            <ul class="navbar-nav mx-auto mb-2 mb-lg-0">
                <li class="nav-item"><a class="nav-link active" href="index.php">Início</a></li>
                <li class="nav-item"><a class="nav-link" href="app/view/produtos.php">Produtos</a></li>
                <li class="nav-item"><a class="nav-link" href="app/view/faq.php">FAQ</a></li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="dropdownMenu" role="button" data-bs-toggle="dropdown">
                        Categorias
                    </a>
                    <ul class="dropdown-menu" aria-labelledby="dropdownMenu">
                        <li><a class="dropdown-item" href="app/view/produtos.php#novidade">Novidade</a></li>
                        <li><a class="dropdown-item" href="app/view/produtos.php#todos">Todos</a></li>
                        <li><a class="dropdown-item" href="app/view/produtos.php#promocoes">Promoções</a></li>
                        <li>
                            <hr class="dropdown-divider">
                        </li>
                        <li><a class="dropdown-item" href="app/view/produtos.php#bermudas-shorts">Bermudas e Shorts</a></li>
                        <li><a class="dropdown-item" href="app/view/produtos.php#blazers">Blazers</a></li>
                        <li><a class="dropdown-item" href="app/view/produtos.php#blusas-camisas">Blusas e Camisas</a></li>
                        <li><a class="dropdown-item" href="app/view/produtos.php#calcas">Calças</a></li>
                        <li><a class="dropdown-item" href="app/view/produtos.php#casacos-jaquetas">Casacos e Jaquetas</a></li>
                        <li><a class="dropdown-item" href="app/view/produtos.php#conjuntos">Conjuntos</a></li>
                        <li><a class="dropdown-item" href="app/view/produtos.php#saias">Saias</a></li>
                        <li><a class="dropdown-item" href="app/view/produtos.php#sapatos">Sapatos</a></li>
                        <li><a class="dropdown-item" href="app/view/produtos.php#social">Social</a></li>
                        <li><a class="dropdown-item" href="app/view/produtos.php#vestidos">Vestidos</a></li>
                    </ul>
                </li>
                
                <!-- BOTÃO ADMIN - APENAS PARA USUÁRIOS COM NÍVEL 2 -->
                <?php if ($isAdmin): ?>
                <li class="nav-item">
                    <a class="nav-link" href="app/view/admin/admin.php">
                        <i class="bi bi-shield-lock me-1"></i> Admin
                    </a>
                </li>
                <?php endif; ?>
            </ul>

            <form class="d-flex me-3" role="search" method="GET" action="app/view/produtos.php">
                <input class="form-control me-2" type="search" name="busca" placeholder="Buscar produtos..."
                    value="<?php echo htmlspecialchars($termo_busca); ?>" aria-label="Search">
                <button class="btn btn-dark" type="submit">Buscar</button>
            </form>

            <ul class="navbar-nav d-flex flex-row">
                <li class="nav-item me-3">
                    <a class="nav-link" href="app/view/minha_conta.php"> <i class="bi bi-person-fill" style="font-size: 1.5rem;"></i> Minha Conta</a>
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

<!-- ===========================
     FIM DA NAVBAR
=========================== -->

<!-- Carrossel -->
<div id="carouselExample" class="carousel slide" data-bs-ride="carousel">
    <div class="carousel-inner">
        <div class="carousel-item active">
            <img src="public/img/carosel1.png" class="d-block w-100" alt="Slide 1">
        </div>
        <div class="carousel-item">
            <img src="public/img/carosel2.png" class="d-block w-100" alt="Slide 2">
        </div>
        <div class="carousel-item">
            <img src="public/img/carosel3.png" class="d-block w-100" alt="Slide 3">
        </div>
    </div>
    <button class="carousel-control-prev" type="button" data-bs-target="#carouselExample" data-bs-slide="prev">
        <span class="carousel-control-prev-icon" aria-hidden="true"></span>
        <span class="visually-hidden">Previous</span>
    </button>
    <button class="carousel-control-next" type="button" data-bs-target="#carouselExample" data-bs-slide="next">
        <span class="carousel-control-next-icon" aria-hidden="true"></span>
        <span class="visually-hidden">Next</span>
    </button>
</div>

<!-- Produtos -->
<div class="container text-center produtos">
    <!-- Linha antes da primeira fileira -->
    <hr class="linha-produtos">

    <?php
    // Buscar 6 produtos aleatórios não vendidos
    try {
        $stmt = $conn->prepare("SELECT * FROM produtos WHERE vendido = 0 ORDER BY RAND() LIMIT 6");
        $stmt->execute();
        $produtos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $produtos = [];
        error_log("Erro ao buscar produtos: " . $e->getMessage());
    }
    ?>

    <!-- Primeira fileira -->
    <div class="row g-4 index">
        <?php for ($i = 0; $i < 3 && $i < count($produtos); $i++): ?>
            <div class="col-md-4">
                <a href="app/view/produto_detalhes.php?id=<?= $produtos[$i]['idProduto']; ?>">
                    <img src="public/img/<?= htmlspecialchars($produtos[$i]['imagem'] ?? 'default.jpg'); ?>" 
                        alt="<?= htmlspecialchars($produtos[$i]['nome']); ?>" 
                        class="img-fluid card index-img"
                        onerror="this.src='public/img/default.jpg'">
                    <p><?= htmlspecialchars($produtos[$i]['nome']); ?></p>
                </a>
            </div>
        <?php endfor; ?>
    </div>

    <!-- Linha entre as fileiras -->
    <hr class="linha-produtos">
    
    <!-- Segunda fileira -->
    <div class="row g-4 mt-4 index">
        <?php for ($i = 3; $i < 6 && $i < count($produtos); $i++): ?>
            <div class="col-md-4">
                <a href="app/view/produto_detalhes.php?id=<?= $produtos[$i]['idProduto']; ?>">
                    <img src="public/img/<?= htmlspecialchars($produtos[$i]['imagem'] ?? 'default.jpg'); ?>" 
                        alt="<?= htmlspecialchars($produtos[$i]['nome']); ?>" 
                        class="img-fluid card index-img"
                        onerror="this.src='public/img/default.jpg'">
                    <p><?= htmlspecialchars($produtos[$i]['nome']); ?></p>
                </a>
            </div>
        <?php endfor; ?>
    </div>

    <!-- Linha depois da segunda fileira -->
    <hr class="linha-produtos">
</div>

<!-- Offcanvas/Sidebar do carrinho de compras -->
<div class="offcanvas offcanvas-end" tabindex="-1" id="carrinhoOffcanvas">
    <div class="offcanvas-header">
        <h5 class="offcanvas-title"><i class="bi bi-bag"></i> Meu Carrinho</h5>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
    </div>
    
    <div class="offcanvas-body">
        <?php if (!isset($_SESSION['usuario_id']) || empty($_SESSION['usuario_id'])): ?>
            <!-- Usuário não logado -->
            <div class="text-center py-4">
                <i class="bi bi-person-x" style="font-size: 3rem; color: #6c757d;"></i>
                <p class="mt-3">Você precisa estar logado para ver o carrinho.</p>
                <a href="app/view/log.php" class="btn btn-primary mt-2">Fazer Login</a>
            </div>
        <?php elseif (empty($_SESSION['carrinho'])): ?>
            <!-- Carrinho vazio -->
            <p class="text-center text-muted">Seu carrinho está vazio 😢</p>
        <?php else: ?>
            <!-- Lista de itens do carrinho -->
            <ul class="list-group mb-3">
                <?php
                // Inicializa o total da compra
                $total = 0;
                
                // Itera sobre cada ID de produto armazenado na sessão do carrinho
                foreach ($_SESSION['carrinho'] as $id) {
                    // Prepara consulta para buscar detalhes do produto no banco usando PDO
                    $stmt = $conn->prepare("SELECT nome, preco, imagem FROM produtos WHERE idProduto=?");
                    $stmt->execute([$id]);
                    $p = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    // Exibe o item se encontrado no banco
                    if ($p):
                        // Soma o preço ao total
                        $total += $p['preco'];
                ?>
                <!-- Item individual do carrinho -->
                <li class="list-group-item d-flex justify-content-between align-items-center">
                    <!-- Container da esquerda: Imagem e informações do produto -->
                    <div class="d-flex align-items-center">
                        <!-- Imagem miniatura do produto -->
                        <img src="public/img/<?php echo htmlspecialchars($p['imagem']); ?>" 
                             style="width:50px;height:50px;object-fit:cover;" 
                             class="rounded me-2"
                             onerror="this.src='public/img/default.jpg';">
                        <div>
                            <!-- Nome do produto -->
                            <strong><?php echo htmlspecialchars($p['nome']); ?></strong><br>
                            <!-- Preço do produto -->
                            <small>R$ <?php echo number_format($p['preco'], 2, ',', '.'); ?></small>
                        </div>
                    </div>
                    
                    <!-- Container da direita: Botão para remover item -->
                    <form method="POST" action="app/controller/remover_carrinho.php">
                        <!-- Campo hidden com ID do produto a ser removido -->
                        <input type="hidden" name="id" value="<?php echo $id; ?>">
                        <!-- Botão de remover com ícone de lixeira -->
                        <button class="btn btn-outline-danger btn-sm"><i class="bi bi-trash"></i></button>
                    </form>
                </li>
                <?php 
                    endif; 
                } // Fim do loop foreach 
                ?>
            </ul>

            <!-- Seção do total da compra -->
            <div class="d-flex justify-content-between mb-3">
                <strong>Total:</strong>
                <!-- Total formatado em Real -->
                <span class="text-success fw-bold">R$ <?php echo number_format($total, 2, ',', '.'); ?></span>
            </div>

            <!-- Botão para finalizar a compra -->
            <a href="app/view/checkout.php" class="btn btn-success w-100">Finalizar Compra</a>
        <?php endif; ?>
    </div>
</div>

<!-- Footer -->
<footer class="footer">
    <div class="container">
        <div class="row gy-4">
            
            <!-- Logo + descrição -->
            <div class="col-lg-6 col-md-3 footer-info">
                <a href="index.php" class="logo align-items-center">
                    <img src="public/img/logo.png" alt="Logo">
                    <span>Brechó Koꓘero</span>
                </a>
                <p>Sua loja online de roupas, estilo e qualidade. Verde, amarelo e preto para realçar sua identidade.</p>
                <div class="social-links d-flex mt-3">
                    <a href="https://wa.me/5511992424158"><i class="bi bi-whatsapp"></i></a>
                    <a href="https://www.instagram.com/brecho.kokero?igsh=aTV4M3YyNmViZXB1"><i class="bi bi-instagram"></i></a>
                </div>
            </div>

            <!-- Links úteis -->
            <div class="col-lg-6 col-md-3 footer-links">
                <h4>Links</h4>
                <ul>
                    <li><a href="index.php">Início</a></li>
                    <li><a href="app/view/produtos.php">Produtos</a></li>
                    <li><a href="app/view/faq.php">FAQ</a></li>
                    <li><a href="app/view/minha_conta.php">Minha Conta</a></li>
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
</body>
</html>