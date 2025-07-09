<?php
session_start();
require "ligabd.php";

header('Content-Type: application/json');

if (!isset($_SESSION['id'])) {
    echo json_encode(['success' => false, 'message' => 'Não autenticado']);
    exit;
}

$currentUserId = $_SESSION['id'];

// Primeiro, verificar quantas pessoas o utilizador atual segue
$sqlMyFollowsCount = "SELECT COUNT(*) as total FROM seguidores WHERE id_seguidor = ?";
$stmtCount = $con->prepare($sqlMyFollowsCount);
$stmtCount->bind_param("i", $currentUserId);
$stmtCount->execute();
$myFollowsCount = $stmtCount->get_result()->fetch_assoc()['total'];

if ($myFollowsCount == 0) {
    // Se não segue ninguém, mostrar utilizadores mais ativos/populares
    $sql = "
        SELECT DISTINCT u.id, u.nome_completo, u.nick, p.foto_perfil, p.ocupacao,
               0 as seguidores_em_comum,
               COUNT(DISTINCT s.id_seguidor) as total_seguidores
        FROM utilizadores u
        LEFT JOIN perfis p ON u.id = p.id_utilizador
        LEFT JOIN seguidores s ON u.id = s.id_seguido
        WHERE u.id != ?
        AND u.id_tipos_utilizador = 0
        GROUP BY u.id, u.nome_completo, u.nick, p.foto_perfil, p.ocupacao
        HAVING total_seguidores > 0
        ORDER BY total_seguidores DESC, RAND()
        LIMIT 5
    ";
    
    $stmt = $con->prepare($sql);
    $stmt->bind_param("i", $currentUserId);
    
} else {
    // Algoritmo principal: pessoas que os meus seguidores seguem
    $sql = "
        SELECT DISTINCT u.id, u.nome_completo, u.nick, p.foto_perfil, p.ocupacao,
               COUNT(DISTINCT common_follows.id_seguidor) as seguidores_em_comum
        FROM utilizadores u
        LEFT JOIN perfis p ON u.id = p.id_utilizador
        -- Encontrar pessoas que os meus seguidores seguem
        JOIN seguidores suggested_follows ON u.id = suggested_follows.id_seguido
        JOIN seguidores my_follows ON suggested_follows.id_seguidor = my_follows.id_seguido
        -- Contar seguidores em comum (pessoas que eu sigo e que também seguem esta pessoa)
        LEFT JOIN seguidores common_follows ON u.id = common_follows.id_seguido
        LEFT JOIN seguidores my_follows_check ON common_follows.id_seguidor = my_follows_check.id_seguido
        WHERE u.id != ?
        AND my_follows.id_seguidor = ?
        AND my_follows_check.id_seguidor = ?
        -- Excluir pessoas que já sigo
        AND NOT EXISTS (
            SELECT 1 FROM seguidores already_following 
            WHERE already_following.id_seguidor = ? 
            AND already_following.id_seguido = u.id
        )
        GROUP BY u.id, u.nome_completo, u.nick, p.foto_perfil, p.ocupacao
        HAVING seguidores_em_comum > 0
        ORDER BY seguidores_em_comum DESC, RAND()
        LIMIT 5
    ";
    
    $stmt = $con->prepare($sql);
    $stmt->bind_param("iiii", $currentUserId, $currentUserId, $currentUserId, $currentUserId);
}

$stmt->execute();
$result = $stmt->get_result();

$suggestions = [];
while ($row = $result->fetch_assoc()) {
    $suggestions[] = [
        'id' => $row['id'],
        'nome_completo' => $row['nome_completo'],
        'nick' => $row['nick'],
        'foto_perfil' => $row['foto_perfil'] ?: 'default-profile.jpg',
        'ocupacao' => $row['ocupacao'] ?: 'Utilizador',
        'seguidores_em_comum' => (int)$row['seguidores_em_comum'],
        'ja_segue' => false
    ];
}

// Se não encontrou sugestões suficientes, complementar com utilizadores ativos
if (count($suggestions) < 5) {
    $excludeIds = array_column($suggestions, 'id');
    $excludeIds[] = $currentUserId;
    $excludePlaceholders = str_repeat('?,', count($excludeIds) - 1) . '?';
    
    $sqlFallback = "
        SELECT DISTINCT u.id, u.nome_completo, u.nick, p.foto_perfil, p.ocupacao,
               0 as seguidores_em_comum
        FROM utilizadores u
        LEFT JOIN perfis p ON u.id = p.id_utilizador
        WHERE u.id NOT IN ($excludePlaceholders)
        AND u.id_tipos_utilizador = 0
        AND NOT EXISTS (
            SELECT 1 FROM seguidores s 
            WHERE s.id_seguidor = ? AND s.id_seguido = u.id
        )
        AND EXISTS (
            SELECT 1 FROM publicacoes pub 
            WHERE pub.id_utilizador = u.id 
            AND pub.data_criacao >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            AND pub.deletado_em = '0000-00-00 00:00:00'
        )
        ORDER BY RAND()
        LIMIT ?
    ";
    
    $remaining = 5 - count($suggestions);
    $stmtFallback = $con->prepare($sqlFallback);
    
    // Preparar parâmetros para bind_param
    $types = str_repeat('i', count($excludeIds)) . 'ii';
    $params = array_merge($excludeIds, [$currentUserId, $remaining]);
    
    $stmtFallback->bind_param($types, ...$params);
    $stmtFallback->execute();
    $resultFallback = $stmtFallback->get_result();
    
    while ($row = $resultFallback->fetch_assoc()) {
        $suggestions[] = [
            'id' => $row['id'],
            'nome_completo' => $row['nome_completo'],
            'nick' => $row['nick'],
            'foto_perfil' => $row['foto_perfil'] ?: 'default-profile.jpg',
            'ocupacao' => $row['ocupacao'] ?: 'Utilizador',
            'seguidores_em_comum' => 0,
            'ja_segue' => false
        ];
    }
}

echo json_encode([
    'success' => true,
    'suggestions' => $suggestions,
    'debug' => [
        'user_follows_count' => $myFollowsCount,
        'algorithm_used' => $myFollowsCount == 0 ? 'popular_users' : 'mutual_connections'
    ]
]);
?>