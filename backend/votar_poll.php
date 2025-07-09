<?php
session_start();
include "ligabd.php";
require "create_notification.php";

header('Content-Type: application/json');

if (!isset($_SESSION['id'])) {
    echo json_encode(['success' => false, 'message' => 'Não autenticado']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método não permitido']);
    exit;
}

$pollId = isset($_POST['poll_id']) ? intval($_POST['poll_id']) : 0;
$opcaoId = isset($_POST['opcao_id']) ? intval($_POST['opcao_id']) : 0;

if ($pollId <= 0 || $opcaoId <= 0) {
    echo json_encode(['success' => false, 'message' => 'IDs inválidos']);
    exit;
}

try {
    // Verificar se a poll ainda está ativa
    $stmt = $con->prepare("SELECT p.id, p.data_expiracao, pub.id_utilizador as criador_id
                          FROM polls p
                          JOIN publicacoes pub ON p.publicacao_id = pub.id_publicacao
                          WHERE p.id = ? AND p.data_expiracao > NOW()");
    
    if (!$stmt) {
        throw new Exception('Erro na preparação da query: ' . $con->error);
    }
    
    $stmt->bind_param("i", $pollId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Poll não encontrada ou já expirada']);
        exit;
    }

    $pollData = $result->fetch_assoc();
    $criadorId = intval($pollData['criador_id']);

    // Verificar se a opção pertence à poll
    $stmt = $con->prepare("SELECT id FROM poll_opcoes WHERE id = ? AND poll_id = ?");
    if (!$stmt) {
        throw new Exception('Erro na preparação da query: ' . $con->error);
    }
    
    $stmt->bind_param("ii", $opcaoId, $pollId);
    $stmt->execute();

    if ($stmt->get_result()->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Opção inválida']);
        exit;
    }

    // Verificar se o usuário já votou
    $stmt = $con->prepare("SELECT id FROM poll_votos WHERE poll_id = ? AND utilizador_id = ?");
    if (!$stmt) {
        throw new Exception('Erro na preparação da query: ' . $con->error);
    }
    
    $stmt->bind_param("ii", $pollId, $_SESSION['id']);
    $stmt->execute();

    if ($stmt->get_result()->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'Você já votou nesta poll']);
        exit;
    }

    // Iniciar transação
    $con->begin_transaction();

    // Registrar o voto
    $stmt = $con->prepare("INSERT INTO poll_votos (poll_id, opcao_id, utilizador_id, data_voto) 
                          VALUES (?, ?, ?, NOW())");
    if (!$stmt) {
        throw new Exception('Erro na preparação da query: ' . $con->error);
    }
    
    $stmt->bind_param("iii", $pollId, $opcaoId, $_SESSION['id']);
    $stmt->execute();

    // Atualizar contagem na opção
    $stmt = $con->prepare("UPDATE poll_opcoes SET votos = votos + 1 WHERE id = ?");
    if (!$stmt) {
        throw new Exception('Erro na preparação da query: ' . $con->error);
    }
    
    $stmt->bind_param("i", $opcaoId);
    $stmt->execute();

    // Atualizar total de votos na poll
    $stmt = $con->prepare("UPDATE polls SET total_votos = total_votos + 1 WHERE id = ?");
    if (!$stmt) {
        throw new Exception('Erro na preparação da query: ' . $con->error);
    }
    
    $stmt->bind_param("i", $pollId);
    $stmt->execute();

    // Criar notificação para o criador da poll (se não for o próprio usuário)
    if ($criadorId != $_SESSION['id']) {
        // Buscar a publicação ID
        $stmt = $con->prepare("SELECT publicacao_id FROM polls WHERE id = ?");
        if ($stmt) {
            $stmt->bind_param("i", $pollId);
            $stmt->execute();
            $pollResult = $stmt->get_result()->fetch_assoc();
            
            if ($pollResult) {
                createNotification($con, $criadorId, $_SESSION['id'], 'poll_vote', $pollResult['publicacao_id']);
            }
        }
    }

    $con->commit();
    
    // Buscar dados atualizados da poll
    $sqlPoll = "
        SELECT p.pergunta, p.data_expiracao, p.total_votos,
               po.id as opcao_id, po.opcao_texto, po.votos
        FROM polls p
        JOIN poll_opcoes po ON p.id = po.poll_id
        WHERE p.id = ?
        ORDER BY po.ordem ASC
    ";
    
    $stmtPoll = $con->prepare($sqlPoll);
    if (!$stmtPoll) {
        throw new Exception('Erro na preparação da query: ' . $con->error);
    }
    
    $stmtPoll->bind_param("i", $pollId);
    $stmtPoll->execute();
    $result = $stmtPoll->get_result();

    $opcoes = [];
    $totalVotos = 0;
    
    while ($row = $result->fetch_assoc()) {
        $totalVotos = intval($row['total_votos']);
        $votos = intval($row['votos']);
        
        $opcoes[] = [
            'id' => intval($row['opcao_id']),
            'texto' => $row['opcao_texto'],
            'votos' => $votos,
            'percentagem' => $totalVotos > 0 ? 
                round(($votos / $totalVotos) * 100, 1) : 0,
            'user_voted' => intval($row['opcao_id']) == $opcaoId
        ];
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Voto registrado com sucesso',
        'opcoes' => $opcoes,
        'total_votos' => $totalVotos,
        'user_voted_option' => $opcaoId
    ]);
    
} catch (Exception $e) {
    $con->rollback();
    error_log('Erro ao votar: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Erro ao registrar voto: ' . $e->getMessage()
    ]);
}
?>