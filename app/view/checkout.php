<?php
require_once 'app/config/conexao.php';
require_once 'app/model/carrinho.php';
require_once 'app/view/carrinho_view.php';
require_once 'app/controller/carrinho_controller.php';

$conn = Conexao::getConexao();

$carrinhoController = new CarrinhoController($conn);
$carrinho = $carrinhoController->getCarrinho();
$carrinhoView = new CarrinhoView($carrinho);
echo $carrinhoView->renderizarOffcanvas();

if (!isset($_SESSION['carrinho']) || count($_SESSION['carrinho']) === 0) {
    header("Location: produtos.php");
}
 
$itensTexto = "";
$total = 0;

foreach ($_SESSION['carrinho'] as $id) {
    $stmt = $conn->prepare("SELECT nome, preco FROM produtos WHERE idProduto = ?");
    $stmt->execute([$id]);
    
    if ($p = $stmt->fetch()) {
        $total += $p['preco'];
        $itensTexto .= "• " . $p['nome'] . " - R$ " . number_format($p['preco'], 2, ',', '.') . "%0A";
    }
}

$mensagemBase = "✨ *NOVO PEDIDO - BRECHO KOKERO* ✨%0A%0A" .
                "➤ *DADOS DO CLIENTE:*%0A" .
                "   • *Nome:* [NOME]%0A" .
                "   • *Telefone:* [TELEFONE]%0A" .
                "   • *Endereço:* [ENDERECO]%0A" .
                "   • *Cidade/UF:* [CIDADE_UF]%0A" .
                "   • *CEP:* [CEP]%0A" .
                "[COMPLEMENTO]" .
                "[OBSERVACOES]" .
                "%0A➤ *ITENS DO PEDIDO:*%0A" .
                $itensTexto .
                "%0A➤ *SUBTOTAL (produtos):* R$ " . number_format($total, 2, ',', '.') . "%0A%0A" .
                "➤ *FRETE E TOTAL FINAL:*%0A" .
                "*A combinar via WhatsApp*%0A%0A" .
                "_Obrigada pela preferencia!_";

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
    $stmt->execute($params);
    return $stmt;
}

$estados = $conn->query("SELECT DISTINCT estado FROM produtos WHERE estado IS NOT NULL AND vendido=0 ORDER BY estado");
$tamanho = $conn->query("SELECT DISTINCT tamanho FROM produtos WHERE tamanho IS NOT NULL AND vendido=0 ORDER BY tamanho");
$marcas = $conn->query("SELECT DISTINCT marca FROM produtos WHERE marca IS NOT NULL AND vendido=0 ORDER BY marca");

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
$produtos_promocao_filtrados = [];
while ($p = $stmt_promocao->fetch()) {
    if ($p['promocao'] == 1) {
        $produtos_promocao_filtrados[] = $p;
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <link rel="icon" type="image/png" href="public/img/logo.png">
  <title>Finalizar Compra | Brechó Kokero</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
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
<div class="checkout-container">
  <h2 class="text-success"><i class="bi bi-bag-check"></i> Finalizar Compra</h2>

  <div class="checkout-grid">
    <div class="checkout-dados-cliente">
      <div class="checkout-card">
        <div class="card-header">Seus dados de contato 💌</div>
        <div class="card-body">
          
          <div class="aviso-frete">
            <h6><span class="emoji">🚚💕</span> Sobre o frete e entrega:</h6>
            <p class="mb-2">
                ✨ <strong>Caro cliente!</strong> O valor do frete e o total final serão combinados diretamente 
                no WhatsApp com nossa equipe!<br><br>
                
                Isso nos permite calcular o frete exato para sua região e te dar 
                um atendimento super personalizado! <br><br>
                
                <strong>Não se preocupe!</strong> Vamos encontrar a opção de entrega mais 
                rápida econômica para você! 
            </p>
          </div>
          
          <form class="checkout-form" id="checkoutForm">
            <div class="form-group">
              <label class="form-label">Nome completo</label>
              <input type="text" id="nome" class="form-control" required placeholder="Seu nome completo">
            </div>
            
            <div class="form-group">
              <label class="form-label">Telefone (WhatsApp)</label>
              <input type="tel" id="telefone" class="form-control" required placeholder="(11) 99999-9999">
            </div>
            
            <div class="form-group">
              <label class="form-label">CEP</label>
              <input type="text" id="cep" class="form-control" required placeholder="00000-000">
            </div>
            
            <div class="form-group">
              <label class="form-label">Rua</label>
              <input type="text" id="rua" class="form-control" required>
            </div>
            
            <div class="form-group">
              <label class="form-label">Número</label>
              <input type="text" id="numero" class="form-control" required>
            </div>
            
            <div class="form-group">
              <label class="form-label">Bairro</label>
              <input type="text" id="bairro" class="form-control" required>
            </div>
            
            <div class="form-group">
              <label class="form-label">Cidade</label>
              <input type="text" id="cidade" class="form-control" required>
            </div>
            
            <div class="form-group">
              <label class="form-label">Estado</label>
              <input type="text" id="estado" class="form-control" required>
            </div>

            <div class="form-group">
              <label class="form-label">Complemento (opcional)</label>
              <input type="text" id="complemento" class="form-control" placeholder="Apartamento, bloco, referência...">
            </div>

            <div class="form-group">
              <label class="form-label">Alguma observação? (opcional)</label>
              <textarea id="observacao" class="form-control" rows="3" placeholder="Nos conte algo importante sobre a entrega ou seu pedido..."></textarea>
            </div>
          </form>
        </div>
      </div>
    </div>

    <!-- 💰 SEÇÃO: Resumo do Pedido -->
    <div class="checkout-resumo-pedido">
      <div class="checkout-card">
        <div class="card-header">Resumo do Pedido</div>
        <div class="card-body">
          <ul class="checkout-list mb-3">
            <?php
              $total = 0;
              
              foreach ($_SESSION['carrinho'] as $id) {
                  $stmt = $conn->prepare("SELECT nome, preco, imagem FROM produtos WHERE idProduto = ?");
                  $stmt->execute([$id]);
                  
                  if ($p = $stmt->fetch()) {
                      $total += $p['preco'];
                      
                      echo '<li class="list-group-item d-flex justify-content-between align-items-center">'
                        . '<div class="d-flex align-items-center">'
                        . '<img src="public/img/' . htmlspecialchars($p['imagem'] ?? 'default.jpg') . '" class="produto-img me-3" alt="' . htmlspecialchars($p['nome']) . '">'
                        . '<div class="produto-info">'
                        . '<div class="produto-nome">' . htmlspecialchars($p['nome']) . '</div>'
                        . '</div>'
                        . '</div>'
                        . '<span class="produto-preco">R$ ' . number_format($p['preco'], 2, ',', '.') . '</span></li>';
                  }
              }
            ?>
          </ul>
          
          <div class="checkout-total">
            <div class="total-line">
              <span>Subtotal dos produtos:</span>
              <span>R$ <?php echo number_format($total, 2, ',', '.'); ?></span>
            </div>
            <div class="total-line">
              <span>Frete:</span>
              <span>A combinar </span>
            </div>
            <div class="total-final">
              <span>Total estimado:</span>
              <span>R$ <?php echo number_format($total, 2, ',', '.'); ?></span>
            </div>
          </div>
          <div class="alert alert-warning mb-3">
            <small> <strong>Encontraremos um frete que cabe no seu bolso!</strong> </small>
          </div>
          <button id="enviarWhatsApp" class="btn-whatsapp" data-mensagem-base="<?php echo htmlspecialchars($mensagemBase); ?>">
            <i class="bi bi-whatsapp"></i> Finalizar pedido no WhatsApp
          </button>
        </div>
      </div>
    </div>
  </div>
</div>

  <!-- Footer -->
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

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    console.log('✅ Checkout carregado!');
   
    const telefoneInput = document.getElementById('telefone');
    if (telefoneInput) {
        telefoneInput.addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            e.target.removeAttribute('maxlength');
            
            if (value.length > 11) value = value.substring(0, 11);
            
            let formattedValue = '';
            if (value.length > 0) formattedValue = '(' + value.substring(0, 2);
            if (value.length > 2) formattedValue += ') ' + value.substring(2, 7);
            if (value.length > 7) formattedValue += '-' + value.substring(7, 11);
            
            e.target.value = formattedValue;
        });
    }

    const cepInput = document.getElementById('cep');
    if (cepInput) {
        cepInput.addEventListener('blur', function() {
            const cep = this.value.replace(/\D/g, '');
            
            if (cep.length !== 8) {
                this.classList.add('is-invalid');
                let errorMsg = this.parentNode.querySelector('.invalid-feedback');
                if (!errorMsg) {
                    errorMsg = document.createElement('div');
                    errorMsg.className = 'invalid-feedback';
                    this.parentNode.appendChild(errorMsg);
                }
                errorMsg.textContent = 'CEP deve conter exatamente 8 dígitos';
                return;
            }
            
            this.classList.remove('is-invalid');
            this.disabled = true;
            this.placeholder = 'Buscando endereço...';
            
            fetch(`https://viacep.com.br/ws/${cep}/json/`)
                .then(res => res.json())
                .then(data => {
                    this.disabled = false;
                    this.placeholder = '00000-000';
                    
                    if (data.erro) {
                        this.classList.add('is-invalid');
                        let errorMsg = this.parentNode.querySelector('.invalid-feedback');
                        if (!errorMsg) errorMsg = document.createElement('div');
                        errorMsg.className = 'invalid-feedback';
                        errorMsg.textContent = 'CEP não encontrado';
                        this.parentNode.appendChild(errorMsg);
                        return;
                    }
                    
                    document.getElementById('rua').value = data.logradouro || '';
                    document.getElementById('bairro').value = data.bairro || '';
                    document.getElementById('cidade').value = data.localidade || '';
                    document.getElementById('estado').value = data.uf || '';
                    document.getElementById('numero').focus();
                })
                .catch(() => {
                    this.disabled = false;
                    this.placeholder = '00000-000';
                    this.classList.add('is-invalid');
                    let errorMsg = this.parentNode.querySelector('.invalid-feedback');
                    if (!errorMsg) errorMsg = document.createElement('div');
                    errorMsg.className = 'invalid-feedback';
                    errorMsg.textContent = 'Erro ao buscar CEP';
                    this.parentNode.appendChild(errorMsg);
                });
        });

        cepInput.addEventListener('input', function() {
            let cep = this.value.replace(/\D/g, '');
            if (cep.length > 5) cep = cep.substring(0, 5) + '-' + cep.substring(5, 8);
            this.value = cep;
            
            if (this.classList.contains('is-invalid')) {
                this.classList.remove('is-invalid');
                const errorMsg = this.parentNode.querySelector('.invalid-feedback');
                if (errorMsg) errorMsg.remove();
            }
        });
    }

    const enviarWhatsAppBtn = document.getElementById('enviarWhatsApp');
    if (enviarWhatsAppBtn) {
        enviarWhatsAppBtn.addEventListener('click', function() {
            console.log('🎯 Botão WhatsApp clicado!');
            
            const nome = document.getElementById('nome').value.trim();
            const telefone = document.getElementById('telefone').value.trim();
            const cep = document.getElementById('cep').value.trim();
            const rua = document.getElementById('rua').value.trim();
            const numero = document.getElementById('numero').value.trim();
            const bairro = document.getElementById('bairro').value.trim();
            const cidade = document.getElementById('cidade').value.trim();
            const estado = document.getElementById('estado').value.trim();
            const complemento = document.getElementById('complemento').value.trim();
            const observacao = document.getElementById('observacao').value.trim();

            if (!nome || !telefone || !cep || !rua || !numero || !bairro || !cidade || !estado) {
                alert('Por favor, preencha todos os campos obrigatórios! 💝');
                return;
            }

            const telefoneLimpo = telefone.replace(/\D/g, '');
            if (telefoneLimpo.length < 10) {
                alert('Por favor, digite um telefone válido com DDD! 📱');
                return;
            }

            const numeroVendedor = "5511962474158";
            let mensagem = this.getAttribute('data-mensagem-base');
            mensagem = mensagem.replace('[NOME]', nome);
            mensagem = mensagem.replace('[TELEFONE]', telefone);
            mensagem = mensagem.replace('[ENDERECO]', `${rua}, ${numero} - ${bairro}`);
            mensagem = mensagem.replace('[CIDADE_UF]', `${cidade}/${estado}`);
            mensagem = mensagem.replace('[CEP]', cep);
            mensagem = mensagem.replace('[COMPLEMENTO]', complemento ? `*Complemento:* ${complemento}%0A` : '');
            mensagem = mensagem.replace('[OBSERVACOES]', observacao ? `*Observações:* ${observacao}%0A` : '');

            const link = `https://wa.me/${numeroVendedor}?text=${mensagem}`;

            console.log('📦 Salvando pedido no banco...');
            fetch('salvar_pedido', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    nome_cliente: nome,
                    telefone: telefone,
                    endereco: `${rua}, ${numero} - ${bairro}, ${cidade}/${estado} - CEP: ${cep}`,
                    observacoes_pagamento: observacao,
                    status_pagamento: 'pendente'
                })
            })
            .then(response => {
                console.log('📨 Resposta do servidor recebida');
                return response.text();
            })
            .then(result => {
                console.log('✅ Pedido salvo:', result);
                console.log('📱 Abrindo WhatsApp...');
                window.open(link, '_blank');
                setTimeout(() => {
                    console.log('🔄 Redirecionando para produtos...');
                    window.location.href = 'produtos';
                }, 1000);
            })
            .catch((error) => {
                console.error('❌ Erro:', error);
                window.open(link, '_blank');
                setTimeout(() => {
                    window.location.href = 'produtos';
                }, 1000);
            });

            this.disabled = true;
            this.innerHTML = '<i class="bi bi-whatsapp"></i> Enviando...';
        });
    }

    const formCheckout = document.getElementById('checkoutForm');
    if (formCheckout) {
        formCheckout.addEventListener('submit', function(e) {
            e.preventDefault();
            const submitBtn = this.querySelector('button[type="submit"]');
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="bi bi-whatsapp"></i> Enviando...';
            }
        });
    }
});
</script>

</body>
</html>