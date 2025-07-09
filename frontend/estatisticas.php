<?php
session_start();
require "../backend/ligabd.php";

// Verificar se o utilizador está autenticado e é administrador
if (!isset($_SESSION["id"]) || $_SESSION["id_tipos_utilizador"] != 2) {
    header("Location: index.php");
    exit();
}

// Função para calcular crescimento percentual
function calcularCrescimento($atual, $anterior)
{
    if ($anterior == 0)
        return $atual > 0 ? 100 : 0;
    return round((($atual - $anterior) / $anterior) * 100, 1);
}

// Função para formatar números grandes
function formatarNumero($numero)
{
    if ($numero >= 1000000) {
        return round($numero / 1000000, 1) . 'M';
    } elseif ($numero >= 1000) {
        return round($numero / 1000, 1) . 'K';
    }
    return $numero;
}

// === ESTATÍSTICAS GERAIS ===

// Total de utilizadores
$sqlTotalUsers = "SELECT COUNT(*) as total FROM utilizadores";
$resultTotalUsers = mysqli_query($con, $sqlTotalUsers);
$totalUsers = mysqli_fetch_assoc($resultTotalUsers)['total'];

// Utilizadores ativos (últimos 30 dias)
$sqlActiveUsers = "SELECT COUNT(DISTINCT u.id) as total 
                   FROM utilizadores u 
                   LEFT JOIN publicacoes p ON u.id = p.id_utilizador 
                   LEFT JOIN comentarios c ON u.id = c.utilizador_id 
                   WHERE p.data_criacao >= DATE_SUB(NOW(), INTERVAL 30 DAY) 
                   OR c.data >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
$resultActiveUsers = mysqli_query($con, $sqlActiveUsers);
$activeUsers = mysqli_fetch_assoc($resultActiveUsers)['total'];

// Total de publicações
$sqlTotalPosts = "SELECT COUNT(*) as total FROM publicacoes WHERE deletado_em = '0000-00-00 00:00:00'";
$resultTotalPosts = mysqli_query($con, $sqlTotalPosts);
$totalPosts = mysqli_fetch_assoc($resultTotalPosts)['total'];

// Total de comentários
$sqlTotalComments = "SELECT COUNT(*) as total FROM comentarios";
$resultTotalComments = mysqli_query($con, $sqlTotalComments);
$totalComments = mysqli_fetch_assoc($resultTotalComments)['total'];

// Total de likes
$sqlTotalLikes = "SELECT COUNT(*) as total FROM publicacao_likes";
$resultTotalLikes = mysqli_query($con, $sqlTotalLikes);
$totalLikes = mysqli_fetch_assoc($resultTotalLikes)['total'];

// Total de mensagens
$sqlTotalMessages = "SELECT COUNT(*) as total FROM mensagens";
$resultTotalMessages = mysqli_query($con, $sqlTotalMessages);
$totalMessages = mysqli_fetch_assoc($resultTotalMessages)['total'];

// === ESTATÍSTICAS DE CRESCIMENTO (últimos 30 dias vs 30 dias anteriores) ===

// Novos utilizadores (últimos 30 dias)
$sqlNewUsers = "SELECT COUNT(*) as total FROM utilizadores WHERE data_criacao >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
$resultNewUsers = mysqli_query($con, $sqlNewUsers);
$newUsers = mysqli_fetch_assoc($resultNewUsers)['total'];

// Utilizadores do período anterior (30-60 dias atrás)
$sqlPrevUsers = "SELECT COUNT(*) as total FROM utilizadores 
                 WHERE data_criacao >= DATE_SUB(NOW(), INTERVAL 60 DAY) 
                 AND data_criacao < DATE_SUB(NOW(), INTERVAL 30 DAY)";
$resultPrevUsers = mysqli_query($con, $sqlPrevUsers);
$prevUsers = mysqli_fetch_assoc($resultPrevUsers)['total'];

// Novas publicações (últimos 30 dias)
$sqlNewPosts = "SELECT COUNT(*) as total FROM publicacoes 
                WHERE data_criacao >= DATE_SUB(NOW(), INTERVAL 30 DAY) 
                AND deletado_em = '0000-00-00 00:00:00'";
$resultNewPosts = mysqli_query($con, $sqlNewPosts);
$newPosts = mysqli_fetch_assoc($resultNewPosts)['total'];

// Publicações do período anterior
$sqlPrevPosts = "SELECT COUNT(*) as total FROM publicacoes 
                 WHERE data_criacao >= DATE_SUB(NOW(), INTERVAL 60 DAY) 
                 AND data_criacao < DATE_SUB(NOW(), INTERVAL 30 DAY)
                 AND deletado_em = '0000-00-00 00:00:00'";
$resultPrevPosts = mysqli_query($con, $sqlPrevPosts);
$prevPosts = mysqli_fetch_assoc($resultPrevPosts)['total'];

// === ESTATÍSTICAS DETALHADAS ===

// Utilizadores por tipo
$sqlUserTypes = "SELECT tu.tipo_utilizador, COUNT(*) as total 
                 FROM utilizadores u 
                 JOIN tipos_utilizador tu ON u.id_tipos_utilizador = tu.id_tipos_utilizador 
                 GROUP BY tu.tipo_utilizador";
$resultUserTypes = mysqli_query($con, $sqlUserTypes);
$userTypes = [];
while ($row = mysqli_fetch_assoc($resultUserTypes)) {
    $userTypes[] = $row;
}

// Top 5 utilizadores mais ativos (por publicações)
$sqlTopUsers = "SELECT u.nick, u.nome_completo, COUNT(p.id_publicacao) as total_posts,
                       (SELECT COUNT(*) FROM comentarios WHERE utilizador_id = u.id) as total_comments,
                       (SELECT COUNT(*) FROM publicacao_likes WHERE utilizador_id = u.id) as total_likes
                FROM utilizadores u 
                LEFT JOIN publicacoes p ON u.id = p.id_utilizador AND p.deletado_em = '0000-00-00 00:00:00'
                GROUP BY u.id 
                ORDER BY total_posts DESC 
                LIMIT 5";
$resultTopUsers = mysqli_query($con, $sqlTopUsers);
$topUsers = [];
while ($row = mysqli_fetch_assoc($resultTopUsers)) {
    $topUsers[] = $row;
}

// Publicações por tipo
$sqlPostTypes = "SELECT 
                    SUM(CASE WHEN tipo = 'post' THEN 1 ELSE 0 END) as posts_normais,
                    SUM(CASE WHEN tipo = 'poll' THEN 1 ELSE 0 END) as polls
                 FROM publicacoes WHERE deletado_em = '0000-00-00 00:00:00'";
$resultPostTypes = mysqli_query($con, $sqlPostTypes);
$postTypes = mysqli_fetch_assoc($resultPostTypes);

// Estatísticas de mídia
$sqlMediaStats = "SELECT 
                     COUNT(*) as total_medias,
                     SUM(CASE WHEN tipo = 'imagem' THEN 1 ELSE 0 END) as total_imagens,
                     SUM(CASE WHEN tipo = 'video' THEN 1 ELSE 0 END) as total_videos
                  FROM publicacao_medias";
$resultMediaStats = mysqli_query($con, $sqlMediaStats);
$mediaStats = mysqli_fetch_assoc($resultMediaStats);

// Atividade por dia da semana (últimos 30 dias)
$sqlWeeklyActivity = "SELECT 
                         DAYNAME(data_criacao) as dia_semana,
                         COUNT(*) as total
                      FROM publicacoes 
                      WHERE data_criacao >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                      AND deletado_em = '0000-00-00 00:00:00'
                      GROUP BY DAYOFWEEK(data_criacao), DAYNAME(data_criacao)
                      ORDER BY DAYOFWEEK(data_criacao)";
$resultWeeklyActivity = mysqli_query($con, $sqlWeeklyActivity);
$weeklyActivity = [];
while ($row = mysqli_fetch_assoc($resultWeeklyActivity)) {
    $weeklyActivity[] = $row;
}

// Estatísticas de mensagens
$sqlMessageStats = "SELECT 
                       COUNT(*) as total_mensagens,
                       COUNT(DISTINCT conversa_id) as total_conversas,
                       AVG(LENGTH(conteudo)) as tamanho_medio_mensagem
                    FROM mensagens";
$resultMessageStats = mysqli_query($con, $sqlMessageStats);
$messageStats = mysqli_fetch_assoc($resultMessageStats);

// Calcular crescimentos
$userGrowth = calcularCrescimento($newUsers, $prevUsers);
$postGrowth = calcularCrescimento($newPosts, $prevPosts);
?>

<!DOCTYPE html>
<html lang="pt">

<head>
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Estatísticas - Orange Admin</title>
    <link rel="stylesheet" href="css/app.css">
    <link rel="stylesheet" href="css/style_index.css">
    <link rel="icon" type="image/x-icon" href="images/favicon/favicon_orange.png">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .stats-container {
            flex: 1;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 var(--space-lg);
        }

        .stats-header {
            background: linear-gradient(135deg, var(--color-primary), var(--color-primary-dark));
            color: white;
            padding: var(--space-xl);
            border-radius: var(--radius-lg);
            margin-bottom: var(--space-xl);
            text-align: center;
            box-shadow: var(--shadow-lg);
        }

        .stats-header h1 {
            margin: 0;
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: var(--space-sm);
        }

        .stats-header p {
            margin: 0;
            opacity: 0.9;
            font-size: 1.1rem;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: var(--space-lg);
            margin-bottom: var(--space-xl);
        }

        .stat-card {
            background: var(--bg-card);
            border-radius: var(--radius-lg);
            padding: var(--space-lg);
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--border-light);
            transition: transform var(--transition-normal), box-shadow var(--transition-normal);
            position: relative;
            overflow: hidden;
        }

        .stat-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-lg);
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--color-primary), var(--color-primary-light));
        }

        .stat-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: var(--space-md);
        }

        .stat-icon {
            width: 48px;
            height: 48px;
            border-radius: var(--radius-md);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: white;
        }

        .stat-icon.users {
            background: linear-gradient(135deg, #3b82f6, #1d4ed8);
        }

        .stat-icon.posts {
            background: linear-gradient(135deg, #10b981, #047857);
        }

        .stat-icon.comments {
            background: linear-gradient(135deg, #f59e0b, #d97706);
        }

        .stat-icon.likes {
            background: linear-gradient(135deg, #ef4444, #dc2626);
        }

        .stat-icon.messages {
            background: linear-gradient(135deg, #8b5cf6, #7c3aed);
        }

        .stat-icon.active {
            background: linear-gradient(135deg, #06b6d4, #0891b2);
        }

        .stat-value {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--text-light);
            margin-bottom: var(--space-xs);
        }

        .stat-label {
            color: var(--text-secondary);
            font-size: 0.9rem;
            margin-bottom: var(--space-sm);
        }

        .stat-growth {
            display: flex;
            align-items: center;
            gap: var(--space-xs);
            font-size: 0.85rem;
            font-weight: 500;
        }

        .stat-growth.positive {
            color: #10b981;
        }

        .stat-growth.negative {
            color: #ef4444;
        }

        .stat-growth.neutral {
            color: var(--text-muted);
        }

        .detailed-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: var(--space-xl);
            margin-bottom: var(--space-xl);
        }

        .detail-card {
            background: var(--bg-card);
            border-radius: var(--radius-lg);
            padding: var(--space-lg);
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--border-light);
        }

        .detail-card h3 {
            margin: 0 0 var(--space-lg);
            color: var(--text-light);
            font-size: 1.3rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: var(--space-sm);
        }

        .detail-card h3 i {
            color: var(--color-primary);
        }

        .user-list {
            display: flex;
            flex-direction: column;
            gap: var(--space-md);
        }

        .user-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: var(--space-md);
            background: var(--bg-input);
            border-radius: var(--radius-md);
        }

        .user-info h4 {
            margin: 0;
            color: var(--text-light);
            font-size: 1rem;
        }

        .user-info p {
            margin: 0;
            color: var(--text-secondary);
            font-size: 0.85rem;
        }

        .user-stats {
            display: flex;
            gap: var(--space-md);
            font-size: 0.8rem;
            color: var(--text-muted);
        }

        .chart-container {
            position: relative;
            height: 300px;
            margin-top: var(--space-md);
        }

        .metric-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: var(--space-sm) 0;
            border-bottom: 1px solid var(--border-light);
        }

        .metric-row:last-child {
            border-bottom: none;
        }

        .metric-label {
            color: var(--text-secondary);
            font-weight: 500;
        }

        .metric-value {
            color: var(--text-light);
            font-weight: 600;
        }

        .refresh-btn {
            position: fixed;
            bottom: 30px;
            right: 30px;
            background: var(--color-primary);
            color: white;
            border: none;
            width: 56px;
            height: 56px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            cursor: pointer;
            box-shadow: var(--shadow-lg);
            transition: all var(--transition-normal);
            z-index: 1000;
        }

        .refresh-btn:hover {
            background: var(--color-primary-dark);
            transform: scale(1.1);
        }

        .refresh-btn:active {
            transform: scale(0.95);
        }

        @media (max-width: 768px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }

            .detailed-stats {
                grid-template-columns: 1fr;
            }

            .stats-header h1 {
                font-size: 2rem;
            }

            .stat-value {
                font-size: 2rem;
            }
        }

        .loading {
            opacity: 0.6;
            pointer-events: none;
        }

        .loading .refresh-btn {
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            from {
                transform: rotate(0deg);
            }

            to {
                transform: rotate(360deg);
            }
        }
    </style>
</head>

<body>
    <?php require "parciais/header.php" ?>

    <div class="container">
        <?php require("parciais/sidebar.php"); ?>

        <main class="stats-container">
            <div class="stats-header">
                <h1><i class="fas fa-chart-line"></i> Painel de Estatísticas</h1>
                <p>Visão geral da Orange</p>
            </div>

            <!-- Estatísticas Principais -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-header">
                        <div class="stat-icon users">
                            <i class="fas fa-users"></i>
                        </div>
                    </div>
                    <div class="stat-value"><?php echo formatarNumero($totalUsers); ?></div>
                    <div class="stat-label">Total de Utilizadores</div>
                    <div
                        class="stat-growth <?php echo $userGrowth > 0 ? 'positive' : ($userGrowth < 0 ? 'negative' : 'neutral'); ?>">
                        <i
                            class="fas fa-arrow-<?php echo $userGrowth > 0 ? 'up' : ($userGrowth < 0 ? 'down' : 'right'); ?>"></i>
                        <?php echo abs($userGrowth); ?>% últimos 30 dias
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-header">
                        <div class="stat-icon active">
                            <i class="fas fa-user-check"></i>
                        </div>
                    </div>
                    <div class="stat-value"><?php echo formatarNumero($activeUsers); ?></div>
                    <div class="stat-label">Utilizadores Ativos (Publicações ou Comentários)</div>
                    <div class="stat-growth neutral">
                        <i class="fas fa-calendar"></i>
                        Últimos 30 dias
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-header">
                        <div class="stat-icon posts">
                            <i class="fas fa-file-alt"></i>
                        </div>
                    </div>
                    <div class="stat-value"><?php echo formatarNumero($totalPosts); ?></div>
                    <div class="stat-label">Total de Publicações</div>
                    <div
                        class="stat-growth <?php echo $postGrowth > 0 ? 'positive' : ($postGrowth < 0 ? 'negative' : 'neutral'); ?>">
                        <i
                            class="fas fa-arrow-<?php echo $postGrowth > 0 ? 'up' : ($postGrowth < 0 ? 'down' : 'right'); ?>"></i>
                        <?php echo abs($postGrowth); ?>% últimos 30 dias
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-header">
                        <div class="stat-icon comments">
                            <i class="fas fa-comments"></i>
                        </div>
                    </div>
                    <div class="stat-value"><?php echo formatarNumero($totalComments); ?></div>
                    <div class="stat-label">Total de Comentários</div>
                    <div class="stat-growth neutral">
                        <i class="fas fa-chart-line"></i>
                        Interação ativa
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-header">
                        <div class="stat-icon likes">
                            <i class="fas fa-heart"></i>
                        </div>
                    </div>
                    <div class="stat-value"><?php echo formatarNumero($totalLikes); ?></div>
                    <div class="stat-label">Total de Likes</div>
                    <div class="stat-growth positive">
                        <i class="fas fa-thumbs-up"></i>
                        Interações positivas
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-header">
                        <div class="stat-icon messages">
                            <i class="fas fa-envelope"></i>
                        </div>
                    </div>
                    <div class="stat-value"><?php echo formatarNumero($totalMessages); ?></div>
                    <div class="stat-label">Total de Mensagens</div>
                    <div class="stat-growth neutral">
                        <i class="fas fa-comments"></i>
                        <?php echo formatarNumero($messageStats['total_conversas']); ?> conversas
                    </div>
                </div>
            </div>

            <!-- Estatísticas Detalhadas -->
            <div class="detailed-stats">
                <!-- Top Utilizadores -->
                <div class="detail-card">
                    <h3><i class="fas fa-trophy"></i> Top Utilizadores Ativos</h3>
                    <div class="user-list">
                        <?php foreach ($topUsers as $user): ?>
                            <div class="user-item">
                                <div class="user-info">
                                    <h4><?php echo htmlspecialchars($user['nome_completo']); ?></h4>
                                    <p>@<?php echo htmlspecialchars($user['nick']); ?></p>
                                </div>
                                <div class="user-stats">
                                    <span><?php echo $user['total_posts']; ?> posts</span>
                                    <span><?php echo $user['total_comments']; ?> comentários</span>
                                    <span><?php echo $user['total_likes']; ?> likes</span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Distribuição de Conteúdo -->
                <div class="detail-card">
                    <h3><i class="fas fa-chart-pie"></i> Distribuição de Conteúdo</h3>
                    <div class="metric-row">
                        <span class="metric-label">Publicações Normais</span>
                        <span class="metric-value"><?php echo formatarNumero($postTypes['posts_normais']); ?></span>
                    </div>
                    <div class="metric-row">
                        <span class="metric-label">Polls</span>
                        <span class="metric-value"><?php echo formatarNumero($postTypes['polls']); ?></span>
                    </div>
                    <div class="metric-row">
                        <span class="metric-label">Total de Mídias</span>
                        <span class="metric-value"><?php echo formatarNumero($mediaStats['total_medias']); ?></span>
                    </div>
                    <div class="metric-row">
                        <span class="metric-label">Imagens</span>
                        <span class="metric-value"><?php echo formatarNumero($mediaStats['total_imagens']); ?></span>
                    </div>
                    <div class="metric-row">
                        <span class="metric-label">Vídeos</span>
                        <span class="metric-value"><?php echo formatarNumero($mediaStats['total_videos']); ?></span>
                    </div>
                </div>

                <!-- Tipos de Utilizador -->
                <div class="detail-card">
                    <h3><i class="fas fa-user-cog"></i> Tipos de Utilizador</h3>
                    <?php foreach ($userTypes as $type): ?>
                        <div class="metric-row">
                            <span class="metric-label"><?php echo ucfirst($type['tipo_utilizador']); ?></span>
                            <span class="metric-value"><?php echo $type['total']; ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Atividade Semanal -->
                <div class="detail-card">
                    <h3><i class="fas fa-calendar-week"></i> Atividade por Dia da Semana</h3>
                    <div class="chart-container">
                        <canvas id="weeklyChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- Métricas Adicionais -->
            <div class="detail-card">
                <h3><i class="fas fa-info-circle"></i> Métricas Adicionais</h3>
                <div class="metric-row">
                    <span class="metric-label">Média de Caracteres por Mensagem</span>
                    <span class="metric-value"><?php echo round($messageStats['tamanho_medio_mensagem']); ?></span>
                </div>
                <div class="metric-row">
                    <span class="metric-label">Novos Utilizadores (30 dias)</span>
                    <span class="metric-value"><?php echo $newUsers; ?></span>
                </div>
                <div class="metric-row">
                    <span class="metric-label">Novas Publicações (30 dias)</span>
                    <span class="metric-value"><?php echo $newPosts; ?></span>
                </div>
                <div class="metric-row">
                    <span class="metric-label">Taxa de Interação</span>
                    <span
                        class="metric-value"><?php echo $totalPosts > 0 ? round(($totalLikes + $totalComments) / $totalPosts, 1) : 0; ?></span>
                </div>
            </div>
        </main>
    </div>

    <button class="refresh-btn" onclick="refreshStats()" title="Atualizar Estatísticas">
        <i class="fas fa-sync-alt"></i>
    </button>

    <script>
        // Gráfico de atividade semanal
        const weeklyData = <?php echo json_encode($weeklyActivity); ?>;
        const ctx = document.getElementById('weeklyChart').getContext('2d');

        // Traduzir nomes dos dias
        const dayTranslations = {
            'Monday': 'Segunda',
            'Tuesday': 'Terça',
            'Wednesday': 'Quarta',
            'Thursday': 'Quinta',
            'Friday': 'Sexta',
            'Saturday': 'Sábado',
            'Sunday': 'Domingo'
        };

        const labels = weeklyData.map(item => dayTranslations[item.dia_semana] || item.dia_semana);
        const data = weeklyData.map(item => item.total);

        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Publicações',
                    data: data,
                    backgroundColor: 'rgba(255, 87, 34, 0.8)',
                    borderColor: 'rgba(255, 87, 34, 1)',
                    borderWidth: 2,
                    borderRadius: 8,
                    borderSkipped: false,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(255, 255, 255, 0.1)'
                        },
                        ticks: {
                            color: '#a3a3a3'
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        },
                        ticks: {
                            color: '#a3a3a3'
                        }
                    }
                }
            }
        });

        // Função para atualizar estatísticas
        function refreshStats() {
            const refreshBtn = document.querySelector('.refresh-btn');
            const body = document.body;

            // Adicionar classe de loading
            body.classList.add('loading');
            refreshBtn.innerHTML = '<i class="fas fa-spinner"></i>';

            // Simular carregamento e recarregar página
            setTimeout(() => {
                window.location.reload();
            }, 1000);
        }

        // Auto-refresh a cada 5 minutos
        setInterval(() => {
            console.log('Auto-refreshing stats...');
            // Aqui poderia fazer uma requisição AJAX para atualizar apenas os dados
        }, 300000); // 5 minutos

        // Animação de entrada dos cards
        document.addEventListener('DOMContentLoaded', function () {
            const cards = document.querySelectorAll('.stat-card, .detail-card');
            cards.forEach((card, index) => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(20px)';

                setTimeout(() => {
                    card.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }, index * 100);
            });
        });
    </script>
</body>

</html>