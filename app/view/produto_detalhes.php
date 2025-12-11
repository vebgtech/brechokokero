<?php
require_once 'app/config/conexao.php';
require_once 'app/model/carrinho.php';
require_once 'app/view/carrinho_view.php';
require_once 'app/controller/carrinho_controller.php';
$conn = Conexao::getConexao();

$produto = null;
$erro = '';
$sucesso = '';
$idProduto = 0;

$carrinhoController = new CarrinhoController($conn);
$carrinho = $carrinhoController->getCarrinho();
$carrinhoView = new CarrinhoView($carrinho);
echo $carrinhoView->renderizarOffcanvas();

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
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="public/img/logo.png">
    <title><?php echo isset($produto['nome']) ? htmlspecialchars($produto['nome']) . ' - Brechó Kokero' : 'Produto - Brechó Kokero'; ?></title> 
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
                <img src="public/img/<?php echo htmlspecialchars($produto['imagem'] ?? 'default.jpg'); ?>" 
                     class="img-fluid rounded" 
                     alt="<?php echo htmlspecialchars($produto['nome']); ?>"
                     onerror="this.src='public/img/default.jpg';">
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
        <!-- FORM CORRETO PARA POO -->
        <form method="POST" action="adicionar_carrinho">
            <input type="hidden" name="id" value="<?php echo $produto['idProduto']; ?>">
            <button type="submit" class="btn btn-success btn-lg w-100">
                <i class="bi bi-cart-plus me-2"></i>Adicionar ao Carrinho
            </button>
        </form>
    <?php else: ?>
        <div class="d-grid">
            <button type="button" class="btn btn-success btn-lg w-100" onclick="redirecionarLogin()">
                <i class="bi bi-person-circle me-2"></i>Faça login para comprar
            </button>
        </div>
    <?php endif; ?>
<?php else: ?>
    <button class="btn btn-secondary btn-lg w-100" disabled>
        <i class="bi bi-ban me-2"></i>Produto Vendido
    </button>
<?php endif; ?>
                
            </div>
        </div>
    <?php endif; ?>
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
<script>
// Função para abrir o carrinho
function abrirCarrinho() {
    const offcanvasElement = document.getElementById('carrinhoOffcanvas');
    if (offcanvasElement) {
        const offcanvas = new bootstrap.Offcanvas(offcanvasElement);
        offcanvas.show();
    }
}

      function redirecionarLogin() {
          // Redireciona para a página de login
          window.location.href = 'login';
          
          // Ou se quiser redirecionar com parâmetros:
          // window.location.href = 'login.php?redirect=' + encodeURIComponent(window.location.pathname);
      }


// Monitorar adição ao carrinho via AJAX
document.addEventListener('DOMContentLoaded', function() {
    // Para forms de adicionar ao carrinho
    const formsCarrinho = document.querySelectorAll('form[action*="adicionar_carrinho"]');
    
    formsCarrinho.forEach(form => {
        form.addEventListener('submit', function(e) {
            // Se for AJAX, não faz nada aqui
            if (this.classList.contains('ajax-form')) return;
            
            // Adiciona flag na sessão para abrir carrinho
            sessionStorage.setItem('abrirCarrinho', 'true');
        });
    });
    
    // Verificar se deve abrir o carrinho
    if (sessionStorage.getItem('abrirCarrinho') === 'true') {
        setTimeout(() => {
            abrirCarrinho();
            sessionStorage.removeItem('abrirCarrinho');
        }, 500);
    }
    
    // Mostrar mensagens da sessão
    <?php if (isset($_SESSION['mensagem_carrinho'])): ?>
        const mensagem = <?php echo json_encode($_SESSION['mensagem_carrinho']); ?>;
        
        // Criar alerta
        const alertDiv = document.createElement('div');
        alertDiv.className = `alert alert-${mensagem.tipo} alert-dismissible fade show position-fixed top-0 end-0 m-3`;
        alertDiv.style.zIndex = '9999';
        alertDiv.innerHTML = `
            ${mensagem.texto}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        document.body.appendChild(alertDiv);
        
        // Auto remover após 3 segundos
        setTimeout(() => {
            alertDiv.remove();
        }, 3000);
        
        // Se foi adição ao carrinho, abre o carrinho
        if (mensagem.tipo === 'success' && mensagem.texto.includes('adicionado')) {
            setTimeout(() => {
                abrirCarrinho();
            }, 800);
        }
        
        // Limpar mensagem da sessão
        <?php unset($_SESSION['mensagem_carrinho']); ?>
    <?php endif; ?>
});
</script>

</body>
</html>