<?php
session_start();
require_once '../config/conexao.php';
$conn = Conexao::getConexao();

$produto = null;
$erro = '';
$sucesso = '';
$idProduto = 0;

if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $idProduto = (int)$_GET['id'];
    
    try {
        $stmt = $conn->prepare("SELECT * FROM produtos WHERE idProduto = ?");
        $stmt->execute([$idProduto]);
        $produto = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$produto) {
            $erro = "Produto não encontrado.";
        }
    } catch (PDOException $e) {
        $erro = "Erro ao buscar produto: " . $e->getMessage();
    }
} else {
    $erro = "ID do produto não especificado.";
}

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
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="../../public/img/logo.png">
    <title><?php echo isset($produto['nome']) ? htmlspecialchars($produto['nome']) . ' - Brechó Kokero' : 'Produto - Brechó Kokero'; ?></title> 
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../../public/css/estilo.css">
</head>
<body>
  <nav class="navbar navbar-expand-lg">
    <div class="container-fluid">
      <a class="navbar-brand" href="../../public/index.php">
        <img src="../../public/img/logo.png" alt="Logo" style="height: 80px; width:auto;">
      </a>
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarContent">
        <span class="navbar-toggler-icon" style="color:#fff;"></span>
      </button>
      <div class="collapse navbar-collapse" id="navbarContent">
        <ul class="navbar-nav mx-auto mb-2 mb-lg-0">
          <li class="nav-item"><a class="nav-link" href="../../public/index.php">Início</a></li>
          <li class="nav-item"><a class="nav-link active" href="produtos.php">Produtos</a></li>
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
          <input class="form-control me-2" type="search" name="busca" placeholder="Buscar produtos..." aria-label="Search">
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

<div class="container mt-5">
    <?php if ($erro): ?>
        <div class="alert alert-danger text-center">
            <h4>Erro</h4>
            <p><?php echo htmlspecialchars($erro); ?></p>
        </div>
    <?php else: ?>

        <?php if ($sucesso): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <?php echo $sucesso; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="row">
            <div class="col-md-6 imagem-produto">
                <img src="../../public/img/<?php echo htmlspecialchars($produto['imagem'] ?? 'default.jpg'); ?>" 
                     class="img-fluid rounded" 
                     alt="<?php echo htmlspecialchars($produto['nome']); ?>"
                     onerror="this.src='../../public/img/default.jpg';">
            </div>

            <div class="col-md-6 produto-detalhe">
                <h2><?php echo htmlspecialchars($produto['nome']); ?></h2>
                <p class="price">R$ <?php echo number_format($produto['preco'], 2, ',', '.'); ?></p>
                <p>
                  <?php if ($produto['vendido'] == 1): ?>
                      <span class="badge bg-danger mt-2 p-2">Já foi vendido</span>
                  <?php else: ?>
                      <span class="badge bg-info mt-2 p-2">Produto Único</span>
                  <?php endif; ?>
                </p>

                <p><strong>Marca:</strong> <?php echo htmlspecialchars($produto['marca'] ?? 'Não especificada'); ?></p>
                <p><strong>Tamanho:</strong> <?php echo htmlspecialchars($produto['tamanho'] ?? 'N/A'); ?></p>
                <p><strong>Estado:</strong> <?php echo htmlspecialchars($produto['estado'] ?? 'Não especificado'); ?></p>
                <p><strong>Descrição:</strong><br><?php echo nl2br(htmlspecialchars($produto['descricao'] ?? 'Descrição não disponível.')); ?></p>
                
<?php if ($produto['vendido'] == 0): ?>
    <?php if (isset($_SESSION['usuario_id']) && !empty($_SESSION['usuario_id'])): ?>
        <form method="POST" id="form-carrinho">
            <input type="hidden" name="acao" value="adicionar_carrinho">
            <input type="hidden" name="id_produto" value="<?php echo $idProduto; ?>">
            <button type="submit" class="btn btn-success btn-lg" id="btn-adicionar-carrinho">
                Adicionar ao Carrinho
            </button>
        </form>
    <?php else: ?>
        <div class="d-grid">
            <button type="button" class="btn btn-success btn-lg" onclick="redirecionarLogin()">
                <i class="bi bi-person-circle me-2"></i>Faça login para comprar
            </button>
            <p class="text-muted mt-2 small">
                <i class="bi bi-info-circle"></i> Você precisa estar logado para adicionar itens ao carrinho.
            </p>
        </div>
    <?php endif; ?>
<?php else: ?>
    <p class="vendido mt-3">Este produto já foi vendido.</p>
<?php endif; ?>
                
            </div>
        </div>
    <?php endif; ?>
</div>

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
        <a href="../../public/index.php" class="logo align-items-center">
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
          <li><a href="../../public/index.php">Início</a></li>
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
<script>
document.addEventListener("DOMContentLoaded", () => {
    const sucesso = <?php echo isset($sucesso) && $sucesso != '' ? 'true' : 'false'; ?>;
    if (sucesso) {
        const offcanvas = new bootstrap.Offcanvas(document.getElementById('carrinhoOffcanvas'));
        setTimeout(() => offcanvas.show(), 500);
    }
    
    if (window.history.replaceState) {
        window.history.replaceState(null, null, window.location.href);
    }
});

document.querySelector('#form-carrinho')?.addEventListener('submit', function(e) {
    const submitBtn = this.querySelector('button[type="submit"]');
    if (submitBtn) {
        submitBtn.disabled = true;
        submitBtn.innerHTML = 'Adicionando...';
    }
});
        function redirecionarLogin() {
            localStorage.setItem('pagina_retorno', window.location.href);
            window.location.href = 'log.php';
        }

</script>

</body>
</html>