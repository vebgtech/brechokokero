<?php
// app/classes/CarrinhoView.php

class CarrinhoView {
    private $carrinho;
    
    public function __construct($carrinho) {
        $this->carrinho = $carrinho;
    }
    
    // MÉTODO PARA RENDERIZAR O BOTÃO DO CARRINHO (IGUAL AO SEU)
    public function renderizarBotaoCarrinho() {
        $quantidade = $this->carrinho->getQuantidadeItens();
        
        return '
        <li class="nav-item">
            <a class="nav-link" href="#" data-bs-toggle="offcanvas" data-bs-target="#carrinhoOffcanvas" aria-controls="carrinhoOffcanvas">
                <i class="bi bi-cart-fill" style="font-size: 1.5rem;"></i> Carrinho
                <span class="badge rounded-pill bg-success">
                    ' . $quantidade . '
                </span>
            </a>
        </li>';
    }
    
    // MÉTODO PARA RENDERIZAR O OFFCANVAS (IGUAL AO SEU ORIGINAL)
    public function renderizarOffcanvas() {
        ob_start();
        ?>
        <!-- Offcanvas/Sidebar do carrinho de compras -->
        <div class="offcanvas offcanvas-end" tabindex="-1" id="carrinhoOffcanvas">
            <div class="offcanvas-header">
                <h5 class="offcanvas-title"><i class="bi bi-bag"></i> Meu Carrinho</h5>
                <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
            </div>
            
            <div class="offcanvas-body">
                <?php echo $this->renderizarConteudo(); ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    private function renderizarConteudo() {
        ob_start();
        
        // Verifica se o usuário está logado
        if (!isset($_SESSION['usuario_id']) || empty($_SESSION['usuario_id'])) {
            $this->renderizarNaoLogado();
        } elseif ($this->carrinho->estaVazio()) {
            $this->renderizarCarrinhoVazio();
        } else {
            $this->renderizarItens();
        }
        
        return ob_get_clean();
    }
    
    private function renderizarNaoLogado() {
        ?>
        <div class="text-center py-4">
            <i class="bi bi-person-x" style="font-size: 3rem; color: #6c757d;"></i>
            <p class="mt-3">Você precisa estar logado para ver o carrinho.</p>
            <a href="login" class="btn btn-primary mt-2">Fazer Login</a>
        </div>
        <?php
    }
    
    private function renderizarCarrinhoVazio() {
        ?>
        <p class="text-center text-muted">Seu carrinho está vazio 😢</p>
        <?php
    }
    
    private function renderizarItens() {
        $itens = $this->carrinho->getDetalhesItens();
        $total = $this->carrinho->getTotal();
        ?>
        <ul class="list-group mb-3">
            <?php
            foreach ($itens as $produto):
                // Verifica se é um objeto Produto
                if ($produto instanceof Produto):
            ?>
                <li class="list-group-item d-flex justify-content-between align-items-center">
                    <!-- Container da esquerda: Imagem e informações do produto -->
                    <div class="d-flex align-items-center">
                        <!-- Imagem miniatura do produto -->
                        <img src="public/img/<?php echo htmlspecialchars($produto->getImagem()); ?>" 
                             style="width:50px;height:50px;object-fit:cover;" 
                             class="rounded me-2"
                             onerror="this.src='public/img/default.jpg';">
                        <div>
                            <!-- Nome do produto -->
                            <strong><?php echo htmlspecialchars($produto->getNome()); ?></strong><br>
                            <!-- Preço do produto -->
                            <small>R$ <?php echo number_format($produto->getPreco(), 2, ',', '.'); ?></small>
                        </div>
                    </div>
                    
                    <!-- Container da direita: Botão para remover item -->
                    <form method="POST" action="remover_carrinho">
                        <!-- Campo hidden com ID do produto a ser removido -->
                        <input type="hidden" name="id" value="<?php echo $produto->getIdProduto(); ?>">
                        <!-- Botão de remover com ícone de lixeira -->
                        <button class="btn btn-outline-danger btn-sm"><i class="bi bi-trash"></i></button>
                    </form>
                </li>
            <?php 
                endif;
            endforeach; 
            ?>
        </ul>

        <!-- Seção do total da compra -->
        <div class="d-flex justify-content-between mb-3">
            <strong>Total:</strong>
            <!-- Total formatado em Real -->
            <span class="text-success fw-bold">R$ <?php echo number_format($total, 2, ',', '.'); ?></span>
        </div>

        <!-- Botão para finalizar a compra -->
        <a href="checkout" class="btn btn-success w-100">Finalizar Compra</a>
        <?php
    }
    
    public function renderizarBotaoAdicionar($produto) {
        // Se $produto for array, converte para objeto Produto
        if (is_array($produto)) {
            // Converte array para objeto Produto
            $produtoObj = new Produto(
                $produto['nome'],
                $produto['marca'],
                $produto['tamanho'],
                $produto['estado'],
                $produto['idCategoria'],
                $produto['preco'],
                $produto['imagem'],
                $produto['promocao'],
                $produto['estoque'],
                $produto['descricao'],
                $produto['vendido'],
                $produto['dataVenda'],
                $produto['idProduto']
            );
            $produto = $produtoObj;
        }
        
        $id = $produto->getIdProduto();
        $vendido = $produto->isVendido();
        
        ob_start();
        ?>
        <?php if (!$vendido): ?>
            <?php if (isset($_SESSION['usuario_id']) && !empty($_SESSION['usuario_id'])): ?>
                <form method="POST" action="adicionar_carrinho">
                    <input type="hidden" name="id" value="<?php echo $id; ?>">
                    <button type="submit" class="btn btn-success btn-lg w-100">
                        <i class="bi bi-cart-plus me-2"></i>Adicionar ao Carrinho
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
            <button class="btn btn-secondary btn-lg w-100" disabled>Produto Vendido</button>
        <?php endif; ?>
        <?php
        return ob_get_clean();
    }
}
?>