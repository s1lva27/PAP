<?php
function createNotification($con, $userId, $remetenteId, $tipo, $publicacaoId = null, $comentarioId = null) {
    // Não criar notificação para si próprio
    if ($userId == $remetenteId) {
        return false;
    }
    
    // Buscar informações do remetente
    $sqlRemetente = "SELECT nick, nome_completo FROM utilizadores WHERE id = ?";
    $stmtRemetente = $con->prepare($sqlRemetente);
    $stmtRemetente->bind_param("i", $remetenteId);
    $stmtRemetente->execute();
    $remetente = $stmtRemetente->get_result()->fetch_assoc();
    
    if (!$remetente) return false;
    
    $mensagem = '';
    
    switch ($tipo) {
        case 'like':
            $mensagem = $remetente['nome_completo'] . " deu like na sua publicação";
            break;
        case 'comment':
            $mensagem = $remetente['nome_completo'] . " comentou na sua publicação";
            break;
        case 'follow':
            $mensagem = $remetente['nome_completo'] . " começou a seguir-te";
            break;
        case 'save':
            $mensagem = $remetente['nome_completo'] . " guardou a sua publicação";
            break;
        case 'poll_vote':
            $mensagem = $remetente['nome_completo'] . " votou na sua poll";
            break;
        case 'unfollow':
            // Para unfollow, vamos remover a notificação de follow se existir
            $sqlDelete = "DELETE FROM notificacoes 
                         WHERE utilizador_id = ? AND remetente_id = ? AND tipo = 'follow'";
            $stmtDelete = $con->prepare($sqlDelete);
            $stmtDelete->bind_param("ii", $userId, $remetenteId);
            $stmtDelete->execute();
            return true;
    }
    
    // Verificar se já existe uma notificação similar recente (últimas 24h)
    $sqlCheck = "SELECT id FROM notificacoes 
                 WHERE utilizador_id = ? AND remetente_id = ? AND tipo = ? 
                 AND publicacao_id = ? AND data_criacao > DATE_SUB(NOW(), INTERVAL 24 HOUR)";
    $stmtCheck = $con->prepare($sqlCheck);
    $stmtCheck->bind_param("iisi", $userId, $remetenteId, $tipo, $publicacaoId);
    $stmtCheck->execute();
    
    if ($stmtCheck->get_result()->num_rows > 0) {
        return false; // Já existe notificação similar
    }
    
    // Criar a notificação
    $sqlInsert = "INSERT INTO notificacoes (utilizador_id, remetente_id, tipo, publicacao_id, comentario_id, mensagem) 
                  VALUES (?, ?, ?, ?, ?, ?)";
    $stmtInsert = $con->prepare($sqlInsert);
    $stmtInsert->bind_param("iisiss", $userId, $remetenteId, $tipo, $publicacaoId, $comentarioId, $mensagem);
    
    return $stmtInsert->execute();
}
?>