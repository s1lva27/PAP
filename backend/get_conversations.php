<?php
session_start();
require "ligabd.php";

header('Content-Type: application/json');

if (!isset($_SESSION['id'])) {
    echo json_encode(['success' => false, 'message' => 'Não autenticado']);
    exit;
}

$currentUserId = $_SESSION['id'];

// Buscar conversas do utilizador
$sqlConversas = "SELECT c.id, c.utilizador1_id, c.utilizador2_id, c.ultima_atividade,
                        u1.nick as nick1, u1.nome_completo as nome1, p1.foto_perfil as foto1,
                        u2.nick as nick2, u2.nome_completo as nome2, p2.foto_perfil as foto2,
                        (SELECT conteudo FROM mensagens WHERE conversa_id = c.id ORDER BY data_envio DESC LIMIT 1) as ultima_mensagem,
                        (SELECT COUNT(*) FROM mensagens WHERE conversa_id = c.id AND remetente_id != $currentUserId AND lida = 0) as mensagens_nao_lidas
                 FROM conversas c
                 JOIN utilizadores u1 ON c.utilizador1_id = u1.id
                 JOIN utilizadores u2 ON c.utilizador2_id = u2.id
                 LEFT JOIN perfis p1 ON u1.id = p1.id_utilizador
                 LEFT JOIN perfis p2 ON u2.id = p2.id_utilizador
                 WHERE c.utilizador1_id = $currentUserId OR c.utilizador2_id = $currentUserId
                 ORDER BY c.ultima_atividade DESC";

$result = mysqli_query($con, $sqlConversas);
$conversations = [];

while ($row = mysqli_fetch_assoc($result)) {
    // Determinar qual é o outro utilizador
    $outroUtilizador = ($row['utilizador1_id'] == $currentUserId) ? 
        ['id' => $row['utilizador2_id'], 'nick' => $row['nick2'], 'nome' => $row['nome2'], 'foto' => $row['foto2']] :
        ['id' => $row['utilizador1_id'], 'nick' => $row['nick1'], 'nome' => $row['nome1'], 'foto' => $row['foto1']];
    
    $conversations[] = [
        'id' => $row['id'],
        'other_user' => $outroUtilizador,
        'ultima_atividade' => $row['ultima_atividade'],
        'ultima_mensagem' => $row['ultima_mensagem'],
        'mensagens_nao_lidas' => $row['mensagens_nao_lidas']
    ];
}

echo json_encode([
    'success' => true,
    'conversations' => $conversations
]);
?>