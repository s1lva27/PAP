// ==========================================================================
// Orange Social Network - Polls JavaScript
// ==========================================================================

class PollManager {
    constructor() {
        this.pollForm = null;
        this.isFormVisible = false;
        this.maxOptions = 4;
        this.minOptions = 2;
        this.init();
    }

    init() {
        this.createPollForm();
        this.bindEvents();
        this.loadExistingPolls();
    }

    createPollForm() {
        const formHTML = `
            <div class="poll-form" id="pollForm">
                <div class="poll-form-header">
                    <h3 class="poll-form-title">
                        <i class="fas fa-poll"></i>
                        Criar Poll
                    </h3>
                    <button type="button" class="poll-form-close" onclick="pollManager.hidePollForm()">
                        <i class="fas fa-times"></i>
                    </button>
                </div>

                <form id="pollCreationForm" action="../backend/criar_publicacao_poll.php" method="POST">

                    <div class="poll-form-group">
                        <label class="poll-form-label">Pergunta da Poll *</label>
                        <input type="text" name="pergunta" class="poll-form-input" 
                               placeholder="Qual é a sua pergunta?" required maxlength="500">
                    </div>

                    <div class="poll-form-group">
                        <label class="poll-form-label">Descrição (opcional)</label>
                        <textarea name="conteudo" class="poll-form-textarea" 
                                placeholder="Adicione uma descrição à sua poll..."></textarea>
                    </div>

                    <div class="poll-form-group">
                        <label class="poll-form-label">Opções *</label>
                        <div class="poll-options-form" id="pollOptionsContainer">
                            <div class="poll-option-input-group">
                                <input type="text" name="opcoes[]" class="poll-form-input poll-option-input" 
                                       placeholder="Opção 1" required maxlength="200">
                            </div>
                            <div class="poll-option-input-group">
                                <input type="text" name="opcoes[]" class="poll-form-input poll-option-input" 
                                       placeholder="Opção 2" required maxlength="200">
                            </div>
                        </div>
                        <button type="button" class="poll-add-option" id="addOptionBtn" onclick="pollManager.addOption()">
                            <i class="fas fa-plus"></i>
                            Adicionar Opção
                        </button>
                    </div>

                    <div class="poll-form-group">
                        <label class="poll-form-label">Duração da poll</label>
                        <div class="poll-duration-group">
                            <input type="number" name="duracao" class="poll-form-input poll-duration-input" 
                                   value="24" min="1" max="168" required>
                            <span class="poll-duration-unit">horas</span>
                        </div>
                        <small style="color: var(--text-muted); margin-top: 4px; display: block;">
                            Mínimo: 1 hora | Máximo: 7 dias (168 horas)
                        </small>
                    </div>

                    <div class="poll-form-actions">
                        <button type="button" class="poll-form-cancel" onclick="pollManager.hidePollForm()">
                            Cancelar
                        </button>
                        <button type="submit" name="publicar_poll" class="poll-form-submit">
                            <i class="fas fa-poll"></i>
                            Publicar pool
                        </button>
                    </div>
                </form>
            </div>
        `;

        // Inserir o formulário após o create-post
        const createPost = document.querySelector('.create-post');
        if (createPost) {
            createPost.insertAdjacentHTML('afterend', formHTML);
            this.pollForm = document.getElementById('pollForm');
        }
    }

    bindEvents() {
        // Adicionar botão de poll às ações de post
        const postActions = document.querySelector('.post-actions');
        if (postActions && !document.querySelector('.poll-toggle-btn')) {
            const pollButton = document.createElement('button');
            pollButton.className = 'poll-toggle-btn';
            pollButton.type = 'button';
            pollButton.innerHTML = '<i class="fas fa-poll"></i> ';
            pollButton.onclick = () => this.togglePollForm();
            
            // Inserir antes do botão de publicar
            const publishBtn = postActions.querySelector('.publish-btn');
            if (publishBtn) {
                postActions.insertBefore(pollButton, publishBtn);
            } else {
                postActions.appendChild(pollButton);
            }
        }

        // Bind form submission
        const form = document.getElementById('pollCreationForm');
        if (form) {
            form.addEventListener('submit', (e) => this.handleFormSubmit(e));
        }
    }

    togglePollForm() {
        if (this.isFormVisible) {
            this.hidePollForm();
        } else {
            this.showPollForm();
        }
    }

    showPollForm() {
        if (this.pollForm) {
            this.pollForm.classList.add('active');
            this.isFormVisible = true;
            
            // Atualizar botão
            const toggleBtn = document.querySelector('.poll-toggle-btn');
            if (toggleBtn) {
                toggleBtn.classList.add('active');
                toggleBtn.innerHTML = '<i class="fas fa-times"></i> Cancelar';
            }

            // Esconder o formulário de post normal
            const createPost = document.querySelector('.create-post');
            if (createPost) {
                createPost.style.display = 'none';
            }

            // Focus no primeiro input
            const firstInput = this.pollForm.querySelector('input[name="pergunta"]');
            if (firstInput) {
                setTimeout(() => firstInput.focus(), 100);
            }
        }
    }

    hidePollForm() {
        if (this.pollForm) {
            this.pollForm.classList.remove('active');
            this.isFormVisible = false;
            
            // Atualizar botão
            const toggleBtn = document.querySelector('.poll-toggle-btn');
            if (toggleBtn) {
                toggleBtn.classList.remove('active');
                toggleBtn.innerHTML = '<i class="fas fa-poll"></i> Poll';
            }

            // Mostrar o formulário de post normal
            const createPost = document.querySelector('.create-post');
            if (createPost) {
                createPost.style.display = 'block';
            }

            // Limpar formulário
            this.resetForm();
        }
    }

    addOption() {
        const container = document.getElementById('pollOptionsContainer');
        const currentOptions = container.querySelectorAll('.poll-option-input-group');
        
        if (currentOptions.length >= this.maxOptions) {
            this.showToast('Máximo de 4 opções permitidas', 'warning');
            return;
        }

        const optionHTML = `
            <div class="poll-option-input-group">
                <input type="text" name="opcoes[]" class="poll-form-input poll-option-input" 
                       placeholder="Opção ${currentOptions.length + 1}" required maxlength="200">
                <button type="button" class="poll-option-remove" onclick="pollManager.removeOption(this)">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
        `;

        container.insertAdjacentHTML('beforeend', optionHTML);
        
        // Atualizar estado do botão adicionar
        this.updateAddButton();
        
        // Focus no novo input
        const newInput = container.lastElementChild.querySelector('input');
        if (newInput) {
            newInput.focus();
        }
    }

    removeOption(button) {
        const container = document.getElementById('pollOptionsContainer');
        const currentOptions = container.querySelectorAll('.poll-option-input-group');
        
        if (currentOptions.length <= this.minOptions) {
            this.showToast('Mínimo de 2 opções necessárias', 'warning');
            return;
        }

        button.closest('.poll-option-input-group').remove();
        this.updateAddButton();
        this.updatePlaceholders();
    }

    updateAddButton() {
        const container = document.getElementById('pollOptionsContainer');
        const addBtn = document.getElementById('addOptionBtn');
        const currentOptions = container.querySelectorAll('.poll-option-input-group');
        
        if (addBtn) {
            addBtn.disabled = currentOptions.length >= this.maxOptions;
        }
    }

    updatePlaceholders() {
        const container = document.getElementById('pollOptionsContainer');
        const inputs = container.querySelectorAll('.poll-option-input');
        
        inputs.forEach((input, index) => {
            input.placeholder = `Opção ${index + 1}`;
        });
    }

    handleFormSubmit(e) {
        const form = e.target;
        const pergunta = form.querySelector('input[name="pergunta"]').value.trim();
        const opcoes = Array.from(form.querySelectorAll('input[name="opcoes[]"]'))
            .map(input => input.value.trim())
            .filter(value => value.length > 0);

        if (!pergunta) {
            e.preventDefault();
            this.showToast('A pergunta é obrigatória', 'error');
            return;
        }

        if (opcoes.length < this.minOptions) {
            e.preventDefault();
            this.showToast('Mínimo de 2 opções necessárias', 'error');
            return;
        }

        if (opcoes.length > this.maxOptions) {
            e.preventDefault();
            this.showToast('Máximo de 4 opções permitidas', 'error');
            return;
        }

        // Verificar opções duplicadas
        const uniqueOpcoes = [...new Set(opcoes)];
        if (uniqueOpcoes.length !== opcoes.length) {
            e.preventDefault();
            this.showToast('Não é possível ter opções duplicadas', 'error');
            return;
        }

        // Desabilitar botão de submit para evitar duplo envio
        const submitBtn = form.querySelector('.poll-form-submit');
        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Criando...';
        }
    }

    resetForm() {
        const form = document.getElementById('pollCreationForm');
        if (form) {
            form.reset();
            
            // Resetar opções para apenas 2
            const container = document.getElementById('pollOptionsContainer');
            const options = container.querySelectorAll('.poll-option-input-group');
            
            // Remover opções extras
            for (let i = options.length - 1; i >= this.minOptions; i--) {
                options[i].remove();
            }
            
            this.updateAddButton();
            this.updatePlaceholders();
            
            // Resetar botão de submit
            const submitBtn = form.querySelector('.poll-form-submit');
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="fas fa-poll"></i> Publicar Poll';
            }
        }
    }

    showToast(message, type = 'info') {
        // Usar o sistema de toast existente se disponível
        if (typeof showToast === 'function') {
            showToast(message);
        } else {
            alert(message);
        }
    }

    // Método para votar em uma poll
    async voteInPoll(pollId, opcaoId) {
        try {
            const optionElement = document.querySelector(`[data-opcao-id="${opcaoId}"]`);
            if (optionElement) {
                optionElement.classList.add('voting');
            }

            const formData = new FormData();
            formData.append('poll_id', pollId);
            formData.append('opcao_id', opcaoId);

            const response = await fetch('../backend/votar_poll.php', {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            if (data.success) {
                this.updatePollDisplay(pollId, data);
                this.showToast('Voto registado com sucesso!');
            } else {
                this.showToast(data.message || 'Erro ao votar', 'error');
            }
        } catch (error) {
            console.error('Erro ao votar:', error);
            this.showToast('Erro de conexão', 'error');
        } finally {
            if (optionElement) {
                optionElement.classList.remove('voting');
            }
        }
    }

    updatePollDisplay(pollId, data) {
        const pollContainer = document.querySelector(`[data-poll-id="${pollId}"]`);
        if (!pollContainer) return;

        // Atualizar opções
        data.opcoes.forEach(opcao => {
            const optionElement = pollContainer.querySelector(`[data-opcao-id="${opcao.id}"]`);
            if (optionElement) {
                // Atualizar barra de progresso
                const progressBar = optionElement.querySelector('.poll-option-progress');
                if (progressBar) {
                    progressBar.style.width = `${opcao.percentagem}%`;
                }

                // Atualizar estatísticas
                const percentage = optionElement.querySelector('.poll-option-percentage');
                const votes = optionElement.querySelector('.poll-option-votes');
                
                if (percentage) {
                    percentage.textContent = `${opcao.percentagem}%`;
                }
                
                if (votes) {
                    votes.textContent = `${opcao.votos} voto${opcao.votos !== 1 ? 's' : ''}`;
                }

                // Marcar como votada e desabilitar
                optionElement.classList.add('voted', 'disabled');
                optionElement.style.pointerEvents = 'none';

                // Destacar opção líder
                if (opcao.percentagem > 0 && opcao.votos === Math.max(...data.opcoes.map(o => o.votos))) {
                    optionElement.classList.add('leading');
                }
            }
        });

        // Atualizar total de votos
        const totalVotesElement = pollContainer.querySelector('.poll-total-votes');
        if (totalVotesElement) {
            totalVotesElement.textContent = `${data.total_votos} voto${data.total_votos !== 1 ? 's' : ''}`;
        }
    }

    // Método para carregar dados de uma poll
    async loadPollData(pollId) {
        try {
            const response = await fetch(`../backend/get_poll_data.php?poll_id=${pollId}`);
            const data = await response.json();

            if (data.success) {
                this.renderPoll(pollId, data);
            } else {
                console.error('Erro ao carregar poll:', data.message);
            }
        } catch (error) {
            console.error('Erro ao carregar poll:', error);
        }
    }

    renderPoll(pollId, data) {
        const { poll, opcoes, user_voted } = data;
        
        const pollHTML = `
            <div class="poll-container" data-poll-id="${pollId}">
                <div class="poll-question">${poll.pergunta}</div>
                
                <div class="poll-options">
                    ${opcoes.map(opcao => `
                        <div class="poll-option ${user_voted || poll.expirada ? 'disabled' : ''} ${user_voted ? 'voted' : ''}" 
                             data-opcao-id="${opcao.id}"
                             ${!user_voted && !poll.expirada ? `onclick="pollManager.voteInPoll(${pollId}, ${opcao.id})"` : ''}>
                            <div class="poll-option-progress" style="width: ${opcao.percentagem}%"></div>
                            <div class="poll-option-content">
                                <span class="poll-option-text">${opcao.texto}</span>
                                ${user_voted || poll.expirada ? `
                                    <div class="poll-option-stats">
                                        <span class="poll-option-percentage">${opcao.percentagem}%</span>
                                        <span class="poll-option-votes">${opcao.votos} voto${opcao.votos !== 1 ? 's' : ''}</span>
                                    </div>
                                ` : ''}
                            </div>
                        </div>
                    `).join('')}
                </div>
                
                <div class="poll-meta">
                    <span class="poll-total-votes">${poll.total_votos} voto${poll.total_votos !== 1 ? 's' : ''}</span>
                    <span class="poll-time-left ${poll.expirada ? 'poll-expired' : ''}">
                        <i class="fas fa-clock"></i>
                        ${poll.expirada ? 'Poll encerrada' : `Encerra em ${this.formatTimeLeft(poll.data_expiracao)}`}
                    </span>
                </div>
            </div>
        `;
        
        return pollHTML;
    }

    formatTimeLeft(expirationDate) {
        const now = new Date();
        const expDate = new Date(expirationDate);
        const diff = expDate - now;
        
        if (diff <= 0) return 'Poll encerrada';
        
        const hours = Math.floor(diff / (1000 * 60 * 60));
        const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
        
        return `${hours}h ${minutes}m`;
    }

    loadExistingPolls() {
        // Carregar enquetes existentes na página
        document.querySelectorAll('[data-poll-id]').forEach(pollElement => {
            const pollId = pollElement.dataset.pollId;
            this.loadPollData(pollId);
        });
    }
}

// Inicializar o PollManager quando o DOM estiver pronto
document.addEventListener('DOMContentLoaded', () => {
    window.pollManager = new PollManager();
});