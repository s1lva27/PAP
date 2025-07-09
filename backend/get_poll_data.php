<?php
session_start();
require 'ligabd.php';

header('Content-Type: application/json');

if (!isset($_GET['poll_id'])) {
    echo json_encode(['success' => false, 'message' => 'ID da poll não fornecido']);
    exit;
}

$pollId = intval($_GET['poll_id']);
$userId = isset($_SESSION['id']) ? intval($_SESSION['id']) : 0;

try {
    // Buscar dados da poll
    $sqlPoll = "
        SELECT p.pergunta, p.data_expiracao, p.total_votos,
               po.id as opcao_id, po.opcao_texto, po.votos, po.ordem
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

    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Poll não encontrada']);
        exit;
    }

    $opcoes = [];
    $pollData = null;
    
    while ($row = $result->fetch_assoc()) {
        if (!$pollData) {
            $pollData = [
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
    $userVoted = false;
    $userVotedOption = null;
    
    if ($userId > 0) {
        $sqlUserVote = "SELECT opcao_id FROM poll_votos WHERE poll_id = ? AND utilizador_id = ?";
        $stmtUserVote = $con->prepare($sqlUserVote);
        if ($stmtUserVote) {
            $stmtUserVote->bind_param("ii", $pollId, $userId);
            $stmtUserVote->execute();
            $voteResult = $stmtUserVote->get_result();
            
            if ($voteResult->num_rows > 0) {
                $userVoted = true;
                $voteData = $voteResult->fetch_assoc();
                $userVotedOption = intval($voteData['opcao_id']);
            }
        }
    }

    echo json_encode([
        'success' => true,
        'poll' => $pollData,
        'opcoes' => $opcoes,
        'user_voted' => $userVoted,
        'user_voted_option' => $userVotedOption
    ]);

} catch (Exception $e) {
    error_log('Erro ao carregar poll: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erro interno do servidor']);
}
?>