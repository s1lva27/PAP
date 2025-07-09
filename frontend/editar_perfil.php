<?php
session_start();
require "../backend/ligabd.php";

// Verificar se o utilizador está autenticado
if (!isset($_SESSION["id"])) {
    header("Location: login.php");
    exit();
}

$userId = $_SESSION["id"];

// Buscar informações do utilizador na tabela "utilizadores"
$sqlUser = "SELECT * FROM utilizadores WHERE id = $userId";
$resultUser = mysqli_query($con, $sqlUser);
$userData = mysqli_fetch_assoc($resultUser);

$_SESSION = $userData;

// Buscar informações do perfil na tabela "perfis"
$sqlPerfil = "SELECT * FROM perfis WHERE id_utilizador = $userId";
$resultPerfil = mysqli_query($con, $sqlPerfil);
$perfilData = mysqli_fetch_assoc($resultPerfil);
?>

<!DOCTYPE html>
<html lang="pt">

<head>
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configurações de Perfil - Orange</title>
    <link rel="stylesheet" href="css/app.css">
    <link rel="stylesheet" href="css/style_index.css">
    <link rel="stylesheet" href="css/style_editar_perfil.css">
    <link rel="icon" type="image/x-icon" href="images/favicon/favicon_orange.png">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://unpkg.com/lucide@latest"></script>
</head>

<body>
    <?php require "parciais/header.php"; ?>

    <div class="container">
        <?php require("parciais/sidebar.php"); ?>

        <!-- Conteúdo Principal -->
        <main class="profile-container">
            <!-- Header da página -->
            <div class="profile-page-header">
                <div class="profile-page-header-content">
                    <div class="profile-page-title">
                        <i class="fas fa-user-cog"></i>
                        <h1>Configurações de Perfil</h1>
                    </div>
                    <p class="profile-page-subtitle">Gerencie as suas informações pessoais e preferências</p>
                </div>
            </div>

            <div class="profile-edit-layout">
                <!-- Sidebar de navegação -->
                <aside class="profile-nav-sidebar">
                    <div class="profile-picture-section">
                        <?php
                        $fotoPerfil = !empty($perfilData['foto_perfil']) ? "images/perfil/" . $perfilData['foto_perfil'] : "images/perfil/default-profile.jpg";
                        ?>

                        <form action="../backend/upload_foto.php" method="POST" enctype="multipart/form-data" class="profile-photo-form">
                            <div class="profile-photo-container">
                                <label for="fotoInput" class="profile-photo-label">
                                    <img id="profile-img" src="<?php echo $fotoPerfil; ?>" alt="Foto do Perfil" class="profile-photo">
                                    <div class="photo-overlay">
                                        <i class="fas fa-camera"></i>
                                        <span>Alterar foto</span>
                                    </div>
                                </label>
                                <input type="file" name="foto" id="fotoInput" accept="image/*" style="display: none;" required>
                                <button type="submit" name="submit" id="uploadForm" style="display: none;"></button>
                            </div>
                        </form>

                        <div class="profile-basic-info">
                            <h3><?php echo htmlspecialchars($userData['nome_completo']); ?></h3>
                            <p>@<?php echo htmlspecialchars($userData['nick']); ?></p>
                        </div>
                    </div>

                    <nav class="profile-navigation">
                        <a href="#profile-info" id="nav-1" onclick="updateNav('1')" class="nav-item active">
                            <i class="fas fa-user"></i>
                            <span>Informações Básicas</span>
                        </a>
                        <a href="#professional-info" id="nav-2" onclick="updateNav('2')" class="nav-item">
                            <i class="fas fa-briefcase"></i>
                            <span>Informações Profissionais</span>
                        </a>
                        <a href="#social-info" id="nav-3" onclick="updateNav('3')" class="nav-item">
                            <i class="fas fa-share-alt"></i>
                            <span>Redes Sociais</span>
                        </a>
                        <a href="#security-info" id="nav-4" onclick="updateNav('4')" class="nav-item">
                            <i class="fas fa-shield-alt"></i>
                            <span>Segurança</span>
                        </a>
                    </nav>
                </aside>

                <!-- Conteúdo das seções -->
                <section class="profile-content">
                    <!-- Informações Básicas -->
                    <div id="profile-info" class="content-section active">
                        <div class="section-header">
                            <h2><i class="fas fa-user"></i> Informações Básicas</h2>
                            <p>Atualize as suas informações pessoais</p>
                        </div>

                        <form action="../backend/editar_perfil/informacoes.php" method="POST" class="profile-form">
                            <input type="hidden" name="id" value="<?php echo $perfilData['id_utilizador']; ?>">

                            <div class="form-grid">
                                <div class="form-group">
                                    <label for="nick">
                                        <i class="fas fa-at"></i>
                                        Nome de Utilizador
                                    </label>
                                    <input type="text" name="nick" id="nick" value="<?php echo htmlspecialchars($userData['nick']); ?>" required>
                                    <small class="form-help">Este será o seu identificador único na plataforma</small>
                                </div>

                                <div class="form-group">
                                    <label for="email">
                                        <i class="fas fa-envelope"></i>
                                        Email
                                    </label>
                                    <input type="email" name="email" id="email" value="<?php echo htmlspecialchars($userData['email']); ?>" required>
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="biografia">
                                    <i class="fas fa-quote-left"></i>
                                    Biografia
                                </label>
                                <textarea name="biografia" id="biografia" rows="4" placeholder="Conte-nos algo sobre si..." maxlength="255"><?php echo htmlspecialchars($perfilData['biografia']); ?></textarea>
                                <small class="form-help">Máximo 255 caracteres</small>
                            </div>

                            <div class="form-grid">
                                <div class="form-group">
                                    <label for="data">
                                        <i class="fas fa-calendar"></i>
                                        Data de Nascimento
                                    </label>
                                    <input type="date" name="data" id="data" value="<?php echo $userData['data_nascimento']; ?>" required>
                                </div>

                                <div class="form-group">
                                    <label for="pais">
                                        <i class="fas fa-globe"></i>
                                        País
                                    </label>
                                    <select name="país" id="pais" class="location-select">
                                        <option value="">Selecione um país</option>
                                    </select>
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="cidade">
                                    <i class="fas fa-map-marker-alt"></i>
                                    Cidade
                                </label>
                                <select name="cidade" id="cidade" class="location-select">
                                    <option value="">Selecione uma cidade</option>
                                </select>
                            </div>

                            <div class="form-actions">
                                <button type="submit" class="btn-primary">
                                    <i class="fas fa-save"></i>
                                    Salvar Alterações
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Informações Profissionais -->
                    <div id="professional-info" class="content-section">
                        <div class="section-header">
                            <h2><i class="fas fa-briefcase"></i> Informações Profissionais</h2>
                            <p>Adicione informações sobre a sua carreira</p>
                        </div>

                        <form action="../backend/editar_perfil/profissional.php" method="POST" class="profile-form">
                            <input type="hidden" name="id" value="<?php echo $perfilData['id_utilizador']; ?>">

                            <div class="form-group">
                                <label for="ocupacao">
                                    <i class="fas fa-user-tie"></i>
                                    Ocupação
                                </label>
                                <input type="text" name="ocupacao" id="ocupacao" value="<?php echo htmlspecialchars($perfilData['ocupacao']); ?>" placeholder="Ex: Desenvolvedor, Estudante, Designer...">
                                <small class="form-help">Descreva a sua ocupação atual</small>
                            </div>

                            <div class="form-actions">
                                <button type="submit" class="btn-primary">
                                    <i class="fas fa-save"></i>
                                    Salvar Alterações
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Redes Sociais -->
                    <div id="social-info" class="content-section">
                        <div class="section-header">
                            <h2><i class="fas fa-share-alt"></i> Redes Sociais</h2>
                            <p>Conecte as suas redes sociais ao perfil</p>
                        </div>

                        <form action="../backend/editar_perfil/redes_sociais.php" method="POST" class="profile-form">
                            <input type="hidden" name="id" value="<?php echo $perfilData['id_utilizador']; ?>">

                            <div class="form-group">
                                <label for="x">
                                    <i class="fab fa-x-twitter"></i>
                                    X (Twitter)
                                </label>
                                <input type="url" name="x" id="x" value="<?php echo htmlspecialchars($perfilData['x']); ?>" placeholder="https://x.com/seuusuario">
                            </div>

                            <div class="form-group">
                                <label for="linkedin">
                                    <i class="fab fa-linkedin"></i>
                                    LinkedIn
                                </label>
                                <input type="url" name="linkedin" id="linkedin" value="<?php echo htmlspecialchars($perfilData['linkedin']); ?>" placeholder="https://linkedin.com/in/seuusuario">
                            </div>

                            <div class="form-group">
                                <label for="github">
                                    <i class="fab fa-github"></i>
                                    GitHub
                                </label>
                                <input type="url" name="github" id="github" value="<?php echo htmlspecialchars($perfilData['github']); ?>" placeholder="https://github.com/seuusuario">
                            </div>

                            <div class="form-actions">
                                <button type="submit" class="btn-primary">
                                    <i class="fas fa-save"></i>
                                    Salvar Alterações
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Segurança -->
                    <div id="security-info" class="content-section">
                        <div class="section-header">
                            <h2><i class="fas fa-shield-alt"></i> Segurança</h2>
                            <p>Altere a sua palavra-passe para manter a conta segura</p>
                        </div>

                        <form action="../backend/editar_perfil/seguranca.php" method="POST" class="profile-form">
                            <input type="hidden" name="id" value="<?php echo $userData['id']; ?>">

                            <div class="security-warning">
                                <i class="fas fa-exclamation-triangle"></i>
                                <div>
                                    <strong>Importante:</strong>
                                    <p>Certifique-se de usar uma palavra-passe forte com pelo menos 8 caracteres.</p>
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="pass_atual">
                                    <i class="fas fa-lock"></i>
                                    Palavra-passe Atual
                                </label>
                                <input type="password" name="pass_atual" id="pass_atual" placeholder="Digite a sua palavra-passe atual" required>
                            </div>

                            <div class="form-group">
                                <label for="pass_nova">
                                    <i class="fas fa-key"></i>
                                    Nova Palavra-passe
                                </label>
                                <input type="password" name="pass_nova" id="pass_nova" placeholder="Digite a nova palavra-passe" required>
                                <small class="form-help">Mínimo 6 caracteres</small>
                            </div>

                            <div class="form-actions">
                                <button type="submit" class="btn-primary">
                                    <i class="fas fa-shield-alt"></i>
                                    Alterar Palavra-passe
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Mensagem de feedback -->
                    <?php if (isset($_SESSION["erro"]) || isset($_SESSION["sucesso"])): ?>
                        <div id="feedback-message" class="feedback-message <?php echo isset($_SESSION['sucesso']) ? 'success' : 'error'; ?>">
                            <div class="feedback-content">
                                <i class="fas <?php echo isset($_SESSION['sucesso']) ? 'fa-check-circle' : 'fa-exclamation-triangle'; ?>"></i>
                                <span><?php echo isset($_SESSION['sucesso']) ? $_SESSION['sucesso'] : $_SESSION['erro']; ?></span>
                            </div>
                            <button type="button" class="feedback-close" onclick="closeFeedback()">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                        <?php 
                        unset($_SESSION["erro"]);
                        unset($_SESSION["sucesso"]);
                        ?>
                    <?php endif; ?>
                </section>
            </div>
        </main>
    </div>

    <script src="js/localizacao.js"></script>
    <script>
        // Navegação entre seções
        function updateNav(activeId) {
            // Remover classe active de todos os itens de navegação
            document.querySelectorAll('.nav-item').forEach(item => {
                item.classList.remove('active');
            });

            // Remover classe active de todas as seções
            document.querySelectorAll('.content-section').forEach(section => {
                section.classList.remove('active');
            });

            // Adicionar classe active ao item clicado
            document.getElementById(`nav-${activeId}`).classList.add('active');

            // Mostrar seção correspondente
            const sections = {
                '1': 'profile-info',
                '2': 'professional-info',
                '3': 'social-info',
                '4': 'security-info'
            };

            document.getElementById(sections[activeId]).classList.add('active');
            
            // Atualizar URL sem recarregar a página
            const newUrl = new URL(window.location);
            newUrl.searchParams.set('section', sections[activeId]);
            window.history.replaceState({}, '', newUrl);
        }

        // Função para determinar seção ativa baseada na URL
        function getActiveSectionFromUrl() {
            const urlParams = new URLSearchParams(window.location.search);
            const section = urlParams.get('section');
            
            const sectionMap = {
                'profile-info': '1',
                'professional-info': '2',
                'social-info': '3',
                'security-info': '4'
            };
            
            return sectionMap[section] || '1';
        }

        // Upload de foto automático
        document.getElementById('fotoInput').addEventListener('change', function () {
            if (this.files && this.files[0]) {
                document.getElementById('uploadForm').click();
            }
        });

        // Preencher países e cidades
        document.addEventListener('DOMContentLoaded', function() {
            // Definir seção ativa baseada na URL
            const activeSection = getActiveSectionFromUrl();
            updateNav(activeSection);
            
            const paisSelect = document.getElementById('pais');
            const cidadeSelect = document.getElementById('cidade');
            
            // Preencher países
            for (const pais in localizacao) {
                const option = document.createElement('option');
                option.value = pais;
                option.textContent = pais;
                if (pais === "<?php echo $perfilData['pais']; ?>") {
                    option.selected = true;
                }
                paisSelect.appendChild(option);
            }

            // Função para preencher cidades
            function preencherCidades(paisSelecionado, cidadeSalva = '') {
                cidadeSelect.innerHTML = '<option value="">Selecione uma cidade</option>';
                
                if (paisSelecionado && localizacao[paisSelecionado]) {
                    localizacao[paisSelecionado].forEach(cidade => {
                        const option = document.createElement('option');
                        option.value = cidade;
                        option.textContent = cidade;
                        if (cidade === cidadeSalva) {
                            option.selected = true;
                        }
                        cidadeSelect.appendChild(option);
                    });
                }
            }

            // Preencher cidades iniciais
            const paisSalvo = "<?php echo $perfilData['pais']; ?>";
            const cidadeSalva = "<?php echo $perfilData['cidade']; ?>";
            if (paisSalvo) {
                preencherCidades(paisSalvo, cidadeSalva);
            }

            // Atualizar cidades quando país muda
            paisSelect.addEventListener('change', function() {
                preencherCidades(this.value);
            });
            
            // Auto-hide feedback message
            const feedbackMessage = document.getElementById('feedback-message');
            if (feedbackMessage) {
                setTimeout(() => {
                    feedbackMessage.style.opacity = '0';
                    setTimeout(() => {
                        feedbackMessage.style.display = 'none';
                    }, 300);
                }, 5000);
            }
        });

        // Fechar mensagem de feedback
        function closeFeedback() {
            const feedbackMessage = document.getElementById('feedback-message');
            if (feedbackMessage) {
                feedbackMessage.style.opacity = '0';
                setTimeout(() => {
                    feedbackMessage.style.display = 'none';
                }, 300);
            }
        }

        // Validação em tempo real para formulário de segurança
        document.addEventListener('DOMContentLoaded', function() {
            const passAtualInput = document.getElementById('pass_atual');
            const passNovaInput = document.getElementById('pass_nova');
            const securityForm = document.querySelector('#security-info form');
            
            if (passNovaInput) {
                passNovaInput.addEventListener('input', function() {
                    const password = this.value;
                    const helpText = this.parentNode.querySelector('.form-help');
                    
                    if (password.length > 0 && password.length < 6) {
                        this.style.borderColor = '#ef4444';
                        if (helpText) {
                            helpText.style.color = '#ef4444';
                            helpText.textContent = 'A palavra-passe deve ter pelo menos 6 caracteres';
                        }
                    } else if (password.length >= 6) {
                        this.style.borderColor = '#10b981';
                        if (helpText) {
                            helpText.style.color = '#10b981';
                            helpText.textContent = 'Palavra-passe válida';
                        }
                    } else {
                        this.style.borderColor = '';
                        if (helpText) {
                            helpText.style.color = '';
                            helpText.textContent = 'Mínimo 6 caracteres';
                        }
                    }
                });
            }
            
            // Validação antes do envio
            if (securityForm) {
                securityForm.addEventListener('submit', function(e) {
                    const passAtual = passAtualInput.value.trim();
                    const passNova = passNovaInput.value.trim();
                    
                    if (!passAtual) {
                        e.preventDefault();
                        alert('Por favor, insira a palavra-passe atual.');
                        passAtualInput.focus();
                        return false;
                    }
                    
                    if (!passNova) {
                        e.preventDefault();
                        alert('Por favor, insira a nova palavra-passe.');
                        passNovaInput.focus();
                        return false;
                    }
                    
                    if (passNova.length < 6) {
                        e.preventDefault();
                        alert('A nova palavra-passe deve ter pelo menos 6 caracteres.');
                        passNovaInput.focus();
                        return false;
                    }
                    
                    if (passAtual === passNova) {
                        e.preventDefault();
                        alert('A nova palavra-passe deve ser diferente da atual.');
                        passNovaInput.focus();
                        return false;
                    }
                });
            }
        });

        // Inicializar Lucide icons
        lucide.createIcons();
    </script>
</body>

</html>