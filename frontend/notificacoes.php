<?php
session_start();
require "../backend/ligabd.php";

// Verificar se o utilizador está autenticado
if (!isset($_SESSION["id"])) {
    header("Location: login.php");
    exit();
}

$userId = $_SESSION["id"];
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
    <title>Notificações - Orange</title>
    <link rel="stylesheet" href="css/app.css">
    <link rel="stylesheet" href="css/style_index.css">
    <link rel="icon" type="image/x-icon" href="images/favicon/favicon_orange.png">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .notifications-container {
            flex: 1;
            max-width: 800px;
        }

        .notifications-header {
            padding: var(--space-lg);
            background: var(--bg-card);
            border-radius: var(--radius-lg);
            margin-bottom: var(--space-xl);
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--border-light);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .notifications-header-left {
            display: flex;
            align-items: center;
            gap: var(--space-md);
        }

        .notifications-header i {
            font-size: 1.5rem;
            color: var(--color-primary);
        }

        .notifications-header h2 {
            margin: 0;
            color: var(--text-light);
        }

        .notifications-list {
            background: var(--bg-card);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--border-light);
            overflow: hidden;
        }

        .notification-item {
            display: flex;
            align-items: flex-start;
            gap: var(--space-md);
            padding: var(--space-lg);
            border-bottom: 1px solid var(--border-light);
            transition: background var(--transition-normal);
            cursor: pointer;
            position: relative;
        }

        .notification-item:last-child {
            border-bottom: none;
        }

        .notification-item:hover {
            background: var(--bg-hover);
        }

        .notification-avatar {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid var(--color-primary);
            flex-shrink: 0;
        }

        .notification-content {
            flex: 1;
            min-width: 0;
        }

        .notification-message {
            color: var(--text-light);
            font-size: 1rem;
            line-height: 1.5;
            margin-bottom: var(--space-xs);
        }

        .notification-message strong {
            font-weight: 600;
        }

        .notification-meta {
            display: flex;
            align-items: center;
            gap: var(--space-md);
            font-size: 0.875rem;
            color: var(--text-muted);
        }

        .notification-time {
            display: flex;
            align-items: center;
            gap: var(--space-xs);
        }

        .notification-type {
            display: flex;
            align-items: center;
            gap: var(--space-xs);
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 500;
        }

        .notification-type.like {
            background: rgba(59, 130, 246, 0.1);
            color: #3b82f6;
        }

        .notification-type.comment {
            background: rgba(16, 185, 129, 0.1);
            color: #10b981;
        }

        .notification-type.follow {
            background: rgba(139, 92, 246, 0.1);
            color: #8b5cf6;
        }

        .notification-type.save {
            background: rgba(245, 158, 11, 0.1);
            color: #f59e0b;
        }

        .notification-icon {
            width: 20px;
            height: 20px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.75rem;
            color: white;
            flex-shrink: 0;
        }

        .notification-icon.like {
            background: #3b82f6;
        }

        .notification-icon.comment {
            background: #10b981;
        }

        .notification-icon.follow {
            background: #8b5cf6;
        }

        .notification-icon.save {
            background: #f59e0b;
        }

        .no-notifications {
            text-align: center;
            padding: var(--space-xxl);
            color: var(--text-muted);
        }

        .no-notifications i {
            font-size: 4rem;
            margin-bottom: var(--space-lg);
            opacity: 0.5;
        }

        .no-notifications h3 {
            margin: 0 0 var(--space-sm);
            color: var(--text-light);
        }

        .no-notifications p {
            margin: 0;
            color: var(--text-secondary);
        }

        .loading-notifications {
            display: flex;
            justify-content: center;
            align-items: center;
            padding: var(--space-xl);
            color: var(--text-muted);
        }

        .loading-notifications i {
            animation: spin 1s linear infinite;
            margin-right: var(--space-sm);
        }

        @keyframes spin {
            from {
                transform: rotate(0deg);
            }

            to {
                transform: rotate(360deg);
            }
        }

        .load-more-btn {
            display: block;
            width: 100%;
            padding: var(--space-md);
            background: var(--bg-input);
            color: var(--text-secondary);
            border: none;
            border-top: 1px solid var(--border-light);
            cursor: pointer;
            transition: all var(--transition-normal);
            font-size: 0.9rem;
        }

        .load-more-btn:hover {
            background: var(--bg-hover);
            color: var(--text-light);
        }

        .load-more-btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        /* Toast Notification */
        .toast {
            position: fixed;
            bottom: 20px;
            left: 50%;
            transform: translateX(-50%);
            background: var(--color-primary);
            color: white;
            padding: 12px 24px;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            display: none;
            align-items: center;
            gap: 12px;
            z-index: 1000;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .toast.show {
            opacity: 1;
        }

        .toast-icon {
            font-size: 1.2rem;
        }

        .toast-content p {
            margin: 0;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .notifications-header {
                flex-direction: column;
                gap: var(--space-md);
                align-items: flex-start;
            }

            .notification-item {
                padding: var(--space-md);
            }

            .notification-avatar {
                width: 40px;
                height: 40px;
            }

            .notification-meta {
                flex-direction: column;
                align-items: flex-start;
                gap: var(--space-xs);
            }
        }
    </style>
</head>

<body>
    <?php require "parciais/header.php" ?>

    <div class="container">
        <?php require("parciais/sidebar.php"); ?>

        <main class="notifications-container">
            <div class="notifications-header">
                <div class="notifications-header-left">
                    <i class="fas fa-bell"></i>
                    <h2>Notificações</h2>
                </div>
            </div>

            <div class="notifications-list" id="notifications-list">
                <div class="loading-notifications">
                    <i class="fas fa-spinner"></i>
                    Carregando notificações...
                </div>
            </div>
        </main>
        <!-- Sugestões de Utilizadores -->
        <?php require("parciais/suggestions.php"); ?>
    </div>

    <!-- Toast Notification -->
    <div id="toast" class="toast">
        <div class="toast-icon">
            <i class="fas fa-check"></i>
        </div>
        <div class="toast-content">
            <p id="toast-message">Mensagem aqui</p>
        </div>
    </div>

    <script>
        let currentOffset = 0;
        const limit = 20;
        let loading = false;
        let hasMore = true;

        document.addEventListener('DOMContentLoaded', function () {
            loadNotifications();
        });

        function loadNotifications(append = false) {
            if (loading) return;
            loading = true;

            if (!append) {
                const container = document.getElementById('notifications-list');
                container.innerHTML = `
            <div class="loading-notifications">
                <i class="fas fa-spinner"></i>
                Carregando notificações...
            </div>
        `;
            }

            const url = `../backend/get_notifications.php?limit=${limit}&offset=${currentOffset}`;

            fetch(url, {
                credentials: 'same-origin'
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        displayNotifications(data.notifications, append);

                        // Atualizar o contador na sidebar com o valor ANTES de marcar como lidas
                        // Se for o primeiro carregamento (offset == 0), atualizar para 0
                        updateSidebarNotificationCount(currentOffset === 0 ? 0 : data.unread_count);

                        hasMore = data.notifications.length >= limit;
                        currentOffset += data.notifications.length;
                    } else {
                        showError(data.message || 'Erro ao carregar notificações');
                    }
                })
                .catch(error => {
                    console.error('Erro:', error);
                    showError('Erro de conexão. Tente recarregar a página.');
                })
                .finally(() => {
                    loading = false;
                });
        }

        function updateSidebarNotificationCount(newCount) {
            // Disparar evento para atualizar a sidebar
            const event = new CustomEvent('notificationsUpdated', {
                detail: { newCount: newCount }
            });
            document.dispatchEvent(event);

            // Atualizar outras abas
            localStorage.setItem('notificationsCountUpdate', JSON.stringify({
                newCount: newCount,
                timestamp: Date.now()
            }));
        }

        // Nova função para atualizar a sidebar
        function updateSidebarNotificationCount(newCount) {
            // Disparar evento para atualizar a sidebar
            const event = new CustomEvent('notificationsUpdated', {
                detail: { newCount: newCount }
            });
            document.dispatchEvent(event);

            // Atualizar outras abas
            localStorage.setItem('notificationsCountUpdate', JSON.stringify({
                newCount: newCount,
                timestamp: Date.now()
            }));
        }

        function displayNotifications(notifications, append = false) {
            const container = document.getElementById('notifications-list');

            if (!append) {
                container.innerHTML = '';
            }

            if (notifications.length === 0 && !append) {
                container.innerHTML = `
                    <div class="no-notifications">
                        <i class="fas fa-bell-slash"></i>
                        <h3>Nenhuma notificação</h3>
                        <p>Quando alguém interagir com o seu conteúdo, as notificações aparecerão aqui.</p>
                    </div>
                `;
                return;
            }

            notifications.forEach(notification => {
                const notificationElement = createNotificationElement(notification);
                container.appendChild(notificationElement);
            });

            // Adicionar botão "Carregar mais" se houver mais notificações
            if (hasMore && notifications.length === limit) {
                const loadMoreBtn = document.createElement('button');
                loadMoreBtn.className = 'load-more-btn';
                loadMoreBtn.textContent = 'Carregar mais notificações';
                loadMoreBtn.onclick = () => {
                    loadMoreBtn.remove();
                    loadNotifications(true);
                };
                container.appendChild(loadMoreBtn);
            }
        }

        function createNotificationElement(notification) {
            const div = document.createElement('div');
            div.className = 'notification-item';

            const timeAgo = getTimeAgo(notification.data_criacao);
            const typeIcon = getTypeIcon(notification.tipo);
            const foto = notification.remetente_foto || 'default-profile.jpg';

            div.innerHTML = `
                <img src="images/perfil/${foto}" alt="${notification.remetente_nome}" class="notification-avatar">
                <div class="notification-content">
                    <div class="notification-message">
                        ${notification.mensagem}
                    </div>
                    <div class="notification-meta">
                        <div class="notification-time">
                            <i class="fas fa-clock"></i>
                            ${timeAgo}
                        </div>
                        <div class="notification-type ${notification.tipo}">
                            ${typeIcon}
                            ${getTypeLabel(notification.tipo)}
                        </div>
                    </div>
                </div>
                <div class="notification-icon ${notification.tipo}">
                    ${typeIcon}
                </div>
            `;

            // Adicionar click handler para ir para o perfil ou publicação
            div.addEventListener('click', () => {
                if (notification.publicacao_id) {
                    // Ir para a publicação (pode implementar modal ou página específica)
                    window.location.href = `perfil.php?id=${notification.remetente_id}`;
                } else {
                    // Ir para o perfil do remetente
                    window.location.href = `perfil.php?id=${notification.remetente_id}`;
                }
            });

            return div;
        }

        function getTypeIcon(type) {
            const icons = {
                'like': '<i class="fas fa-thumbs-up"></i>',
                'comment': '<i class="fas fa-comment"></i>',
                'follow': '<i class="fas fa-user-plus"></i>',
                'save': '<i class="fas fa-bookmark"></i>'
            };
            return icons[type] || '<i class="fas fa-bell"></i>';
        }

        function getTypeLabel(type) {
            const labels = {
                'like': 'Like',
                'comment': 'Comentário',
                'follow': 'Seguidor',
                'save': 'Guardado'
            };
            return labels[type] || 'Notificação';
        }

        function getTimeAgo(dateString) {
            const date = new Date(dateString);
            const now = new Date();
            const diffInSeconds = Math.floor((now - date) / 1000);

            if (diffInSeconds < 60) {
                return 'Agora mesmo';
            } else if (diffInSeconds < 3600) {
                const minutes = Math.floor(diffInSeconds / 60);
                return `${minutes} min atrás`;
            } else if (diffInSeconds < 86400) {
                const hours = Math.floor(diffInSeconds / 3600);
                return `${hours}h atrás`;
            } else if (diffInSeconds < 604800) {
                const days = Math.floor(diffInSeconds / 86400);
                return `${days}d atrás`;
            } else {
                return date.toLocaleDateString('pt-PT');
            }
        }

        function showError(message) {
            const container = document.getElementById('notifications-list');
            container.innerHTML = `
                <div class="no-notifications">
                    <i class="fas fa-exclamation-triangle"></i>
                    <h3>Erro</h3>
                    <p>${message}</p>
                </div>
            `;
        }
    </script>
</body>

</html>