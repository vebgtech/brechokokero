class AdminApp {
    constructor() {
        this.API_BASE_URL = 'api';
        this.categoriasFixa = [
            'Bermudas e Shorts', 'Blazers', 'Blusas e Camisas',
            'Calças', 'Casacos e Jaquetas', 'Conjuntos',
            'Saias', 'Sapatos', 'Social', 'Vestidos'
        ];
        this.estados = ['Novo', 'Semi-novo', 'Usado'];
        this.produtos = [];
        this.produtoEditando = null;

        console.log('AdminApp iniciado');
        this.init();
    }

    async init() {
        console.log('Inicializando AdminApp...');

        try {
            this.mostrarLoadingProdutos();
            await this.carregarProdutos();
            this.renderizarFormCadastro();
            this.renderizarFiltros();
            this.configurarEventos();

            this.mostrarMensagem('Sistema carregado com sucesso!', 'success');
            console.log('AdminApp inicializado');

        } catch (error) {
            console.error('Erro na inicialização:', error);
            this.mostrarMensagem(`Erro: ${error.message}`, 'error');
        }
    }

    async carregarProdutos(filtros = {}) {
    try {
        console.log('Carregando produtos...');

        const queryParams = new URLSearchParams();

        // Por padrão, NÃO enviar filtro de vendido para ver TODOS
        for (const [key, value] of Object.entries(filtros)) {
            if (value !== '' && value !== null && value !== undefined) {
                queryParams.append(key, value);
            }
        }

        const url = queryParams.toString()
            ? `${this.API_BASE_URL}?action=listar&${queryParams.toString()}`
            : `${this.API_BASE_URL}?action=listar`;

        console.log('URL:', url);

        const response = await fetch(url, {
            method: 'GET',
            credentials: 'include',
            headers: {
                'Accept': 'application/json'
            }
        });

        if (!response.ok) {
            throw new Error(`HTTP ${response.status}`);
        }

        const result = await response.json();

        if (!result.success) {
            throw new Error(result.message || 'Erro ao carregar produtos');
        }

        this.produtos = result.data || [];
        console.log(`${this.produtos.length} produtos carregados`);
        this.renderizarProdutos();

        return this.produtos;

    } catch (error) {
        console.error('Erro ao carregar produtos:', error);
        this.mostrarMensagem(`Erro ao carregar produtos: ${error.message}`, 'error');

        this.produtos = [];
        this.renderizarProdutos();

        throw error;
    }
}

    async fazerRequisicao(endpoint, method = 'GET', data = null, isFormData = false) {
        const url = `${this.API_BASE_URL}?action=${endpoint}`;
        console.log(`${method} ${url}`, isFormData ? '(FormData)' : '(JSON)');

        const options = {
            method,
            credentials: 'include',
            headers: {}
        };

        if (data) {
            if (isFormData) {
                options.body = data;
                console.log('Enviando FormData com', data.entries ? Array.from(data.entries()).length : 0, 'campos');
            } else {
                options.headers['Content-Type'] = 'application/json';
                options.body = JSON.stringify(data);
                console.log('Enviando JSON:', options.body);
            }
        }

        try {
            console.log('Enviando requisição...');
            const response = await fetch(url, options);

            console.log('Status da resposta:', response.status);

            const responseText = await response.text();

            if (responseText.trim().startsWith('<!DOCTYPE') ||
                responseText.trim().startsWith('<html')) {
                console.error('Resposta contém HTML:', responseText.substring(0, 500));
                throw new Error('O servidor retornou uma página de erro. Verifique os logs do PHP.');
            }

            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }

            let result;
            try {
                result = JSON.parse(responseText);
            } catch (jsonError) {
                console.error('Erro ao parsear JSON:', jsonError);
                console.error('Texto recebido:', responseText.substring(0, 500));
                throw new Error('Resposta do servidor não é um JSON válido');
            }

            if (!result.success) {
                console.error('API Error:', result.message);
                throw new Error(result.message || 'Erro na API');
            }

            console.log('Requisição bem-sucedida:', result);
            return result;

        } catch (error) {
            console.error('Erro na requisição:', error);

            if (error.name === 'TypeError' && error.message.includes('Failed to fetch')) {
                throw new Error('Erro de rede: Não foi possível conectar ao servidor. Verifique a URL: ' + url);
            }

            throw error;
        }
    }

    async cadastrarProduto(formData) {
        try {
            this.mostrarMensagem('Cadastrando produto...', 'info');

            console.log('Enviando dados para API...');

            const result = await this.fazerRequisicao('cadastrar', 'POST', formData, true);

            if (result.success) {
                this.mostrarMensagem('Produto cadastrado com sucesso!', 'success');
                await this.carregarProdutos();
                this.limparFormularioCadastro();
            } else {
                throw new Error(result.message || 'Erro desconhecido');
            }

        } catch (error) {
            console.error('Erro ao cadastrar produto:', error);

            let mensagemErro = error.message;
            if (error.message.includes('HTTP 400')) {
                mensagemErro = 'Erro de validação: Verifique se todos os campos obrigatórios foram preenchidos corretamente.';
            }

            this.mostrarMensagem(`${mensagemErro}`, 'error');

            window.scrollTo({ top: 0, behavior: 'smooth' });
        }
    }

    async atualizarProduto() {
        try {
            console.log('INICIANDO ATUALIZAÇÃO');

            const form = document.getElementById('form-editar-produto');
            if (!form) {
                throw new Error('Formulário de edição não encontrado');
            }

            console.log('DEBUG DO FORMULÁRIO');
            console.log('Formulário encontrado:', form);
            console.log('Form action:', form.action);
            console.log('Form method:', form.method);
            console.log('Form enctype:', form.enctype);

            const formData = new FormData(form);

            console.log('DEBUG DO INPUT DE ARQUIVO');
            const fileInput = document.getElementById('nova-imagem');
            console.log('Input de arquivo encontrado:', fileInput);

            if (fileInput) {
                console.log('Arquivo selecionado:', fileInput.files[0]);
                console.log('Número de arquivos:', fileInput.files.length);

                if (fileInput.files.length > 0) {
                    const file = fileInput.files[0];
                    console.log('Detalhes completos do arquivo:');
                    console.log('  - Nome:', file.name);
                    console.log('  - Tamanho:', file.size, 'bytes', '(', Math.round(file.size / 1024), 'KB)');
                    console.log('  - Tipo:', file.type);
                    console.log('  - Última modificação:', new Date(file.lastModified).toLocaleString());
                    console.log('  - É arquivo válido?', file instanceof File);

                    if (file.size > 5 * 1024 * 1024) {
                        throw new Error('A imagem é muito grande (máximo 5MB)');
                    }

                    const validTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
                    if (!validTypes.includes(file.type.toLowerCase())) {
                        throw new Error('Tipo de arquivo não suportado. Use JPG, PNG ou GIF.');
                    }
                } else {
                    console.log('Nenhum arquivo selecionado no input "nova-imagem"');
                }
            } else {
                console.log('Input "nova-imagem" não encontrado no DOM');
            }

            console.log('VERIFICANDO TODOS OS INPUTS');
            const allInputs = form.querySelectorAll('input, select, textarea');
            console.log('Total de campos:', allInputs.length);
            allInputs.forEach((input, index) => {
                console.log(`${index + 1}. ${input.name || input.id || 'sem-nome'}:`,
                    input.type || input.tagName,
                    '- Valor:', input.value);
            });

            console.log('CONTEÚDO COMPLETO DO FORMDATA');
            console.log('Número total de entradas:', Array.from(formData.entries()).length);

            for (let pair of formData.entries()) {
                const key = pair[0];
                const value = pair[1];

                if (value instanceof File) {
                    console.log(`${key}: FILE - ${value.name} (${value.size} bytes, ${value.type})`);
                } else if (value instanceof Blob) {
                    console.log(`${key}: BLOB - ${value.size} bytes, ${value.type}`);
                } else if (typeof value === 'string' && value.length > 100) {
                    console.log(`${key}: STRING - ${value.substring(0, 100)}... (${value.length} chars)`);
                } else {
                    console.log(`${key}: ${typeof value} -`, value);
                }
            }

            const id = formData.get('id');
            console.log('ID do produto no FormData:', id);
            if (!id) {
                throw new Error('ID do produto não informado');
            }

            const opcaoImagem = document.querySelector('input[name="opcao_imagem"]:checked');
            if (opcaoImagem) {
                console.log('Opção de imagem selecionada:', opcaoImagem.value);
                formData.append('opcao_imagem', opcaoImagem.value);
            } else {
                console.log('Nenhuma opção de imagem selecionada');
            }

            console.log('Processando checkboxes...');
            const promocaoCheckbox = document.getElementById('editar-promocao');
            const vendidoCheckbox = document.getElementById('editar-vendido');

            if (promocaoCheckbox) {
                const promocaoValue = promocaoCheckbox.checked ? 'true' : 'false';
                console.log('Promoção checkbox:', promocaoCheckbox.checked, '->', promocaoValue);
                formData.set('promocao', promocaoValue);
            }

            if (vendidoCheckbox) {
                const vendidoValue = vendidoCheckbox.checked ? 'true' : 'false';
                console.log('Vendido checkbox:', vendidoCheckbox.checked, '->', vendidoValue);
                formData.set('vendido', vendidoValue);
            }

            console.log('FORMDATA FINAL ANTES DO ENVIO');
            const formDataEntries = Array.from(formData.entries());
            console.log('Total de campos para envio:', formDataEntries.length);

            formDataEntries.forEach((pair, index) => {
                const [key, value] = pair;
                console.log(`${index + 1}. ${key}:`,
                    value instanceof File ? `FILE (${value.name})` :
                        value instanceof Blob ? `BLOB (${value.size} bytes)` :
                            value);
            });

            const hasImageFile = formDataEntries.some(([key, value]) =>
                key === 'imagem' && value instanceof File
            );
            console.log('Arquivo de imagem presente no FormData?', hasImageFile ? 'SIM' : 'NÃO');

            console.log('ENVIANDO PARA A API');
            console.log('URL:', this.API_BASE_URL + '?action=atualizar');
            console.log('Método: POST');
            console.log('Tipo: FormData (multipart/form-data)');

            const result = await this.fazerRequisicao('atualizar', 'POST', formData, true);

            if (result.success) {
                console.log('ATUALIZAÇÃO BEM-SUCEDIDA');
                console.log('Resposta:', result);
                this.mostrarMensagem('Produto atualizado com sucesso!', 'success');
                this.fecharModalEdicao();
                await this.carregarProdutos();
            } else {
                console.error('ERRO NA RESPOSTA DA API');
                console.error('Mensagem:', result.message);
                console.error('Dados:', result.data);
                throw new Error(result.message || 'Erro ao atualizar produto');
            }

        } catch (error) {
            console.error('ERRO NA ATUALIZAÇÃO');
            console.error('Erro completo:', error);
            console.error('Mensagem:', error.message);
            console.error('Stack:', error.stack);

            let mensagemUsuario = error.message;

            if (error.message.includes('Failed to fetch')) {
                mensagemUsuario = 'Erro de conexão com o servidor. Verifique sua internet.';
            } else if (error.message.includes('network')) {
                mensagemUsuario = 'Erro de rede. Verifique sua conexão.';
            } else if (error.message.includes('JSON')) {
                mensagemUsuario = 'Erro no servidor. A resposta não é válida.';
            } else if (error.message.includes('5MB')) {
                mensagemUsuario = 'A imagem é muito grande. O tamanho máximo é 5MB.';
            } else if (error.message.includes('não suportado')) {
                mensagemUsuario = 'Tipo de arquivo não suportado. Use JPG, PNG ou GIF.';
            }

            this.mostrarMensagem(`Erro: ${mensagemUsuario}`, 'error');

            window.scrollTo({ top: 0, behavior: 'smooth' });
        }
    }

    fecharModalEdicao() {
        const modalElement = document.getElementById('modalEditar');
        if (modalElement) {
            const modal = bootstrap.Modal.getInstance(modalElement);
            if (modal) {
                modal.hide();
            } else {
                const modalInstance = new bootstrap.Modal(modalElement);
                modalInstance.hide();
            }
        }

        setTimeout(() => {
            const backdrops = document.querySelectorAll('.modal-backdrop');
            backdrops.forEach(backdrop => backdrop.remove());
            document.body.classList.remove('modal-open');
            document.body.style.overflow = '';
            document.body.style.paddingRight = '';
        }, 300);
    }

    async excluirProduto(id) {
        if (!confirm('Tem certeza que deseja excluir este produto? Esta ação não pode ser desfeita.')) {
            return;
        }

        try {
            this.mostrarMensagem('Excluindo produto...', 'info');

            const formData = new FormData();
            formData.append('id', id);

            const result = await this.fazerRequisicao('excluir', 'POST', formData, true);

            this.mostrarMensagem('Produto excluído com sucesso!', 'success');
            await this.carregarProdutos();

        } catch (error) {
            console.error('Erro ao excluir produto:', error);
            this.mostrarMensagem(`Erro ao excluir: ${error.message}`, 'error');
        }
    }

    async marcarComoVendido(id) {
        try {
            console.log('Marcando produto ID:', id, 'como vendido...');
            
            const produto = this.produtos.find(p => p.id == id);
            if (!produto) {
                throw new Error('Produto não encontrado');
            }
            
            const novoStatus = !produto.vendido;
            
            const confirmMsg = novoStatus 
                ? `Marcar produto "${produto.nome}" como VENDIDO?` 
                : `Desmarcar produto "${produto.nome}" como vendido? (Tornar DISPONÍVEL)`;
            
            if (!confirm(confirmMsg)) {
                return;
            }
            
            this.mostrarMensagem('Atualizando status...', 'info');
            
            const formData = new FormData();
            formData.append('id', id);
            
            const result = await this.fazerRequisicao('marcar_vendido', 'POST', formData, true);
            
            if (result.success) {
                const mensagem = novoStatus 
                    ? 'Produto marcado como VENDIDO!' 
                    : 'Produto marcado como DISPONÍVEL novamente!';
                
                this.mostrarMensagem(mensagem, 'success');
                
                await this.carregarProdutos();
            } else {
                throw new Error(result.message || 'Erro ao atualizar status');
            }
            
        } catch (error) {
            console.error('Erro ao marcar como vendido:', error);
            this.mostrarMensagem(`Erro: ${error.message}`, 'error');
        }
    }

    renderizarFormCadastro() {
        const container = document.getElementById('form-cadastro-container');
        if (!container) return;

        container.innerHTML = `
            <form id="form-cadastro-produto" enctype="multipart/form-data">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Nome do Produto *</label>
                        <input type="text" class="form-control" name="nome" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Marca</label>
                        <input type="text" class="form-control" name="marca" placeholder="Ex: Nike, Adidas, etc.">
                    </div>
                    
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Preço (R$) *</label>
                        <input type="number" step="0.01" class="form-control" name="preco" min="0" required>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Tamanho</label>
                        <input type="text" class="form-control" name="tamanho" placeholder="Ex: P, M, G, 38, 40, 42, etc.">
                        <small class="form-text text-muted">Digite o tamanho conforme o produto</small>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Controle de Estoque</label>
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="checkbox" id="tem-estoque" name="tem_estoque" value="true">
                            <label class="form-check-label" for="tem-estoque">
                                <strong>Tem estoque (mais de 1 unidade)</strong>
                            </label>
                        </div>
                        <div id="quantidade-container" class="mt-2" style="display: none;">
                            <label class="form-label">Quantidade em estoque *</label>
                            <input type="number" class="form-control" id="estoque-input" name="estoque" min="2" value="2">
                            <small class="form-text text-muted">Digite a quantidade disponível</small>
                        </div>
                        <small class="text-muted d-block mt-1">Se não marcar, será considerado peça única (estoque = 1)</small>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Categoria *</label>
                        <select class="form-select" name="categoria" required>
                            <option value="">Selecione...</option>
                            ${this.categoriasFixa.map(c => `<option value="${c}">${c}</option>`).join('')}
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Estado *</label>
                        <select class="form-select" name="estado" required>
                            <option value="">Selecione...</option>
                            ${this.estados.map(e => `<option value="${e}">${e}</option>`).join('')}
                        </select>
                    </div>
                    
                    <div class="col-md-12 mb-3">
                        <label class="form-label">Imagem do Produto</label>
                        <input type="file" class="form-control" name="imagem" accept="image/*">
                        <small class="text-muted">Formatos: JPG, PNG, GIF. Máx: 5MB</small>
                    </div>
                    
                    <div class="col-md-12 mb-3">
                        <label class="form-label">Descrição</label>
                        <textarea class="form-control" name="descricao" rows="3" 
                                  placeholder="Descreva o produto detalhadamente..."></textarea>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="promocao" value="true">
                            <label class="form-check-label">Em Promoção</label>
                        </div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="vendido" value="true">
                            <label class="form-check-label">Marcar como Vendido</label>
                        </div>
                    </div>
                </div>
                
                <div class="d-flex justify-content-between">
                    <button type="submit" class="btn btn-success">
                        <i class="bi bi-plus-circle"></i> Cadastrar Produto
                    </button>
                    <button type="button" class="btn btn-secondary" onclick="this.form.reset(); document.getElementById('quantidade-container').style.display='none';">
                        <i class="bi bi-x-circle"></i> Limpar Formulário
                    </button>
                </div>
            </form>
        `;

        const temEstoqueCheckbox = document.getElementById('tem-estoque');
        const quantidadeContainer = document.getElementById('quantidade-container');
        const estoqueInput = document.getElementById('estoque-input');

        if (temEstoqueCheckbox && quantidadeContainer) {
            temEstoqueCheckbox.addEventListener('change', function () {
                if (this.checked) {
                    quantidadeContainer.style.display = 'block';
                    estoqueInput.required = true;
                } else {
                    quantidadeContainer.style.display = 'none';
                    estoqueInput.required = false;
                    estoqueInput.value = '';
                }
            });
        }
    }

    renderizarProdutos() {
        const container = document.getElementById('produtos-container');
        if (!container) return;

        if (this.produtos.length === 0) {
            container.innerHTML = `
                <div class="text-center py-5">
                    <i class="bi bi-box-seam display-1 text-muted mb-3"></i>
                    <p class="text-muted fs-5">Nenhum produto encontrado.</p>
                    <p class="text-muted">Cadastre seu primeiro produto usando o formulário acima.</p>
                </div>
            `;
            return;
        }

        const rows = this.produtos.map(produto => {
            const precoNumero = parseFloat(produto.preco) || 0;
            const imagemPath = produto.imagem
                ? `public/img/${produto.imagem}`
                : `public/img/default.jpg`;

            const estoqueTexto = produto.estoque === 1 ? 'Peça única' : `${produto.estoque} un.`;

            return `
                <tr>
                    <td>${produto.id}</td>
                    <td>
                        <img src="${imagemPath}"
                             alt="${produto.nome}"
                             style="width: 60px; height: 60px; object-fit: cover;"
                             class="img-thumbnail rounded">
                    </td>
                    <td>
                        <strong>${this.escapeHtml(produto.nome)}</strong><br>
                        <small class="text-muted">${produto.marca || 'Sem marca'}</small>
                    </td>
                    <td>${produto.categoria}</td>
                    <td>
                        <span class="badge ${produto.promocao ? 'bg-danger' : 'bg-secondary'}">
                            R$ ${precoNumero.toFixed(2).replace('.', ',')}
                        </span>
                    </td>
                    <td>
                        <span class="badge ${produto.estoque === 1 ? 'bg-warning text-dark' : 'bg-info'}">
                            ${estoqueTexto}
                        </span><br>
                        <small>${produto.tamanho || '-'}</small>
                    </td>
                    <td><span class="badge ${this.getEstadoBadgeClass(produto.estado)}">${produto.estado}</span></td>
                    <td>
                        ${produto.promocao ?
                    '<span class="badge bg-success">SIM</span>' :
                    '<span class="badge bg-secondary">NÃO</span>'}
                    </td>
                    <td>
                        ${produto.vendido ?
                    '<span class="badge bg-danger">VENDIDO</span><br>' +
                    '<small class="text-muted">' + (produto.dataVenda || '') + '</small>' :
                    '<span class="badge bg-success">DISPONÍVEL</span>'}
                    </td>
                    <td>
    <td>
    <div class="btn-group btn-group-sm">
        ${produto.vendido ? `
            <!-- Produto VENDIDO: Apenas botão excluir -->
            <button class="btn btn-danger excluir-btn" data-id="${produto.id}">
                <i class="bi bi-trash"></i> Excluir
            </button>
        ` : `
            <!-- Produto DISPONÍVEL: Todos os botões -->
            <button class="btn btn-warning editar-btn" data-id="${produto.id}">
                <i class="bi bi-pencil"></i> Editar
            </button>
            <button class="btn btn-success vender-btn" data-id="${produto.id}">
                <i class="bi bi-currency-dollar"></i> Vender
            </button>
            <button class="btn btn-danger excluir-btn" data-id="${produto.id}">
                <i class="bi bi-trash"></i> Excluir
            </button>
        `}
    </div>
</td>
                </tr>
            `;
        }).join('');

        container.innerHTML = `
            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle">
                    <thead class="table-dark">
                        <tr>
                            <th>ID</th>
                            <th>Imagem</th>
                            <th>Produto</th>
                            <th>Categoria</th>
                            <th>Preço</th>
                            <th>Estoque/Tamanho</th>
                            <th>Estado</th>
                            <th>Promoção</th>
                            <th>Status</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>${rows}</tbody>
                </table>
            </div>
            <div class="mt-3 d-flex justify-content-between align-items-center">
                <span class="badge bg-primary">
                    <i class="bi bi-box"></i> ${this.produtos.length} produtos
                </span>
                <small class="text-muted">Clique nos ícones para editar, vender ou excluir</small>
            </div>
        `;
    }

    renderizarFiltros() {
        const container = document.getElementById('filtros-container');
        if (!container) return;

        const categoriasUnicas = [...new Set(this.produtos.map(p => p.categoria).filter(Boolean))];

        container.innerHTML = `
        <div class="row g-3">
            <div class="col-md-3">
                <label class="form-label">Buscar</label>
                <input type="text" class="form-control" id="filtro-nome" placeholder="Nome ou marca">
            </div>
            <div class="col-md-2">
                <label class="form-label">Categoria</label>
                <select class="form-select" id="filtro-categoria">
                    <option value="">Todas</option>
                    ${categoriasUnicas.map(c => `<option value="${c}">${c}</option>`).join('')}
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Estado</label>
                <select class="form-select" id="filtro-estado">
                    <option value="">Todos</option>
                    ${this.estados.map(e => `<option value="${e}">${e}</option>`).join('')}
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Status Venda</label>
                <select class="form-select" id="filtro-vendido">
                    <option value="">Todos</option>
                    <option value="0">Disponíveis</option>
                    <option value="1">Vendidos</option>
                </select>
            </div>
            <div class="col-md-3 d-flex align-items-end">
                <div class="btn-group w-100">
                    <button class="btn btn-primary" id="btn-aplicar-filtros">
                        <i class="bi bi-funnel"></i> Filtrar
                    </button>
                    <button class="btn btn-outline-secondary" id="btn-limpar-filtros">
                        <i class="bi bi-x-circle"></i>
                    </button>
                </div>
            </div>
        </div>
    `;

        document.getElementById('btn-aplicar-filtros').addEventListener('click', () => this.aplicarFiltros());
        document.getElementById('btn-limpar-filtros').addEventListener('click', () => this.limparFiltros());
    }

    abrirModalEdicao(id) {
        const produto = this.produtos.find(p => p.id == id);
        if (!produto) return;

        this.produtoEditando = produto;

        const modal = new bootstrap.Modal(document.getElementById('modalEditar'));
        const modalContainer = document.getElementById('modal-editar-container');

        const temEstoque = produto.estoque > 1;
        const estoqueDisplay = temEstoque ? 'block' : 'none';

        modalContainer.innerHTML = `
            <form id="form-editar-produto" enctype="multipart/form-data">
                <input type="hidden" name="id" value="${produto.id}">
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Nome do Produto *</label>
                        <input type="text" class="form-control" name="nome" value="${this.escapeHtml(produto.nome)}" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Marca</label>
                        <input type="text" class="form-control" name="marca" value="${this.escapeHtml(produto.marca || '')}">
                    </div>
                    
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Preço (R$) *</label>
                        <input type="number" step="0.01" class="form-control" name="preco" value="${produto.preco}" required>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Tamanho</label>
                        <input type="text" class="form-control" name="tamanho" 
                               value="${this.escapeHtml(produto.tamanho || '')}" 
                               placeholder="Ex: P, M, G, 38, 40, 42, etc.">
                        <small class="form-text text-muted">Digite o tamanho conforme o produto</small>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Controle de Estoque</label>
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="checkbox" id="tem-estoque-editar" 
                                   name="tem_estoque" value="true" ${temEstoque ? 'checked' : ''}>
                            <label class="form-check-label" for="tem-estoque-editar">
                                <strong>Tem estoque (mais de 1 unidade)</strong>
                            </label>
                        </div>
                        <div id="quantidade-container-editar" class="mt-2" style="display: ${estoqueDisplay};">
                            <label class="form-label">Quantidade em estoque *</label>
                            <input type="number" class="form-control" id="estoque-input-editar" 
                                   name="estoque" min="2" value="${produto.estoque > 1 ? produto.estoque : 2}">
                            <small class="form-text text-muted">Digite a quantidade disponível</small>
                        </div>
                        <small class="text-muted d-block mt-1">Se não marcar, será considerado peça única (estoque = 1)</small>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Categoria *</label>
                        <select class="form-select" name="categoria" required>
                            <option value="">Selecione...</option>
                            ${this.categoriasFixa.map(c =>
            `<option value="${c}" ${produto.categoria === c ? 'selected' : ''}>${c}</option>`
        ).join('')}
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Estado *</label>
                        <select class="form-select" name="estado" required>
                            <option value="">Selecione...</option>
                            ${this.estados.map(e =>
            `<option value="${e}" ${produto.estado === e ? 'selected' : ''}>${e}</option>`
        ).join('')}
                        </select>
                    </div>
                    
                    <div class="col-md-12 mb-3">
                        <label class="form-label">Imagem do Produto</label>
                        
                        <div id="preview-container" class="mb-3"></div>
                        
                        <div class="mb-3">
                            <p class="text-muted mb-1">Imagem atual:</p>
                            ${produto.imagem && produto.imagem !== 'default.jpg' ?
                `<div class="border rounded p-2 mb-2">
                                    <img src="../../public/img/${produto.imagem}" 
                                          alt="${produto.nome}" 
                                          class="img-thumbnail rounded mb-1" 
                                          style="max-width: 200px; max-height: 200px;"
                                          id="imagem-atual">
                                    <p class="text-muted small mb-0">${produto.imagem}</p>
                                 </div>` :
                `<div class="alert alert-warning py-2 mb-2">
                                    <i class="bi bi-exclamation-triangle"></i> Sem imagem cadastrada
                                 </div>`}
                        </div>
                        
                        <div>
                            <label class="form-label">Alterar imagem (opcional)</label>
                            <input type="file" class="form-control" name="imagem" id="nova-imagem" accept="image/*">
                            <div class="form-text">
                                Deixe em branco para manter a imagem atual. Formatos: JPG, PNG, GIF. Máx: 5MB
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-12 mb-3">
                        <label class="form-label">Descrição</label>
                        <textarea class="form-control" name="descricao" rows="3">${this.escapeHtml(produto.descricao || '')}</textarea>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="promocao" value="true" 
                                   ${produto.promocao ? 'checked' : ''}>
                            <label class="form-check-label">Em Promoção</label>
                        </div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="vendido" value="true"
                                   ${produto.vendido ? 'checked' : ''}>
                            <label class="form-check-label">Marcar como Vendido</label>
                        </div>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-circle"></i> Cancelar
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-circle"></i> Salvar Alterações
                    </button>
                </div>
            </form>
        `;

        const temEstoqueCheckbox = document.getElementById('tem-estoque-editar');
        const quantidadeContainer = document.getElementById('quantidade-container-editar');
        const estoqueInput = document.getElementById('estoque-input-editar');

        if (temEstoqueCheckbox && quantidadeContainer) {
            temEstoqueCheckbox.addEventListener('change', function () {
                if (this.checked) {
                    quantidadeContainer.style.display = 'block';
                    estoqueInput.required = true;
                    estoqueInput.value = estoqueInput.value || '2';
                } else {
                    quantidadeContainer.style.display = 'none';
                    estoqueInput.required = false;
                    estoqueInput.value = '';
                }
            });
        }

        const form = document.getElementById('form-editar-produto');
        form.addEventListener('submit', (e) => {
            e.preventDefault();
            this.atualizarProduto(produto.id, new FormData(form));
        });

        const fileInput = document.getElementById('nova-imagem');
        const previewContainer = document.getElementById('preview-container');

        fileInput.addEventListener('change', function (e) {
            const file = e.target.files[0];

            if (file) {
                previewContainer.innerHTML = '';

                if (!file.type.match('image.*')) {
                    previewContainer.innerHTML = `
                        <div class="alert alert-danger">
                            <i class="bi bi-exclamation-circle"></i> 
                            Por favor, selecione um arquivo de imagem (JPG, PNG, GIF)
                        </div>
                    `;
                    fileInput.value = '';
                    return;
                }

                if (file.size > 5 * 1024 * 1024) {
                    previewContainer.innerHTML = `
                        <div class="alert alert-danger">
                            <i class="bi bi-exclamation-circle"></i> 
                            A imagem é muito grande (máximo 5MB)
                        </div>
                    `;
                    fileInput.value = '';
                    return;
                }

                const reader = new FileReader();

                reader.onload = function (e) {
                    previewContainer.innerHTML = `
                        <div class="border rounded p-2">
                            <p class="text-muted mb-1">Nova imagem selecionada:</p>
                            <img src="${e.target.result}" 
                                 class="img-thumbnail rounded mb-1" 
                                 style="max-width: 200px; max-height: 200px;">
                            <p class="text-muted small mb-0">${file.name} (${Math.round(file.size / 1024)} KB)</p>
                        </div>
                    `;
                };

                reader.readAsDataURL(file);
            } else {
                previewContainer.innerHTML = '';
            }
        });

        modal.show();
    }

    aplicarFiltros() {
        const nome = document.getElementById('filtro-nome').value.toLowerCase();
        const categoria = document.getElementById('filtro-categoria').value;
        const estado = document.getElementById('filtro-estado').value;
        const vendido = document.getElementById('filtro-vendido').value;

        let produtosFiltrados = [...this.produtos];

        if (nome) {
            produtosFiltrados = produtosFiltrados.filter(p =>
                p.nome.toLowerCase().includes(nome) ||
                (p.marca && p.marca.toLowerCase().includes(nome))
            );
        }

        if (categoria) {
            produtosFiltrados = produtosFiltrados.filter(p => p.categoria === categoria);
        }

        if (estado) {
            produtosFiltrados = produtosFiltrados.filter(p => p.estado === estado);
        }

        if (vendido === '0') {
            produtosFiltrados = produtosFiltrados.filter(p => !p.vendido);
        } else if (vendido === '1') {
            produtosFiltrados = produtosFiltrados.filter(p => p.vendido);
        }

        this.renderizarProdutosFiltrados(produtosFiltrados);
    }

    renderizarProdutosFiltrados(produtosFiltrados) {
        const container = document.getElementById('produtos-container');
        if (!container) return;

        if (produtosFiltrados.length === 0) {
            container.innerHTML = `
                <div class="text-center py-5">
                    <i class="bi bi-search display-1 text-muted mb-3"></i>
                    <p class="text-muted fs-5">Nenhum produto encontrado com os filtros atuais.</p>
                </div>
            `;
            return;
        }

        const rows = produtosFiltrados.map(produto => {
            const precoNumero = parseFloat(produto.preco) || 0;
            const imagemPath = produto.imagem
                ? `../../public/img/${produto.imagem}`
                : `../../public/img/default.jpg`;

            const estoqueTexto = produto.estoque === 1 ? 'Peça única' : `${produto.estoque} un.`;

            return `
                <tr>
                    <td>${produto.id}</td>
                    <td><img src="${imagemPath}" style="width: 60px; height: 60px;" class="img-thumbnail"></td>
                    <td><strong>${produto.nome}</strong><br><small>${produto.marca || ''}</small></td>
                    <td>${produto.categoria}</td>
                    <td><span class="badge ${produto.promocao ? 'bg-danger' : 'bg-secondary'}">R$ ${precoNumero.toFixed(2).replace('.', ',')}</span></td>
                    <td>
                        <span class="badge ${produto.estoque === 1 ? 'bg-warning text-dark' : 'bg-info'}">
                            ${estoqueTexto}
                        </span><br>
                        <small>${produto.tamanho || '-'}</small>
                    </td>
                    <td><span class="badge ${this.getEstadoBadgeClass(produto.estado)}">${produto.estado}</span></td>
                    <td>${produto.promocao ? '<span class="badge bg-success">SIM</span>' : '<span class="badge bg-secondary">NÃO</span>'}</td>
                    <td>
                        ${produto.vendido ?
        '<span class="badge bg-danger">VENDIDO</span><br>' +
        '<small class="text-muted">' + (produto.dataVenda || '') + '</small>' :
        '<span class="badge bg-success">DISPONÍVEL</span>'}
                    </td>
           <td>
    <div class="btn-group btn-group-sm">
        ${produto.vendido ? `
            <!-- Produto VENDIDO: Apenas botão excluir -->
            <button class="btn btn-danger excluir-btn" data-id="${produto.id}">
                <i class="bi bi-trash"></i> Excluir
            </button>
        ` : `
            <!-- Produto DISPONÍVEL: Todos os botões -->
            <button class="btn btn-warning editar-btn" data-id="${produto.id}">
                <i class="bi bi-pencil"></i> Editar
            </button>
            <button class="btn btn-success vender-btn" data-id="${produto.id}">
                <i class="bi bi-currency-dollar"></i> Vender
            </button>
            <button class="btn btn-danger excluir-btn" data-id="${produto.id}">
                <i class="bi bi-trash"></i> Excluir
            </button>
        `}
    </div>
</td>
                </tr>
            `;
        }).join('');

        container.innerHTML = `
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead class="table-dark">
                        <tr>
                            <th>ID</th><th>Imagem</th><th>Produto</th><th>Categoria</th>
                            <th>Preço</th><th>Estoque/Tamanho</th><th>Estado</th>
                            <th>Promoção</th><th>Status</th><th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>${rows}</tbody>
                </table>
            </div>
            <div class="mt-3">
                <span class="badge bg-info">
                    <i class="bi bi-funnel"></i> Filtrados: ${produtosFiltrados.length} de ${this.produtos.length} produtos
                </span>
            </div>
        `;
    }

    limparFiltros() {
        document.getElementById('filtro-nome').value = '';
        document.getElementById('filtro-categoria').value = '';
        document.getElementById('filtro-estado').value = '';
        document.getElementById('filtro-status').value = '';
        this.renderizarProdutos();
    }

    limparFormularioCadastro() {
        const form = document.getElementById('form-cadastro-produto');
        if (form) {
            form.reset();
            const quantidadeContainer = document.getElementById('quantidade-container');
            if (quantidadeContainer) {
                quantidadeContainer.style.display = 'none';
            }
        }
    }

    getEstadoBadgeClass(estado) {
        const classes = {
            'Novo': 'bg-success',
            'Semi-novo': 'bg-warning text-dark',
            'Usado': 'bg-info'
        };
        return classes[estado] || 'bg-secondary';
    }

    escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    mostrarLoadingProdutos() {
        const container = document.getElementById('produtos-container');
        if (!container) return;

        container.innerHTML = `
            <div class="text-center py-5">
                <div class="spinner-border text-primary mb-3" role="status">
                    <span class="visually-hidden">Carregando...</span>
                </div>
                <p class="text-muted">Carregando produtos...</p>
            </div>
        `;
    }

    mostrarMensagem(mensagem, tipo = 'info') {
        const container = document.getElementById('mensagem-container');
        if (!container) return;

        const alertClass = {
            'success': 'alert-success',
            'error': 'alert-danger',
            'warning': 'alert-warning',
            'info': 'alert-info'
        }[tipo] || 'alert-info';

        const icon = {
            'success': 'bi-check-circle-fill',
            'error': 'bi-exclamation-circle-fill',
            'warning': 'bi-exclamation-triangle-fill',
            'info': 'bi-info-circle-fill'
        }[tipo] || 'bi-info-circle-fill';

        container.innerHTML = `
            <div class="alert ${alertClass} alert-dismissible fade show d-flex align-items-center" role="alert">
                <i class="bi ${icon} me-2"></i>
                <div>${mensagem}</div>
                <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
            </div>
        `;

        setTimeout(() => {
            if (container.innerHTML.includes('alert')) {
                container.innerHTML = '';
            }
        }, 5000);
    }

    configurarEventos() {
        const formEditar = document.getElementById('form-editar-produto');
        if (formEditar) {
            console.log('Formulário de edição encontrado');

            formEditar.addEventListener('submit', (e) => {
                e.preventDefault();
                console.log('SUBMIT do formulário de edição capturado!');

                console.log('Campos do formulário:');
                const formData = new FormData(formEditar);
                for (let pair of formData.entries()) {
                    console.log(pair[0] + ': ', pair[1]);
                }

                this.atualizarProduto();
            });
        } else {
            console.log('Formulário de edição NÃO encontrado!');
        }
        
        const formCadastro = document.getElementById('form-cadastro-produto');
        if (formCadastro) {
            formCadastro.addEventListener('submit', (e) => {
                e.preventDefault();

                console.log('Enviando formulário de cadastro...');

                const formData = new FormData(formCadastro);

                console.log('Dados do FormData:');
                for (let pair of formData.entries()) {
                    console.log(pair[0] + ': ', pair[1]);
                }

                const imagemFile = formData.get('imagem');
                console.log('Arquivo de imagem:', imagemFile);
                console.log('Tamanho do arquivo:', imagemFile ? imagemFile.size : 0);

                const temEstoqueCheckbox = document.getElementById('tem-estoque');
                const temEstoque = temEstoqueCheckbox ? temEstoqueCheckbox.checked : false;

                if (!temEstoque) {
                    formData.set('estoque', '1');
                }

                formData.set('tem_estoque', temEstoque ? 'true' : 'false');

                const promocaoCheckbox = formCadastro.querySelector('input[name="promocao"]');
                const vendidoCheckbox = formCadastro.querySelector('input[name="vendido"]');

                if (promocaoCheckbox) {
                    formData.set('promocao', promocaoCheckbox.checked ? 'true' : 'false');
                }

                if (vendidoCheckbox) {
                    formData.set('vendido', vendidoCheckbox.checked ? 'true' : 'false');
                }

                console.log('Dados FINAIS do FormData:');
                for (let pair of formData.entries()) {
                    console.log(pair[0] + ': ', pair[1]);
                }

                this.cadastrarProduto(formData);
            });
        }

        document.addEventListener('click', (e) => {
            const target = e.target;

            if (target.closest('.editar-btn')) {
                const btn = target.closest('.editar-btn');
                this.abrirModalEdicao(btn.dataset.id);
            }

            if (target.closest('.vender-btn')) {
                const btn = target.closest('.vender-btn');
                this.marcarComoVendido(btn.dataset.id);
            }

            if (target.closest('.excluir-btn')) {
                const btn = target.closest('.excluir-btn');
                this.excluirProduto(btn.dataset.id);
            }
        });
    }
}

document.addEventListener('DOMContentLoaded', function () {
    console.log('DOM carregado');

    if (window.location.pathname.includes('admin')) {
        setTimeout(() => {
            try {
                window.adminApp = new AdminApp();
            } catch (error) {
                console.error('Erro ao inicializar AdminApp:', error);
                alert('Erro ao carregar sistema admin: ' + error.message);
            }
        }, 100);
    }
});