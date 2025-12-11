document.addEventListener('DOMContentLoaded', function () {
    if (window.innerWidth < 992) {
        document.querySelectorAll('.dropdown-submenu a.dropdown-toggle').forEach(function (element) {
            element.addEventListener('click', function (e) {
                e.preventDefault();
                e.stopPropagation();
                
                let parentMenu = this.closest('.dropdown-menu');
                parentMenu.querySelectorAll('.dropdown-submenu .dropdown-menu.show').forEach(function(openSubmenu) {
                    if (openSubmenu !== this.nextElementSibling) {
                        openSubmenu.classList.remove('show');
                    }
                });
                this.nextElementSibling.classList.toggle('show');
            });
        });
    }
});


// ======================================================
//  LÓGICA PARA NAVEGAÇÃO ATIVA NA PÁGINA FAQ
// ======================================================
document.addEventListener('DOMContentLoaded', () => {
    const faqNav = document.querySelector('.faq-nav');
    if (faqNav) {
        const navLinks = document.querySelectorAll('.faq-nav a');
        const sections = document.querySelectorAll('.faq-item');

        const observerOptions = {
            root: null,
            rootMargin: '0px',
            threshold: 0.4 
        };

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    navLinks.forEach(link => {
                        link.classList.remove('active');
                    });

                    const activeLink = document.querySelector(`.faq-nav a[href="#${entry.target.id}"]`);
                    if (activeLink) {
                        activeLink.classList.add('active');
                    }
                }
            });
        }, observerOptions);

        sections.forEach(section => {
            observer.observe(section);
        });
    }
});

document.addEventListener('DOMContentLoaded', function() {
    const urlParams = new URLSearchParams(window.location.search);
    const cadastroSucesso = urlParams.get('cadastro_sucesso');
    const loginSucesso = urlParams.get('login_sucesso');
    const erro = urlParams.get('erro');
    const novaURL = window.location.pathname;
    window.history.replaceState({}, document.title, novaURL);
    
    if (typeof bootstrap === 'undefined') {
        console.log('Bootstrap não carregado');
        return;
    }

    if (cadastroSucesso === 'true' && window.location.pathname.includes('cadastre')) {
        const modalHTML = `
            <div class="modal fade" id="cadastroSucessoModal" tabindex="-1">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title text-success">
                                <i class="bi bi-check-circle-fill me-2"></i>Cadastro realizado com sucesso!
                            </h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <p>Cadastro realizado com sucesso! Faça seu log-in!</p>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                            <a href="log.php" class="btn btn-primary">Fazer Login</a>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        document.body.insertAdjacentHTML('beforeend', modalHTML);
        const modalElement = document.getElementById('cadastroSucessoModal');
        if (modalElement) {
            const modal = new bootstrap.Modal(modalElement);
            modal.show();
        }
    }
    else if (loginSucesso === 'true' && window.location.pathname.includes('log')) {
        const modalHTML = `
            <div class="modal fade" id="loginSucessoModal" tabindex="-1">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title text-success">
                                <i class="bi bi-check-circle-fill me-2"></i>Login realizado com sucesso!
                            </h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <p>Bem-vindo de volta! Redirecionando para sua conta...</p>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        document.body.insertAdjacentHTML('beforeend', modalHTML);
        const modalElement = document.getElementById('loginSucessoModal');
        if (modalElement) {
            const modal = new bootstrap.Modal(modalElement);
            modal.show();
            setTimeout(() => {
                window.location.href = 'minha_conta.php';
            }, 2000);
        }
    }
    else if (erro) {
        if (window.location.pathname.includes('cadastre')) {
            let titulo, texto, icone, btnLogin;
            
            switch(erro) {
                case 'email_ja_cadastrado':
                    titulo = '<i class="bi bi-exclamation-triangle-fill me-2 text-warning"></i>Email já cadastrado!';
                    texto = 'Este email já está em uso. Deseja fazer login?';
                    icone = 'warning';
                    btnLogin = true;
                    break;
                case 'campos_vazios':
                    titulo = '<i class="bi bi-exclamation-circle-fill me-2 text-warning"></i>Campos obrigatórios!';
                    texto = 'Todos os campos devem ser preenchidos.';
                    icone = 'warning';
                    btnLogin = false;
                    break;
                case 'erro_banco_dados':
                    titulo = '<i class="bi bi-x-circle-fill me-2 text-danger"></i>Erro no cadastro!';
                    texto = 'Ocorreu um erro durante o cadastro. Tente novamente.';
                    icone = 'error';
                    btnLogin = false;
                    break;
                default:
                    return;
            }
            
            const modalHTML = `
                <div class="modal fade" id="erroModal" tabindex="-1">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title ${icone === 'error' ? 'text-danger' : 'text-warning'}">
                                    ${titulo}
                                </h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <p>${texto}</p>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                                ${btnLogin ? '<a href="log.php" class="btn btn-primary">Fazer Login</a>' : ''}
                            </div>
                        </div>
                    </div>
                </div>
            `;
            
            document.body.insertAdjacentHTML('beforeend', modalHTML);
            const modalElement = document.getElementById('erroModal');
            if (modalElement) {
                const modal = new bootstrap.Modal(modalElement);
                modal.show();
            }
        }
        
        // ERROS DE LOGIN
        else if (window.location.pathname.includes('log')) {
            let titulo, texto, icone, btnCadastro;
            
            switch(erro) {
                case 'email_nao_encontrado':
                    titulo = '<i class="bi bi-person-x-fill me-2 text-warning"></i>Email não encontrado!';
                    texto = 'Este email não está cadastrado. Deseja criar uma conta?';
                    icone = 'warning';
                    btnCadastro = true;
                    break;
                case 'senha_invalida':
                    titulo = '<i class="bi bi-shield-exclamation me-2 text-warning"></i>Senha incorreta!';
                    texto = 'A senha informada está incorreta. Tente novamente.';
                    icone = 'warning';
                    btnCadastro = false;
                    break;
                case 'campos_vazios':
                    titulo = '<i class="bi bi-exclamation-circle-fill me-2 text-warning"></i>Campos obrigatórios!';
                    texto = 'Todos os campos devem ser preenchidos.';
                    icone = 'warning';
                    btnCadastro = false;
                    break;
                default:
                    return;
            }
            
            const modalHTML = `
                <div class="modal fade" id="loginErroModal" tabindex="-1">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title ${icone === 'error' ? 'text-danger' : 'text-warning'}">
                                    ${titulo}
                                </h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <p>${texto}</p>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                                ${btnCadastro ? '<a href="cadastre.php" class="btn btn-primary">Criar Conta</a>' : ''}
                            </div>
                        </div>
                    </div>
                </div>
            `;
            
            document.body.insertAdjacentHTML('beforeend', modalHTML);
            const modalElement = document.getElementById('loginErroModal');
            if (modalElement) {
                const modal = new bootstrap.Modal(modalElement);
                modal.show();
            }
        }
    }
});

// Interatividade para as páginas de cadastro e login
document.addEventListener('DOMContentLoaded', function() {
    const formInputs = document.querySelectorAll('.php-email-form .form-control');
    
    formInputs.forEach(input => {
        input.addEventListener('focus', function() {
            this.parentElement.classList.add('focused');
        });
        input.addEventListener('blur', function() {
            if (this.value === '') {
                this.parentElement.classList.remove('focused');
            }
        });
        if (input.value !== '') {
            input.parentElement.classList.add('focused');
        }
    });
    const cadastroForm = document.querySelector('.php-email-form');
    if (cadastroForm) {
        cadastroForm.addEventListener('submit', function() {
            const submitBtn = this.querySelector('#butao');
            if (submitBtn) {
                submitBtn.classList.add('loading');
            }
        });
    }
});

// botão mostrar senha
$(document).ready(function() {
    if ($('#toggleSenha').length) {
        const $toggleBtn = $('#toggleSenha');
        const $senhaInput = $('#senha');
        const $icon = $toggleBtn.find('i');

        $toggleBtn.attr({
            'type': 'button',
            'aria-label': 'Mostrar senha',
            'aria-pressed': 'false'
        });
        
        $toggleBtn.click(function() {
            if($senhaInput.attr('type') === 'password') {
                $senhaInput.attr('type', 'text');
                $icon.removeClass('fa-eye').addClass('fa-eye-slash');
                $toggleBtn.attr({
                    'aria-label': 'Ocultar senha',
                    'aria-pressed': 'true'
                });
                $(this).addClass('active');
            } else {
                $senhaInput.attr('type', 'password');
                $icon.removeClass('fa-eye-slash').addClass('fa-eye');
                $toggleBtn.attr({
                    'aria-label': 'Mostrar senha',
                    'aria-pressed': 'false'
                });
                $(this).removeClass('active');
            }
            $senhaInput.focus();
        });
    }
});