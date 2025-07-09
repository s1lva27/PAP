<?php
$currentUserId = $_SESSION['id'] ?? 0;

// Query to get total unread messages count
$totalUnread = 0;
if ($currentUserId) {
    $sqlUnread = "SELECT COUNT(*) as total_unread
                  FROM mensagens m
                  JOIN conversas c ON m.conversa_id = c.id
                  WHERE (c.utilizador1_id = ? OR c.utilizador2_id = ?)
                  AND m.remetente_id != ?
                  AND m.lida = 0";

    $stmt = $con->prepare($sqlUnread);
    $stmt->bind_param("iii", $currentUserId, $currentUserId, $currentUserId);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $totalUnread = (int) $result['total_unread'];
}

// Query to get total unread notifications count
$totalNotifications = 0;
if ($currentUserId) {
    $sqlNotifications = "SELECT COUNT(*) as total_notifications
                        FROM notificacoes
                        WHERE utilizador_id = ? AND lida = 0";

    $stmt = $con->prepare($sqlNotifications);
    $stmt->bind_param("i", $currentUserId);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $totalNotifications = (int) $result['total_notifications'];
}
?>

<!-- Left Sidebar -->
<aside class="sidebar">
    <nav>
        <ul>
            <li><a href="index.php"><i class="fas fa-home"></i> <span>Home</span></a></li>
            <li><a href="perfil.php"><i class="fas fa-user"></i> <span>Perfil</span></a></li>
            <li><a href="mensagens.php"><i class="fas fa-comments"></i> <span>Mensagens</span>
                    <?php if ($totalUnread > 0): ?>
                        <span id="unread-count-badge" class="notification-badge animate-float"><?= $totalUnread ?></span>
                    <?php else: ?>
                        <span id="unread-count-badge" class="notification-badge" style="display:none;">0</span>
                    <?php endif; ?>
                </a></li>
            <li><a href="notificacoes.php"><i class="fas fa-bell"></i> <span>Notificações</span>
                    <?php if ($totalNotifications > 0): ?>
                        <span id="notifications-count-badge"
                            class="notification-badge animate-float"><?= $totalNotifications ?></span>
                    <?php else: ?>
                        <span id="notifications-count-badge" class="notification-badge" style="display:none;">0</span>
                    <?php endif; ?>
                </a></li>
            <li><a href="itens_salvos.php"><i class="fas fa-bookmark"></i> <span>Itens Salvos</span></a></li>
            <?php if (isset($_SESSION["id"]) && $_SESSION["id_tipos_utilizador"] == 2): ?>
                <li><a href="estatisticas.php"><i class="fas fa-chart-line"></i> <span>Estatísticas</span></a></li>
            <?php endif; ?>
        </ul>
    </nav>
</aside>

<script>
    var sidebar = document.querySelector(".sidebar");
    var link = window.location.href;
    Array.from(sidebar.querySelectorAll("a")).forEach(element => {
        if (link == element.href) {
            element.classList.add("active");
        }
    });

    // Sistema de atualização do contador de mensagens não lidas
    let unreadPolling = null;
    const UNREAD_POLL_INTERVAL = 10000; // 10 segundos

    // Função para atualizar o contador global de mensagens
    function updateUnreadCount(newCount) {
        const badge = document.getElementById('unread-count-badge');
        if (!badge) return;

        const currentCount = parseInt(badge.textContent) || 0;

        // Só atualizar se mudou
        if (currentCount !== newCount) {
            badge.textContent = newCount;

            // Mostrar ou esconder conforme necessário
            if (newCount > 0) {
                badge.style.display = 'inline-flex';
                badge.classList.add('animate-float');
            } else {
                badge.style.display = 'none';
                badge.classList.remove('animate-float');
            }

            // Adicionar animação de mudança
            badge.classList.add('animate-pop');
            setTimeout(() => {
                badge.classList.remove('animate-pop');
            }, 300);
        }
    }

    // Ouvir eventos de atualização de notificações
    document.addEventListener('notificationsUpdated', function (e) {
        updateNotificationsCount(e.detail.newCount);
    });

    // Sincronização entre abas para notificações
    window.addEventListener('storage', function (e) {
        if (e.key === 'notificationsCountUpdate') {
            const data = JSON.parse(e.newValue);
            updateNotificationsCount(data.newCount);
        }
    });

    // Função para atualizar o contador de notificações
    function updateNotificationsCount(newCount) {
        const badge = document.getElementById('notifications-count-badge');
        if (!badge) return;

        const currentCount = parseInt(badge.textContent) || 0;

        // Só atualizar se mudou
        if (currentCount !== newCount) {
            badge.textContent = newCount;

            // Mostrar ou esconder conforme necessário
            if (newCount > 0) {
                badge.style.display = 'inline-flex';
                badge.classList.add('animate-float');
            } else {
                badge.style.display = 'none';
                badge.classList.remove('animate-float');
            }

            // Adicionar animação de mudança
            badge.classList.add('animate-pop');
            setTimeout(() => {
                badge.classList.remove('animate-pop');
            }, 300);
        }
    }

    // Função para verificar mensagens não lidas
    function checkUnreadMessages() {
        fetch('../backend/get_unread_count_global.php')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    updateUnreadCount(data.total_unread);
                }
            })
            .catch(error => {
                console.error('Erro ao verificar mensagens não lidas:', error);
            });
    }

    // Função para verificar notificações não lidas
    function checkUnreadNotifications() {
        fetch('../backend/get_unread_notifications_count.php')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    updateNotificationsCount(data.unread_count);
                }
            })
            .catch(error => {
                console.error('Erro ao verificar notificações não lidas:', error);
            });
    }

    // Iniciar polling para atualização automática
    function startUnreadPolling() {
        // Se já estiver rodando, limpar primeiro
        if (unreadPolling) clearInterval(unreadPolling);

        // Verificar imediatamente e depois em intervalos
        checkUnreadMessages();
        checkUnreadNotifications();
        unreadPolling = setInterval(() => {
            checkUnreadMessages();
            checkUnreadNotifications();
        }, UNREAD_POLL_INTERVAL);
    }

    // Ouvir eventos de atualização (será chamado de mensagens.php)
    document.addEventListener('unreadCountUpdated', function (e) {
        updateUnreadCount(e.detail.newCount);
    });

    // Sincronização entre abas
    window.addEventListener('storage', function (e) {
        if (e.key === 'unreadCountUpdate') {
            const data = JSON.parse(e.newValue);
            updateUnreadCount(data.newCount);
        }
    });

    // Iniciar quando a página carregar
    document.addEventListener('DOMContentLoaded', startUnreadPolling);

    // Pausar quando a aba não estiver visível
    document.addEventListener('visibilitychange', function () {
        if (document.visibilityState === 'hidden') {
            if (unreadPolling) clearInterval(unreadPolling);
        } else {
            startUnreadPolling();
        }
    });

    // Cleanup ao sair da página
    window.addEventListener('beforeunload', function () {
        if (unreadPolling) clearInterval(unreadPolling);
    });
</script>

<style>
    .notification-badge {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background-color: var(--color-primary);
        /* Cor laranja da Orange */
        color: white;
        border-radius: 10px;
        min-width: 18px;
        height: 18px;
        padding: 0 5px;
        font-size: 10px;
        font-weight: 600;
        margin-left: 6px;
        position: relative;
        top: -1px;
        scale: 1.2;
    }

    /* Animação de flutuação contínua */
    .animate-float {
        animation: float 4s ease-in-out infinite;
    }

    @keyframes float {

        0%,
        100% {
            transform: translateY(0);
        }

        50% {
            transform: translateY(-2px);
        }
    }

    /* Animação quando aparece */
    .notification-badge {
        animation: pop-in 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    }

    .animate-pop {
        animation: pop-in 0.3s ease-out;
    }



    @keyframes pop-in {
        0% {
            transform: scale(0.8);
            opacity: 0;
        }

        100% {
            transform: scale(1);
            opacity: 1;
        }
    }

    /* Efeito hover pulsante */
    .notification-badge:hover {
        opacity: 0.9;
        transform: scale(1.05);
        transition: all 0.2s ease;
    }

    @keyframes pulse {


        70% {
            transform: scale(1.1);
            box-shadow: 0 0 0 8px rgba(255, 0, 0, 0);
        }

        100% {
            transform: scale(1);
            box-shadow: 0 0 0 0 rgba(255, 0, 0, 0);
        }
    }

    /* Brilho intermitente */
    .notification-badge::after {
        content: '';
        position: absolute;
        top: -5px;
        right: -5px;
        width: 10px;
        height: 10px;
        background: white;
        border-radius: 50%;
        opacity: 0.8;
        animation: blink 2s infinite;
    }

    @keyframes blink {

        0%,
        100% {
            opacity: 0.8;
            transform: scale(1);
        }

        50% {
            opacity: 0;
            transform: scale(0.5);
        }
    }



    @keyframes gradient-shift {
        0% {
            background-position: 0% 50%;
        }

        50% {
            background-position: 100% 50%;
        }

        100% {
            background-position: 0% 50%;
        }
    }
</style>