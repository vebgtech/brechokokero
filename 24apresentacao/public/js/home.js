document.addEventListener('DOMContentLoaded', function() {
    // Carregar produtos aleatórios via API
    carregarProdutosAleatorios();
    
    // Exibir mensagens da sessão (se houver)
    if (sessionData.mensagens && sessionData.mensagens.length > 0) {
        sessionData.mensagens.forEach(function(mensagem) {
            if (mensagem.tipo && mensagem.texto) {
                exibirMensagem(mensagem.tipo, mensagem.texto);
            }
        });
    }
    
    // Configurar busca
    const formBusca = document.querySelector('form[role="search"]');
    if (formBusca) {
        formBusca.addEventListener('submit', function(e) {
            e.preventDefault();
            const termo = this.querySelector('input[name="busca"]').value;
            window.location.href = `produtos?busca=${encodeURIComponent(termo)}`;
        });
    }
});

// ========== FUNÇÕES PARA CARREGAR PRODUTOS VIA API ==========

async function carregarProdutosAleatorios() {
    try {
        const response = await fetch('app/apiprodutos.php?action=random&limit=6');
        const data = await response.json();
        
        if (data.success) {
            exibirProdutos(data.data);
        } else {
            exibirErroProdutos('Erro ao carregar produtos');
        }
    } catch (error) {
        console.error('Erro:', error);
        exibirErroProdutos('Erro de conexão');
    }
}

function exibirProdutos(produtos) {
    const container = document.getElementById('produtos-container');
    
    if (!produtos || produtos.length === 0) {
        container.innerHTML = `
            <div class="alert alert-info">
                Nenhum produto disponível no momento.
            </div>
        `;
        return;
    }
    
    // Dividir em duas fileiras (igual ao original)
    const primeiraFileira = produtos.slice(0, 3);
    const segundaFileira = produtos.slice(3, 6);
    
    let html = '';
    
    // Primeira fileira
    html += '<div class="row g-4 index">';
    primeiraFileira.forEach(produto => {
        html += criarCardProduto(produto);
    });
    html += '</div>';
    
    // Linha entre as fileiras
    html += '<hr class="linha-produtos">';
    
    // Segunda fileira
    html += '<div class="row g-4 mt-4 index">';
    segundaFileira.forEach(produto => {
        html += criarCardProduto(produto);
    });
    html += '</div>';
    
    container.innerHTML = html;
}

function criarCardProduto(produto) {
    const imagem = produto.imagem ? `public/img/${produto.imagem}` : 'public/img/default.jpg';
    
    return `
        <div class="col-md-4">
            <a href="produto_detalhes?id=${produto.idProduto}">
                <img src="${imagem}" 
                    alt="${produto.nome}" 
                    class="img-fluid card index-img"
                    onerror="this.src='public/img/default.jpg'">
                <p>${produto.nome}</p>
            </a>
        </div>
    `;
}

function exibirErroProdutos(mensagem) {
    const container = document.getElementById('produtos-container');
    container.innerHTML = `
        <div class="alert alert-danger">
            <i class="bi bi-exclamation-triangle"></i> ${mensagem}
        </div>
    `;
}

// ========== FUNÇÕES AUXILIARES ==========

function exibirMensagem(tipo, mensagem) {
    // Criar toast se não existir
    let toastContainer = document.getElementById('toast-container');
    if (!toastContainer) {
        toastContainer = document.createElement('div');
        toastContainer.id = 'toast-container';
        toastContainer.className = 'toast-container position-fixed top-0 end-0 p-3';
        document.body.appendChild(toastContainer);
    }
    
    const toastId = 'toast-' + Date.now();
    const toast = document.createElement('div');
    toast.className = `toast align-items-center text-bg-${tipo} border-0`;
    toast.id = toastId;
    toast.setAttribute('role', 'alert');
    toast.setAttribute('aria-live', 'assertive');
    toast.setAttribute('aria-atomic', 'true');
    
    toast.innerHTML = `
        <div class="d-flex">
            <div class="toast-body">
                ${mensagem}
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
    `;
    
    toastContainer.appendChild(toast);
    
    // Mostrar o toast
    const bsToast = new bootstrap.Toast(toast, { delay: 3000 });
    bsToast.show();
    
    // Remover o toast após ser escondido
    toast.addEventListener('hidden.bs.toast', function () {
        toast.remove();
    });
}