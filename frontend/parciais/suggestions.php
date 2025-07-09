<!-- Sugestões de Utilizadores -->
<aside class="suggestions-sidebar">
    <div class="suggestions-container">
        <div class="suggestions-header">
            <h3><i class="fas fa-user-plus"></i> Sugestões para si</h3>
            <button class="refresh-suggestions" onclick="loadSuggestions()" title="Atualizar sugestões">
                <i class="fas fa-sync-alt"></i>
            </button>
        </div>
        
        <div class="suggestions-list" id="suggestionsList">
            <div class="suggestions-loading">
                <i class="fas fa-spinner fa-spin"></i>
                <span>Carregando sugestões...</span>
            </div>
        </div>
        
        <div class="suggestions-footer">
            <a href="pesquisar.php" class="see-more-link">
                <i class="fas fa-search"></i>
                Ver mais pessoas
            </a>
        </div>
    </div>
</aside>

<style>
.suggestions-sidebar {
    width: 300px;
    position: sticky;
    top: calc(var(--header-height) + var(--space-lg));
    height: fit-content;
    max-height: calc(100vh - var(--header-height) - var(--space-xl));
    overflow-y: auto;
    padding-left: var(--space-lg);
}

.suggestions-container {
    background: var(--bg-card);
    border-radius: var(--radius-lg);
    border: 1px solid var(--border-light);
    box-shadow: var(--shadow-sm);
    overflow: hidden;
}

.suggestions-header {
    padding: var(--space-lg);
    border-bottom: 1px solid var(--border-light);
    display: flex;
    align-items: center;
    justify-content: space-between;
    background: var(--bg-input);
}

.suggestions-header h3 {
    margin: 0;
    font-size: 1.1rem;
    font-weight: 600;
    color: var(--text-light);
    display: flex;
    align-items: center;
    gap: var(--space-sm);
}

.suggestions-header i {
    color: var(--color-primary);
    font-size: 1rem;
}

.refresh-suggestions {
    background: transparent;
    border: none;
    color: var(--text-secondary);
    cursor: pointer;
    padding: var(--space-xs);
    border-radius: var(--radius-sm);
    transition: all var(--transition-normal);
    display: flex;
    align-items: center;
    justify-content: center;
    width: 32px;
    height: 32px;
}

.refresh-suggestions:hover {
    background: var(--bg-hover);
    color: var(--color-primary);
    transform: rotate(180deg);
}

.suggestions-list {
    max-height: 400px;
    overflow-y: auto;
}

.suggestions-loading {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: var(--space-sm);
    padding: var(--space-xl);
    color: var(--text-muted);
    font-size: 0.9rem;
}

.suggestions-loading i {
    color: var(--color-primary);
}

.suggestion-item {
    display: flex;
    align-items: center;
    gap: var(--space-md);
    padding: var(--space-md) var(--space-lg);
    border-bottom: 1px solid var(--border-light);
    transition: background var(--transition-normal);
    cursor: pointer;
}

.suggestion-item:last-child {
    border-bottom: none;
}

.suggestion-item:hover {
    background: var(--bg-hover);
}

.suggestion-avatar {
    width: 48px;
    height: 48px;
    border-radius: 50%;
    object-fit: cover;
    border: 2px solid var(--color-primary);
    flex-shrink: 0;
}

.suggestion-info {
    flex: 1;
    min-width: 0;
}

.suggestion-name {
    font-weight: 600;
    color: var(--text-light);
    font-size: 0.95rem;
    margin: 0 0 2px 0;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.suggestion-username {
    color: var(--text-secondary);
    font-size: 0.85rem;
    margin: 0 0 4px 0;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.suggestion-meta {
    color: var(--text-muted);
    font-size: 0.75rem;
    display: flex;
    align-items: center;
    gap: var(--space-xs);
}

.suggestion-meta i {
    font-size: 0.7rem;
}

.follow-btn {
    background: var(--color-primary);
    color: white;
    border: none;
    padding: var(--space-xs) var(--space-md);
    border-radius: var(--radius-md);
    font-size: 0.85rem;
    font-weight: 500;
    cursor: pointer;
    transition: all var(--transition-normal);
    flex-shrink: 0;
    min-width: 70px;
    height: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.follow-btn:hover {
    background: var(--color-primary-dark);
    transform: translateY(-1px);
    box-shadow: var(--shadow-sm);
}

.follow-btn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
    transform: none;
}

.follow-btn.following {
    background: var(--bg-input);
    color: var(--text-secondary);
    border: 1px solid var(--border-light);
}

.follow-btn.following:hover {
    background: #ef4444;
    color: white;
    border-color: #ef4444;
}

.follow-btn.following:hover::after {
    content: "Deixar de seguir";
}

.follow-btn.following::after {
    content: "A seguir";
}

.suggestions-footer {
    padding: var(--space-md) var(--space-lg);
    border-top: 1px solid var(--border-light);
    background: var(--bg-input);
}

.see-more-link {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: var(--space-sm);
    color: var(--color-primary);
    text-decoration: none;
    font-size: 0.9rem;
    font-weight: 500;
    padding: var(--space-sm);
    border-radius: var(--radius-md);
    transition: all var(--transition-normal);
}

.see-more-link:hover {
    background: rgba(255, 87, 34, 0.1);
    transform: translateY(-1px);
}

.no-suggestions {
    padding: var(--space-xl);
    text-align: center;
    color: var(--text-muted);
}

.no-suggestions i {
    font-size: 2rem;
    margin-bottom: var(--space-md);
    opacity: 0.5;
}

.no-suggestions h4 {
    margin: 0 0 var(--space-sm);
    color: var(--text-secondary);
}

.no-suggestions p {
    margin: 0;
    font-size: 0.9rem;
}

/* Scrollbar personalizada */
.suggestions-list::-webkit-scrollbar {
    width: 4px;
}

.suggestions-list::-webkit-scrollbar-track {
    background: var(--bg-input);
}

.suggestions-list::-webkit-scrollbar-thumb {
    background: var(--border-light);
    border-radius: 2px;
}

.suggestions-list::-webkit-scrollbar-thumb:hover {
    background: var(--text-muted);
}

/* Animações */
@keyframes slideInSuggestion {
    from {
        opacity: 0;
        transform: translateX(20px);
    }
    to {
        opacity: 1;
        transform: translateX(0);
    }
}

.suggestion-item {
    animation: slideInSuggestion 0.3s ease-out;
}

/* Responsive */
@media (max-width: 1200px) {
    .suggestions-sidebar {
        display: none;
    }
}

@media (max-width: 992px) {
    .suggestions-sidebar {
        display: none;
    }
}
</style>

<script>
let suggestionsLoaded = false;

// Carregar sugestões quando a página carrega
document.addEventListener('DOMContentLoaded', function() {
    loadSuggestions();
});

function loadSuggestions() {
    const suggestionsList = document.getElementById('suggestionsList');
    const refreshBtn = document.querySelector('.refresh-suggestions');
    
    // Mostrar loading
    suggestionsList.innerHTML = `
        <div class="suggestions-loading">
            <i class="fas fa-spinner fa-spin"></i>
            <span>Carregando sugestões...</span>
        </div>
    `;
    
    // Animar botão de refresh
    if (refreshBtn) {
        refreshBtn.style.transform = 'rotate(180deg)';
        setTimeout(() => {
            refreshBtn.style.transform = 'rotate(0deg)';
        }, 300);
    }
    
    fetch('../backend/get_suggestions.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displaySuggestions(data.suggestions);
                suggestionsLoaded = true;
            } else {
                showSuggestionsError('Erro ao carregar sugestões');
            }
        })
        .catch(error => {
            console.error('Erro:', error);
            showSuggestionsError('Erro de conexão');
        });
}

function displaySuggestions(suggestions) {
    const suggestionsList = document.getElementById('suggestionsList');
    
    if (suggestions.length === 0) {
        suggestionsList.innerHTML = `
            <div class="no-suggestions">
                <i class="fas fa-users"></i>
                <h4>Nenhuma sugestão</h4>
                <p>Explore mais para encontrar pessoas interessantes!</p>
            </div>
        `;
        return;
    }
    
    const suggestionsHTML = suggestions.map((user, index) => {
        const commonFollowersText = user.seguidores_em_comum > 0 
            ? `<i class="fas fa-users"></i> ${user.seguidores_em_comum} em comum`
            : `<i class="fas fa-user"></i> ${user.ocupacao}`;
            
        return `
            <div class="suggestion-item" style="animation-delay: ${index * 0.1}s" onclick="goToProfile(${user.id})">
                <img src="images/perfil/${user.foto_perfil}" 
                     alt="${user.nome_completo}" 
                     class="suggestion-avatar">
                <div class="suggestion-info">
                    <h4 class="suggestion-name">${user.nome_completo}</h4>
                    <p class="suggestion-username">@${user.nick}</p>
                    <div class="suggestion-meta">
                        ${commonFollowersText}
                    </div>
                </div>
                <button class="follow-btn" 
                        onclick="followUser(event, ${user.id}, this)"
                        data-user-id="${user.id}">
                    Seguir
                </button>
            </div>
        `;
    }).join('');
    
    suggestionsList.innerHTML = suggestionsHTML;
}

function showSuggestionsError(message) {
    const suggestionsList = document.getElementById('suggestionsList');
    suggestionsList.innerHTML = `
        <div class="no-suggestions">
            <i class="fas fa-exclamation-triangle"></i>
            <h4>Erro</h4>
            <p>${message}</p>
        </div>
    `;
}

function followUser(event, userId, button) {
    event.stopPropagation(); // Evitar que clique no perfil
    
    if (button.disabled) return;
    
    const isFollowing = button.classList.contains('following');
    const action = isFollowing ? 'unfollow' : 'follow';
    
    // Desabilitar botão temporariamente
    button.disabled = true;
    button.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
    
    const formData = new FormData();
    formData.append('user_id', userId);
    formData.append('action', action);
    
    fetch('../backend/follow_user.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            if (data.action === 'followed') {
                button.classList.add('following');
                button.innerHTML = 'A seguir';
                showToast('Utilizador seguido com sucesso!');
            } else {
                button.classList.remove('following');
                button.innerHTML = 'Seguir';
                showToast('Deixou de seguir o utilizador');
            }
        } else {
            showToast(data.message || 'Erro ao processar ação', 'error');
            // Restaurar estado original
            button.innerHTML = isFollowing ? 'A seguir' : 'Seguir';
        }
    })
    .catch(error => {
        console.error('Erro:', error);
        showToast('Erro de conexão', 'error');
        button.innerHTML = isFollowing ? 'A seguir' : 'Seguir';
    })
    .finally(() => {
        button.disabled = false;
    });
}

function goToProfile(userId) {
    window.location.href = `perfil.php?id=${userId}`;
}

function showToast(message, type = 'success') {
    // Usar o sistema de toast existente se disponível
    if (typeof showToast !== 'undefined') {
        showToast(message);
    } else {
        // Fallback simples
        const toast = document.createElement('div');
        toast.style.cssText = `
            position: fixed;
            bottom: 20px;
            right: 20px;
            background: ${type === 'error' ? '#ef4444' : '#10b981'};
            color: white;
            padding: 12px 20px;
            border-radius: 8px;
            z-index: 10000;
            animation: slideInToast 0.3s ease;
        `;
        toast.textContent = message;
        document.body.appendChild(toast);
        
        setTimeout(() => {
            toast.remove();
        }, 3000);
    }
}

// Atualizar sugestões periodicamente (opcional)
setInterval(() => {
    if (suggestionsLoaded && document.visibilityState === 'visible') {
        loadSuggestions();
    }
}, 300000); // 5 minutos
</script>