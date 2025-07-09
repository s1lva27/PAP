<?php
session_start();
require "ligabd.php";

header('Content-Type: application/json');

if (!isset($_SESSION['id'])) {
    echo json_encode(['success' => false, 'message' => 'Não autenticado']);
    exit;
}

$currentUserId = $_SESSION['id'];

// Algoritmo inteligente de sugestões
$sql = "
    SELECT DISTINCT u.id, u.nome_completo, u.nick, p.foto_perfil, p.ocupacao,
           COUNT(DISTINCT s2.id_seguidor) as seguidores_em_comum,
           CASE 
               WHEN EXISTS(
                   SELECT 1 FROM seguidores s3 
                   WHERE s3.id_seguidor = ? AND s3.id_seguido = u.id
               ) THEN 1 
               ELSE 0 
           END as ja_segue
    FROM utilizadores u
    LEFT JOIN perfis p ON u.id = p.id_utilizador
    LEFT JOIN seguidores s1 ON u.id = s1.id_seguido
    LEFT JOIN seguidores s2 ON s1.id_seguidor = s2.id_seguido
    LEFT JOIN seguidores my_follows ON my_follows.id_seguidor = ? AND my_follows.id_seguido = s2.id_seguidor
    WHERE u.id != ?
    AND u.id != ?
    AND NOT EXISTS (
        SELECT 1 FROM seguidores s 
        WHERE s.id_seguidor = ? AND s.id_seguido = u.id
    )
    AND (
        -- Pessoas que seguem quem eu sigo (conexões de 2º grau)
        my_follows.id_seguidor IS NOT NULL
        OR
        -- Pessoas com atividade recente
        EXISTS (
            SELECT 1 FROM publicacoes pub 
            WHERE pub.id_utilizador = u.id 
            AND pub.data_criacao >= DATE_SUB(NOW(), INTERVAL 7 DAY)
            AND pub.deletado_em = '0000-00-00 00:00:00'
        )
        OR
        -- Pessoas que interagiram comigo recentemente
        EXISTS (
            SELECT 1 FROM publicacao_likes pl
            JOIN publicacoes pub ON pl.publicacao_id = pub.id_publicacao
            WHERE pl.utilizador_id = u.id 
            AND pub.id_utilizador = ?
            AND pl.data >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        )
        OR
        EXISTS (
            SELECT 1 FROM comentarios c
            JOIN publicacoes pub ON c.id_publicacao = pub.id_publicacao
            WHERE c.utilizador_id = u.id 
            AND pub.id_utilizador = ?
            AND c.data >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        )
    )
    GROUP BY u.id, u.nome_completo, u.nick, p.foto_perfil, p.ocupacao
    ORDER BY 
        seguidores_em_comum DESC,
        RAND()
    LIMIT 5
";

$stmt = $con->prepare($sql);
$stmt->bind_param("iiiiiii", $currentUserId, $currentUserId, $currentUserId, $currentUserId, $currentUserId, $currentUserId, $currentUserId);
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
        'ja_segue' => (bool)$row['ja_segue']
    ];
}

echo json_encode([
    'success' => true,
    'suggestions' => $suggestions
]);
?>