<?php
include_once("../backend/ligabd.php");
session_start();

// Adicione estas duas linhas
$currentUserId = isset($_SESSION['id']) ? $_SESSION['id'] : 0;
$currentUserType = isset($_SESSION['id_tipos_utilizador']) ? $_SESSION['id_tipos_utilizador'] : 0;

// Função para transformar URLs em links clicáveis (copiada do index.php)
function makeLinksClickable($text)
{
    $pattern = '/(https?:\/\/[^\s]+)/';
    $linkedText = preg_replace($pattern, '<a href="$1" target="_blank" rel="noopener noreferrer">$1</a>', $text);
    return $linkedText;
}

// Função para contar comentários (copiada do index.php)
function getCommentCount($con, $postId)
{
    $sql = "SELECT COUNT(*) as count FROM comentarios WHERE id_publicacao = $postId";
    $result = mysqli_query($con, $sql);
    $data = mysqli_fetch_assoc($result);
    return $data['count'];
}

// Função para verificar se post está salvo
function isPostSaved($con, $userId, $postId)
{
    $sql = "SELECT * FROM publicacao_salvas 
            WHERE utilizador_id = $userId AND publicacao_id = $postId";
    $result = mysqli_query($con, $sql);
    return mysqli_num_rows($result) > 0;
}

// Função para obter mídias da publicação (atualizada para incluir vídeos)
function getPostMedias($con, $postId)
{
    $medias = array();
    $sql = "SELECT url, tipo FROM publicacao_medias WHERE publicacao_id = $postId ORDER BY ordem ASC";
    $result = mysqli_query($con, $sql);

    while ($row = mysqli_fetch_assoc($result)) {
        $medias[] = $row;
    }

    return $medias;
}

// Função para obter dados da poll
function getPollData($con, $postId, $userId = 0)
{
    $sql = "SELECT p.id, p.pergunta, p.data_expiracao, p.total_votos,
                   po.id as opcao_id, po.opcao_texto, po.votos, po.ordem
            FROM polls p
            JOIN poll_opcoes po ON p.id = po.poll_id
            WHERE p.publicacao_id = ?
            ORDER BY po.ordem ASC";
    
    $stmt = $con->prepare($sql);
    $stmt->bind_param("i", $postId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        return null;
    }

    $pollData = null;
    $opcoes = [];
    $userVoted = false;
    $userVotedOption = null;

    while ($row = $result->fetch_assoc()) {
        if (!$pollData) {
            $pollData = [
                'id' => $row['id'],
                'pergunta' => $row['pergunta'],
                'data_expiracao' => $row['data_expiracao'],
                'total_votos' => intval($row['total_votos']),
                'expirada' => strtotime($row['data_expiracao']) < time()
            ];
        }
        
        $opcoes[] = [
            'id' => intval($row['opcao_id']),
            'texto' => $row['opcao_texto'],
            'votos' => intval($row['votos']),
            'percentagem' => $pollData['total_votos'] > 0 ? 
                round((intval($row['votos']) / $pollData['total_votos']) * 100, 1) : 0
        ];
    }

    // Verificar se o usuário já votou
    if ($userId > 0) {
        $sqlUserVote = "SELECT opcao_id FROM poll_votos WHERE poll_id = ? AND utilizador_id = ?";
        $stmtUserVote = $con->prepare($sqlUserVote);
        if ($stmtUserVote) {
            $stmtUserVote->bind_param("ii", $pollData['id'], $userId);
            $stmtUserVote->execute();
            $voteResult = $stmtUserVote->get_result();
            
            if ($voteResult->num_rows > 0) {
                $userVoted = true;
                $voteData = $voteResult->fetch_assoc();
                $userVotedOption = intval($voteData['opcao_id']);
            }
        }
    }

    return [
        'poll' => $pollData,
        'opcoes' => $opcoes,
        'user_voted' => $userVoted,
        'user_voted_option' => $userVotedOption
    ];
}

// Função para formatar tempo restante
function formatTimeLeft($expirationDate)
{
    $now = new DateTime();
    $expDate = new DateTime($expirationDate);
    $diff = $expDate->getTimestamp() - $now->getTimestamp();
    
    if ($diff <= 0) return 'Poll encerrada';
    
    $hours = floor($diff / 3600);
    $minutes = floor(($diff % 3600) / 60);
    
    if ($hours > 24) {
        $days = floor($hours / 24);
        return $days . 'd ' . ($hours % 24) . 'h';
    }
    
    return $hours . 'h ' . $minutes . 'm';
}

$termo = isset($_GET['pesquisa']) ? trim($_GET['pesquisa']) : '';
$termo_sql = mysqli_real_escape_string($con, $termo);
$userId = isset($_SESSION['id']) ? $_SESSION['id'] : 0;
$excludeSelf = $userId ? "AND u.id != $userId" : "";
?>
<!DOCTYPE html>
<html lang="pt">

<head>
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pesquisar - Orange</title>
    <link rel="stylesheet" href="css/style_index.css">
    <link rel="stylesheet" href="css/style_pesquisar.css">
    <link rel="stylesheet" href="css/style_polls.css">
    <link rel="icon" type="image/x-icon" href="images/favicon/favicon_orange.png">
    <link rel="stylesheet" href="css/app.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

</head>

<style>
    /* Estilos para o grid de mídias */
    .post-images {
        margin: 15px 0;
    }

    .images-grid {
        display: grid;
        gap: 5px;
        border-radius: 8px;
        overflow: hidden;
    }

    .images-grid.single {
        grid-template-columns: 1fr;
    }

    .images-grid.double {
        grid-template-columns: 1fr 1fr;
    }

    .images-grid.triple {
        grid-template-columns: 1fr 1fr;
        grid-template-rows: 1fr 1fr;
    }

    .images-grid.triple .media-item:first-child {
        grid-row: span 2;
    }

    .images-grid.quad {
        grid-template-columns: 1fr 1fr;
        grid-template-rows: 1fr 1fr;
    }

    .images-grid.multiple {
        grid-template-columns: 1fr 1fr;
        grid-template-rows: 1fr 1fr;
    }

    .media-item {
        position: relative;
        cursor: pointer;
        overflow: hidden;
    }

    .media-item img,
    .media-item video {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform 0.3s ease;
    }

    .media-item:hover img,
    .media-item:hover video {
        transform: scale(1.05);
    }

    .more-images-overlay {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0, 0, 0, 0.5);
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 2rem;
        font-weight: bold;
    }

    .video-container {
        position: relative;
        width: 100%;
        height: 100%;
    }

    .video-container video {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    /* Modal de mídia expandida */
    .image-modal {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background-color: rgba(0, 0, 0, 0.9);
        z-index: 1000;
        align-items: center;
        justify-content: center;
    }

    .image-modal-content {
        position: relative;
        width: 90%;
        max-width: 800px;
        max-height: 90vh;
    }

    .close-image-modal {
        position: absolute;
        top: 15px;
        right: 15px;
        color: white;
        font-size: 30px;
        cursor: pointer;
        background: none;
        border: none;
        z-index: 10;
    }

    .modal-image-container {
        width: 100%;
        height: 100%;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .modal-media {
        max-width: 100%;
        max-height: 80vh;
        object-fit: contain;
    }

    .no-comments {
        text-align: center;
        padding: 20px;
        color: var(--text-secondary);
        font-style: italic;
        border-top: 1px solid var(--border-light);
        margin-top: 15px;
    }

    .image-modal-nav {
        position: fixed;
        bottom: 20px;
        left: 0;
        right: 0;
        display: flex;
        justify-content: center;
        align-items: center;
        gap: 20px;
    }

    .modal-nav-btn {
        background: rgba(255, 255, 255, 0.2);
        border: none;
        color: white;
        width: 40px;
        height: 40px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: background 0.2s ease;
    }

    .modal-nav-btn:hover {
        background: rgba(255, 255, 255, 0.4);
    }

    .modal-nav-btn:disabled {
        opacity: 0.5;
        cursor: not-allowed;
    }

    .image-counter {
        color: white;
        font-size: 1rem;
        min-width: 60px;
        text-align: center;
    }

    /* Estilos para perfis */
    .profile-card {
        display: flex;
        align-items: center;
        gap: 15px;
        padding: 15px;
        background: var(--bg-card);
        border-radius: 12px;
        margin-bottom: 10px;
        transition: transform 0.2s ease;
    }

    .profile-card:hover {
        transform: translateY(-2px);
    }

    .profile-img {
        width: 60px;
        height: 60px;
        border-radius: 50%;
        object-fit: cover;
        border: 2px solid var(--color-primary);
        box-shadow: var(--shadow-sm);
    }

    .profile-info {
        flex: 1;
    }

    .profile-info h4 {
        margin: 0;
        font-size: 1.1rem;
        color: var(--text-light);
    }

    .profile-info p {
        margin: 4px 0;
        color: var(--text-secondary);
        font-size: 0.9rem;
    }

    .profile-info small {
        color: var(--text-muted);
        font-size: 0.8rem;
    }

    .follow-btn {
        background: var(--color-primary);
        color: white;
        border: none;
        border-radius: 20px;
        padding: 8px 16px;
        cursor: pointer;
        transition: all 0.2s ease;
        font-size: 0.9rem;
    }

    .follow-btn.following {
        background: var(--bg-card);
        color: var(--color-primary);
        border: 1px solid var(--color-primary);
    }

    /* Estilos para o modal de confirmação */
    .modal-overlay {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background-color: rgba(0, 0, 0, 0.5);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 1001;
    }

    .confirmation-modal {
        background-color: var(--bg-card);
        border-radius: 12px;
        padding: 24px;
        width: 90%;
        max-width: 400px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
    }

    .confirmation-modal h3 {
        margin-top: 0;
        color: var(--text-light);
        font-size: 1.2rem;
    }

    .confirmation-modal p {
        margin: 15px 0 25px;
        color: var(--text-secondary);
        line-height: 1.5;
    }

    .confirmation-buttons {
        display: flex;
        justify-content: flex-end;
        gap: 12px;
    }

    .confirmation-buttons button {
        padding: 8px 16px;
        border-radius: 6px;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.2s ease;
    }

    .confirm-btn {
        background-color: var(--color-primary);
        color: white;
        border: none;
    }

    .confirm-btn:hover {
        background-color: var(--color-primary-dark);
    }

    .cancel-btn {
        background-color: transparent;
        color: var(--text-secondary);
        border: 1px solid var(--border-light);
    }

    .cancel-btn:hover {
        background-color: var(--bg-input);
    }

    .post-actions {
        display: flex;
        align-items: center;
        gap: 15px;
        padding-top: 10px;
        border-top: 1px solid var(--border-light);
        margin-top: 10px;
    }

    /* Estilos para botões de apagar */
    .post-actions .delete-btn {
        background: none;
        border: none;
        color: var(--text-secondary);
        cursor: pointer;
        margin-left: auto;
        padding: 5px;
        transition: color 0.2s ease;
    }

    .post-actions .delete-btn:hover {
        color: #ff3333;
    }

    .comment-item .delete-comment-btn {
        background: none;
        border: none;
        color: var(--text-secondary);
        cursor: pointer;
        margin-left: 10px;
        padding: 2px;
        font-size: 0.8rem;
        transition: color 0.2s ease;
    }

    .comment-item .delete-comment-btn:hover {
        color: #ff3333;
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
        display: flex;
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

    .btn-load-more {
        display: block;
        width: 100%;
        padding: 10px;
        background: var(--color-primary);
        color: white;
        border: none;
        border-radius: 6px;
        margin-top: 10px;
        cursor: pointer;
        transition: background 0.2s ease;
    }

    .btn-load-more:hover {
        background: var(--color-primary-dark);
    }
</style>


<body>

    <!-- Modal de confirmação -->
    <div id="confirmationModal" class="modal-overlay" style="display: none;">
        <div class="confirmation-modal">
            <h3>Confirmar ação</h3>
            <p id="confirmationMessage">Tem a certeza que deseja apagar esta publicação?</p>
            <div class="confirmation-buttons">
                <button id="confirmCancel" class="cancel-btn">Cancelar</button>
                <button id="confirmAction" class="confirm-btn">Confirmar</button>
            </div>
        </div>
    </div>

    <!-- Modal de mídia expandida -->
    <div id="imageModal" class="image-modal">
        <div class="image-modal-content">
            <button class="close-image-modal">&times;</button>
            <div id="modalImage" class="modal-image-container"></div>
        </div>
        <div class="image-modal-nav">
            <button id="prevImageBtn" class="modal-nav-btn">
                <i class="fas fa-chevron-left"></i>
            </button>
            <span id="imageCounter" class="image-counter">1 / 1</span>
            <button id="nextImageBtn" class="modal-nav-btn">
                <i class="fas fa-chevron-right"></i>
            </button>
        </div>
    </div>
    <?php require "parciais/header.php" ?>

    <!-- Comments Modal -->
    <div id="commentsModal" class="modal-overlay" style="display: none; z-index: 1000;">
        <div class="comment-modal">
            <div class="modal-post" id="modalPostContent">
                <!-- Conteúdo será preenchido via JS -->
            </div>
            <div class="modal-comments">
                <div class="comments-list" id="commentsList">
                    <!-- Comentários serão carregados aqui -->
                </div>
                <form class="comment-form" id="commentForm">
                    <input type="hidden" id="currentPostId" value="">
                    <input type="text" class="comment-input" id="commentInput" placeholder="Adicione um comentário..."
                        required>
                    <button type="submit" class="comment-submit">Publicar</button>
                </form>
            </div>
            <button class="close-button">
                <i class="fas fa-times"></i>
            </button>
        </div>
    </div>

    <!-- Toast Notification -->
    <div id="toast" class="toast">
        <div class="toast-icon">
            <i class="fas fa-bookmark"></i>
        </div>
        <div class="toast-content">
            <p id="toast-message">Mensagem aqui</p>
        </div>
    </div>

    <div class="container">
        <?php require("parciais/sidebar.php"); ?>

        <main class="feed">
            <!-- PERFIS -->
            <section class="mb-4">
                <h3 class="mb-2">Perfis relacionados</h3>
                <div class="profile-results" id="profileResults">
                    <?php
                    $sqlPerfis = "SELECT u.id, u.nome_completo, u.nick, p.foto_perfil,
                    (SELECT COUNT(*) FROM seguidores WHERE id_seguido = u.id) AS seguidores,
                    (SELECT COUNT(*) FROM seguidores WHERE id_seguidor = u.id) AS a_seguir,
                    " . ($userId ?
                        "(SELECT COUNT(*) FROM seguidores 
                        WHERE id_seguidor = $userId AND id_seguido = u.id) AS is_following"
                        : "0 AS is_following") . "
                    FROM utilizadores u
                    JOIN perfis p ON u.id = p.id_utilizador
                    WHERE (u.nome_completo LIKE '%$termo_sql%' 
                        OR u.nick LIKE '%$termo_sql%' 
                        OR p.biografia LIKE '%$termo_sql%')
                        $excludeSelf
                    LIMIT 3";
                    $resPerfis = mysqli_query($con, $sqlPerfis);

                    if (mysqli_num_rows($resPerfis) > 0) {
                        while ($perfil = mysqli_fetch_assoc($resPerfis)) {
                            ?>

                            <div class="profile-card">
                                <a href="perfil.php?id=<?php echo $perfil['id']; ?>" class="profile-link">
                                    <img src="images/perfil/<?php echo htmlspecialchars($perfil['foto_perfil']); ?>"
                                        class="profile-img">
                                    <div class="profile-info">
                                        <h4><?php echo htmlspecialchars($perfil['nome_completo']); ?></h4>
                                        <p>@<?php echo htmlspecialchars($perfil['nick']); ?></p>
                                        <small>
                                            <strong><?php echo $perfil['seguidores']; ?></strong> seguidores |
                                            <strong><?php echo $perfil['a_seguir']; ?></strong> a seguir
                                        </small>
                                    </div>
                                </a>
                                <?php if ($userId && $userId != $perfil['id']) { ?>
                                    <button class="follow-btn <?php echo $perfil['is_following'] ? 'following' : ''; ?>"
                                        data-user-id="<?php echo $perfil['id']; ?>">
                                        <?php echo $perfil['is_following'] ? 'Seguindo' : 'Seguir'; ?>
                                    </button>
                                <?php } ?>
                            </div>
                            <?php
                        }
                    } else {
                        echo "<p class='no-posts'>Nenhum perfil encontrado.</p>";
                    }

                    // Verificar se há mais perfis
                    $sqlCount = "SELECT COUNT(*) as total FROM utilizadores u
                                JOIN perfis p ON u.id = p.id_utilizador
                                WHERE u.nome_completo LIKE '%$termo_sql%' 
                                    OR u.nick LIKE '%$termo_sql%' 
                                    OR p.biografia LIKE '%$termo_sql%'";
                    $resCount = mysqli_query($con, $sqlCount);
                    $totalPerfis = mysqli_fetch_assoc($resCount)['total'];
                    $offset = 3; // Já mostramos 3
                    
                    // Botão "Ver mais" fora da div de resultados
                    if ($totalPerfis > 3) {
                        echo '<button id="loadMoreProfiles" class="btn-load-more">Ver mais</button>';
                    }
                    ?>
                </div>
            </section>

            <!-- PUBLICACOES -->
            <section>
                <h3 class="mb-2">Publicações relacionadas</h3>
                <div class="posts">
                    <?php
                    // Consulta atualizada para incluir polls
                    $sqlPosts = "SELECT p.id_publicacao, p.conteudo, p.data_criacao, p.likes, p.tipo,
                        u.id AS id_utilizador, u.nick, 
                        pr.foto_perfil, pr.ocupacao,
                        " . ($userId ?
                        "(SELECT COUNT(*) FROM publicacao_likes 
                            WHERE publicacao_id = p.id_publicacao AND utilizador_id = $userId) AS user_liked,
                            (SELECT COUNT(*) FROM publicacao_salvas 
                            WHERE publicacao_id = p.id_publicacao AND utilizador_id = $userId) AS user_saved"
                        : "0 AS user_liked, 0 AS user_saved") . "
                        FROM publicacoes p
                        JOIN utilizadores u ON p.id_utilizador = u.id
                        LEFT JOIN perfis pr ON pr.id_utilizador = u.id
                        LEFT JOIN publicacao_medias pm ON pm.publicacao_id = p.id_publicacao
                        LEFT JOIN polls pol ON pol.publicacao_id = p.id_publicacao
                        WHERE p.deletado_em = '0000-00-00 00:00:00' 
                        AND (p.conteudo LIKE '%$termo_sql%' 
                             OR pm.url LIKE '%$termo_sql%'
                             OR pol.pergunta LIKE '%$termo_sql%')
                        GROUP BY p.id_publicacao
                        ORDER BY p.data_criacao DESC";
                    $resPosts = mysqli_query($con, $sqlPosts);

                    if (mysqli_num_rows($resPosts) > 0) {
                        while ($post = mysqli_fetch_assoc($resPosts)) {
                            $publicacaoId = $post['id_publicacao'];
                            $foto = $post['foto_perfil'] ?? 'default-profile.jpg';
                            $ocupacao = $post['ocupacao'] ?? 'Utilizador';
                            $likedClass = $post['user_liked'] ? 'liked' : '';
                            $savedClass = $post['user_saved'] ? 'saved' : '';
                            $commentCount = getCommentCount($con, $publicacaoId);
                            $postMedias = getPostMedias($con, $publicacaoId);
                            $pollData = null;
                            
                            // Se for uma poll, buscar dados da poll
                            if ($post['tipo'] === 'poll') {
                                $pollData = getPollData($con, $publicacaoId, $userId);
                            }
                            ?>
                            <article class="post" data-post-id="<?php echo $publicacaoId; ?>">
                                <div class="post-header">
                                    <a href="perfil.php?id=<?php echo $post['id_utilizador']; ?>">
                                        <img src="images/perfil/<?php echo htmlspecialchars($foto); ?>" class="profile-pic">
                                    </a>
                                    <div class="post-info">
                                        <div>
                                            <a href="perfil.php?id=<?php echo $post['id_utilizador']; ?>" class="profile-link">
                                                <h3><?php echo htmlspecialchars($post['nick']); ?></h3>
                                            </a>
                                            <p><?php echo htmlspecialchars($ocupacao); ?></p>
                                        </div>
                                        <span
                                            class="timestamp"><?php echo date('d/m/Y H:i', strtotime($post['data_criacao'])); ?></span>
                                    </div>

                                </div>
                                <div class="post-content">
                                    <p><?php echo nl2br(makeLinksClickable($post['conteudo'])); ?></p>
                                </div>

                                <!-- Exibir poll se for uma publicação de poll -->
                                <?php if ($post['tipo'] === 'poll' && $pollData): ?>
                                    <div class="poll-container" data-poll-id="<?php echo $pollData['poll']['id']; ?>">
                                        <div class="poll-question"><?php echo htmlspecialchars($pollData['poll']['pergunta']); ?></div>
                                        
                                        <div class="poll-options">
                                            <?php foreach ($pollData['opcoes'] as $opcao): ?>
                                                <div class="poll-option <?php echo ($pollData['user_voted'] || $pollData['poll']['expirada']) ? 'disabled voted' : ''; ?> <?php echo ($pollData['user_voted_option'] == $opcao['id']) ? 'user-voted' : ''; ?>" 
                                                     data-opcao-id="<?php echo $opcao['id']; ?>"
                                                     <?php if (!$pollData['user_voted'] && !$pollData['poll']['expirada']): ?>
                                                         onclick="voteInPoll(<?php echo $pollData['poll']['id']; ?>, <?php echo $opcao['id']; ?>)"
                                                     <?php endif; ?>>
                                                    <div class="poll-option-progress" style="width: <?php echo $opcao['percentagem']; ?>%"></div>
                                                    <div class="poll-option-content">
                                                        <span class="poll-option-text"><?php echo htmlspecialchars($opcao['texto']); ?></span>
                                                        <?php if ($pollData['user_voted'] || $pollData['poll']['expirada']): ?>
                                                            <div class="poll-option-stats">
                                                                <span class="poll-option-percentage"><?php echo $opcao['percentagem']; ?>%</span>
                                                                <span class="poll-option-votes"><?php echo $opcao['votos']; ?> voto<?php echo $opcao['votos'] !== 1 ? 's' : ''; ?></span>
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                        
                                        <div class="poll-meta">
                                            <span class="poll-total-votes"><?php echo $pollData['poll']['total_votos']; ?> voto<?php echo $pollData['poll']['total_votos'] !== 1 ? 's' : ''; ?></span>
                                            <span class="poll-time-left <?php echo $pollData['poll']['expirada'] ? 'poll-expired' : ''; ?>">
                                                <i class="fas fa-clock"></i>
                                                <?php echo $pollData['poll']['expirada'] ? 'Poll encerrada' : 'Encerra em ' . formatTimeLeft($pollData['poll']['data_expiracao']); ?>
                                            </span>
                                        </div>
                                    </div>
                                <?php endif; ?>

                                <!-- Exibir mídias da publicação -->
                                <?php if (!empty($postMedias)): ?>
                                    <div class="post-images">
                                        <?php
                                        $mediaCount = count($postMedias);
                                        $gridClass = '';
                                        if ($mediaCount == 1)
                                            $gridClass = 'single';
                                        elseif ($mediaCount == 2)
                                            $gridClass = 'double';
                                        elseif ($mediaCount == 3)
                                            $gridClass = 'triple';
                                        elseif ($mediaCount == 4)
                                            $gridClass = 'quad';
                                        else
                                            $gridClass = 'multiple';
                                        ?>
                                        <div class="images-grid <?php echo $gridClass; ?>">
                                            <?php foreach ($postMedias as $i => $media): ?>
                                                <?php if ($i < 4 || $mediaCount <= 4): ?>
                                                    <div class="media-item"
                                                        onclick="openMediaModal(<?php echo $publicacaoId; ?>, <?php echo $i; ?>)">
                                                        <?php if ($media['tipo'] === 'video'): ?>
                                                            <div class="video-container">
                                                                <video muted preload="metadata" playsInline>
                                                                    <source
                                                                        src="images/publicacoes/<?php echo htmlspecialchars($media['url']); ?>"
                                                                        type="video/mp4">
                                                                    Seu navegador não suporta vídeos.
                                                                </video>
                                                            </div>
                                                        <?php else: ?>
                                                            <img src="images/publicacoes/<?php echo htmlspecialchars($media['url']); ?>"
                                                                alt="Imagem da publicação" class="post-media">
                                                        <?php endif; ?>
                                                        <?php if ($i == 3 && $mediaCount > 4): ?>
                                                            <div class="more-images-overlay">
                                                                +<?php echo $mediaCount - 4; ?>
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>
                                                <?php endif; ?>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                <?php endif; ?>

                                <div class="post-actions">
                                    <button class="like-btn <?php echo $likedClass; ?>"
                                        data-publicacao-id="<?php echo $publicacaoId; ?>">
                                        <i class="fas fa-thumbs-up"></i>
                                        <span class="like-count"><?php echo $post['likes']; ?></span>
                                    </button>
                                    <button class="comment-btn" onclick="openCommentsModal(<?php echo $publicacaoId; ?>)">
                                        <i class="fas fa-comment"></i>
                                        <span class="comment-count"><?php echo $commentCount; ?></span>
                                    </button>
                                    <button><i class="fas fa-share"></i></button>
                                    <button class="save-btn <?php echo $savedClass; ?>"
                                        data-publicacao-id="<?php echo $publicacaoId; ?>">
                                        <i class="fas fa-bookmark"></i>
                                    </button>
                                    <?php if ($currentUserId && ($currentUserId == $post['id_utilizador'] || $currentUserType == 2)) { ?>
                                        <button class="delete-btn" onclick="deletePost(<?php echo $publicacaoId; ?>, this)">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    <?php } ?>
                                </div>
                            </article>
                            <?php
                        }
                    } else {
                        echo "<p class='no-posts'>Nenhuma publicação encontrada.</p>";
                    }
                    ?>
                </div>
            </section>
        </main>
        <!-- Sugestões de Utilizadores -->
        <?php require("parciais/suggestions.php"); ?>


        <script>
            // Função para votar em uma poll
            async function voteInPoll(pollId, opcaoId) {
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
                        updatePollDisplay(pollId, data);
                        showToast('Voto registado com sucesso!');
                    } else {
                        showToast(data.message || 'Erro ao votar');
                    }
                } catch (error) {
                    console.error('Erro ao votar:', error);
                    showToast('Erro de conexão');
                } finally {
                    if (optionElement) {
                        optionElement.classList.remove('voting');
                    }
                }
            }

            function updatePollDisplay(pollId, data) {
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

                        // Destacar opção do usuário
                        if (opcao.id === data.user_voted_option) {
                            optionElement.classList.add('user-voted');
                        }

                        // Mostrar estatísticas se não estavam visíveis
                        if (!optionElement.querySelector('.poll-option-stats')) {
                            const statsDiv = document.createElement('div');
                            statsDiv.className = 'poll-option-stats';
                            statsDiv.innerHTML = `
                                <span class="poll-option-percentage">${opcao.percentagem}%</span>
                                <span class="poll-option-votes">${opcao.votos} voto${opcao.votos !== 1 ? 's' : ''}</span>
                            `;
                            optionElement.querySelector('.poll-option-content').appendChild(statsDiv);
                        }
                    }
                });

                // Atualizar total de votos
                const totalVotesElement = pollContainer.querySelector('.poll-total-votes');
                if (totalVotesElement) {
                    totalVotesElement.textContent = `${data.total_votos} voto${data.total_votos !== 1 ? 's' : ''}`;
                }
            }

            // Variáveis globais para controle da confirmação
            let pendingDelete = {
                id: null,
                element: null,
                type: null // 'post' ou 'comment'
            };

            // Função para mostrar o modal de confirmação (reutilizável)
            function showConfirmation(callback) {
                const modal = document.getElementById('confirmationModal');
                const confirmBtn = document.getElementById('confirmAction');
                const cancelBtn = document.getElementById('confirmCancel');

                modal.style.display = 'flex';
                document.body.style.overflow = 'hidden';

                // Limpa listeners anteriores
                confirmBtn.onclick = null;
                cancelBtn.onclick = null;

                confirmBtn.onclick = function () {
                    modal.style.display = 'none';
                    document.body.style.overflow = 'auto';
                    callback(true);
                };

                cancelBtn.onclick = function () {
                    modal.style.display = 'none';
                    document.body.style.overflow = 'auto';
                    callback(false);
                };
            }

            // Função para apagar publicação com modal de confirmação
            function deletePost(postId, element) {
                pendingDelete = {
                    id: postId,
                    element: element,
                    type: 'post'
                };

                document.getElementById('confirmationMessage').textContent = 'Tem certeza que deseja apagar esta publicação?';
                showConfirmation(function (confirmed) {
                    if (confirmed) {
                        fetch('../backend/delete_post.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded',
                            },
                            body: `id_publicacao=${postId}`
                        })
                            .then(response => response.json())
                            .then(data => {
                                if (data.success) {
                                    // Remove o elemento da publicação do DOM
                                    element.closest('.post').style.opacity = '0';
                                    element.closest('.post').style.transform = 'translateX(-100px)';
                                    setTimeout(() => {
                                        element.closest('.post').remove();
                                    }, 300);
                                    showToast('Publicação apagada com sucesso');
                                } else {
                                    showToast('Erro ao apagar publicação');
                                }
                            })
                            .catch(error => {
                                console.error('Error:', error);
                                showToast('Erro ao apagar publicação');
                            });
                    }
                });
            }

            // Função para apagar comentário com modal de confirmação
            function deleteComment(commentId, element) {
                pendingDelete = {
                    id: commentId,
                    element: element,
                    type: 'comment'
                };

                document.getElementById('confirmationMessage').textContent = 'Tem a certeza que deseja apagar este comentário?';
                showConfirmation(function (confirmed) {
                    if (confirmed) {
                        fetch('../backend/delete_comment.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded',
                            },
                            body: `id_comentario=${commentId}`
                        })
                            .then(response => response.json())
                            .then(data => {
                                if (data.success) {
                                    // Remove o elemento do comentário do DOM
                                    element.closest('.comment-item').remove();
                                    showToast('Comentário apagado com sucesso');

                                    // Atualiza a contagem de comentários
                                    const commentCount = document.querySelector(`.comment-btn[onclick*="${currentPostId}"] .comment-count`);
                                    if (commentCount) {
                                        commentCount.textContent = parseInt(commentCount.textContent) - 1;
                                    }
                                } else {
                                    showToast('Erro ao apagar comentário');
                                }
                            })
                            .catch(error => {
                                console.error('Error:', error);
                                showToast('Erro ao apagar comentário');
                            });
                    }
                });
            }

            // Like functionality
            document.querySelectorAll('.like-btn').forEach(button => {
                button.addEventListener('click', function () {
                    const publicacaoId = this.getAttribute('data-publicacao-id');
                    const likeCount = this.querySelector('.like-count');

                    fetch('../backend/like.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: `id_publicacao=${publicacaoId}`
                    })
                        .then(response => response.text())
                        .then(data => {
                            if (data === 'liked') {
                                this.classList.add('liked');
                                likeCount.textContent = parseInt(likeCount.textContent) + 1;
                            } else if (data === 'unliked') {
                                this.classList.remove('liked');
                                likeCount.textContent = parseInt(likeCount.textContent) - 1;
                            }
                        })
                        .catch(error => console.error('Error:', error));
                });
            });


            // Referências para o modal e o botão de fechar
            const modal = document.getElementById('commentsModal');
            const closeButton = modal.querySelector('.close-button');

            // Função para fechar o modal
            function closeModal() {
                modal.style.display = 'none';
                document.body.style.overflow = 'auto';
            }

            // Evento para o botão de fechar
            closeButton.addEventListener('click', closeModal);

            // Fechar modal ao clicar fora
            modal.addEventListener('click', (e) => {
                if (e.target === modal) {
                    closeModal();
                }
            });

            // Variável global para armazenar o ID da publicação atual
            let currentPostId = null;

            // Função para abrir o modal de comentários
            function openCommentsModal(postId) {
                currentPostId = postId;

                fetch(`../backend/get_post.php?id=${postId}`)
                    .then(response => response.json())
                    .then(post => {
                        const dataCriacao = new Date(post.data_criacao);
                        const dataFormatada = `${dataCriacao.getDate().toString().padStart(2, '0')}-${(dataCriacao.getMonth() + 1).toString().padStart(2, '0')}-${dataCriacao.getFullYear()} ${dataCriacao.getHours().toString().padStart(2, '0')}:${dataCriacao.getMinutes().toString().padStart(2, '0')}`;

                        // Adicione a classe 'post-content' para manter a formatação
                        document.getElementById('modalPostContent').innerHTML = `
        <div class="post">
          <div class="post-header">
            <a href="perfil.php?id=${post.id_utilizador}">
              <img src="images/perfil/${post.foto_perfil || 'default-profile.jpg'}" alt="User" class="profile-pic">
            </a>
            <div class="post-info">
              <a href="perfil.php?id=${post.id_utilizador}" class="profile-link">
                <h3>${post.nick}</h3>
              </a>
              <p>${post.ocupacao || 'Utilizador'}</p>
              <span class="timestamp">${dataFormatada}</span>
            </div>
          </div>
          <div class="post-content">
            <p>${post.conteudo.replace(/\n/g, '<br>')}</p> <!-- Mantém quebras de linha -->
          </div>
        </div>
      `;

                        loadComments(postId);
                    });

                document.getElementById('currentPostId').value = postId;
                modal.style.display = 'flex';
                document.body.style.overflow = 'hidden';
            }

            // Função para carregar comentários
            function loadComments(postId) {
                fetch(`../backend/get_comments.php?post_id=${postId}`)
                    .then(response => response.json())
                    .then(comments => {
                        const commentsList = document.getElementById('commentsList');
                        commentsList.innerHTML = '';

                        // Adiciona mensagem quando não há comentários
                        if (comments.length === 0) {
                            const noCommentsMsg = document.createElement('div');
                            noCommentsMsg.className = 'no-comments';
                            noCommentsMsg.textContent = 'Ainda sem comentários. Seja o primeiro a comentar!';
                            commentsList.appendChild(noCommentsMsg);
                            return;
                        }

                        comments.forEach(comment => {
                            const dataComentario = new Date(comment.data);
                            const dataComentarioFormatada = `${dataComentario.getDate().toString().padStart(2, '0')}-${(dataComentario.getMonth() + 1).toString().padStart(2, '0')}-${dataComentario.getFullYear()} ${dataComentario.getHours().toString().padStart(2, '0')}:${dataComentario.getMinutes().toString().padStart(2, '0')}`;

                            const commentItem = document.createElement('div');
                            commentItem.className = 'comment-item';
                            commentItem.innerHTML = `
                    <a href="perfil.php?id=${comment.utilizador_id}">
                        <img src="images/perfil/${comment.foto_perfil || 'default-profile.jpg'}" alt="User" class="comment-avatar">
                    </a>
                    <div class="comment-content">
                        <div class="comment-header">
                            <div class="comment-user-info">
                                <a href="perfil.php?id=${comment.utilizador_id}" class="profile-link">
                                    <span class="comment-username">${comment.nick}</span>
                                </a>
                                <span class="comment-time">${dataComentarioFormatada}</span>
                            </div>
                            ${(<?php echo $_SESSION['id']; ?> == comment.utilizador_id || <?php echo $_SESSION['id_tipos_utilizador']; ?> == 2) ?
                                    `<button class="delete-comment-btn" onclick="deleteComment(${comment.id}, this)">
                                    <i class="fas fa-trash"></i>
                                </button>` : ''}
                        </div>
                        <p class="comment-text">${comment.conteudo}</p>
                    </div>
                `;
                            commentsList.appendChild(commentItem);
                        });
                    });
            }

            // Função para mostrar toast
            function showToast(message) {
                const toast = document.getElementById('toast');
                const toastMessage = document.getElementById('toast-message');
                toastMessage.textContent = message;

                // Mostrar o toast
                toast.style.display = 'flex';
                setTimeout(() => {
                    toast.classList.add('show');
                }, 10);

                // Esconder após 3 segundos
                setTimeout(() => {
                    toast.classList.remove('show');
                    setTimeout(() => {
                        toast.style.display = 'none';
                    }, 300);
                }, 3000);
            }

            // Dentro do evento de clique do save-btn:
            document.querySelectorAll('.save-btn').forEach(button => {
                button.addEventListener('click', function () {
                    const publicacaoId = this.getAttribute('data-publicacao-id');
                    const isCurrentlySaved = this.classList.contains('saved');

                    fetch('../backend/save_post.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: `id_publicacao=${publicacaoId}`
                    })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                if (data.action === 'saved') {
                                    this.classList.add('saved');
                                    showToast('Adicionado aos itens salvos');
                                } else {
                                    this.classList.remove('saved');
                                    showToast('Removido dos itens salvos');
                                }
                            }
                        })
                        .catch(error => console.error('Error:', error));
                });
            });


            // Envio de novo comentário
            document.getElementById('commentForm').addEventListener('submit', function (e) {
                e.preventDefault();

                const commentInput = document.getElementById('commentInput');
                const content = commentInput.value.trim();

                if (content && currentPostId) {
                    const formData = new FormData();
                    formData.append('post_id', currentPostId);
                    formData.append('content', content);

                    fetch('../backend/add_comment.php', {
                        method: 'POST',
                        body: formData
                    })
                        .then(response => response.json())
                        .then(result => {
                            if (result.success) {
                                commentInput.value = '';
                                loadComments(currentPostId);

                                // Atualiza contador de comentários
                                const commentCount = document.querySelector(`.comment-btn[onclick*="${currentPostId}"] .comment-count`);
                                if (commentCount) {
                                    commentCount.textContent = parseInt(commentCount.textContent) + 1;
                                }
                            }
                        });
                }
            });

            document.addEventListener('click', function (e) {
                const followBtn = e.target.closest('.follow-btn');
                if (followBtn) {
                    e.preventDefault();
                    const userId = followBtn.getAttribute('data-user-id');
                    const isFollowing = followBtn.classList.contains('following');
                    const profileCard = followBtn.closest('.profile-card');

                    // Criar FormData para enviar
                    const formData = new FormData();
                    formData.append('user_id', userId);

                    fetch('../backend/seguir_alternativo.php', {
                        method: 'POST',
                        body: formData
                    })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                // Atualizar estado do botão
                                if (data.action === 'follow') {
                                    followBtn.classList.add('following');
                                    followBtn.textContent = 'Seguindo';

                                    // Incrementar contador de seguidores
                                    const seguidoresElement = profileCard.querySelector('.profile-info small strong:first-child');
                                    let seguidoresCount = parseInt(seguidoresElement.textContent);
                                    seguidoresElement.textContent = seguidoresCount + 1;
                                } else {
                                    followBtn.classList.remove('following');
                                    followBtn.textContent = 'Seguir';

                                    // Decrementar contador de seguidores
                                    const seguidoresElement = profileCard.querySelector('.profile-info small strong:first-child');
                                    let seguidoresCount = parseInt(seguidoresElement.textContent);
                                    seguidoresElement.textContent = seguidoresCount - 1;
                                }
                            } else {
                                console.error('Erro:', data.message);
                            }
                        })
                        .catch(error => console.error('Error:', error));
                }
            });

            // CORREÇÃO CARREGAR MAIS PERFIS
            document.getElementById('loadMoreProfiles')?.addEventListener('click', function () {
                const termo = "<?php echo $termo; ?>";
                const currentCount = document.querySelectorAll('.profile-card').length;

                fetch(`../backend/load_more_profiles.php?termo=${termo}&offset=${currentCount}`)
                    .then(response => response.text())
                    .then(html => {
                        if (html.trim() === '') {
                            this.style.display = 'none';
                        } else {
                            // Inserir antes do botão
                            this.insertAdjacentHTML('beforebegin', html);
                        }
                    });
            });

            // Tornar cards de perfil clicáveis
            document.querySelectorAll('.profile-card').forEach(card => {
                card.addEventListener('click', function (e) {
                    // Só redireciona se não foi clicado diretamente no botão de seguir
                    if (!e.target.closest('.follow-btn')) {
                        const link = this.querySelector('.profile-link');
                        if (link) {
                            window.location.href = link.href;
                        }
                    }
                });
            });
        </script>
</body>

</html>