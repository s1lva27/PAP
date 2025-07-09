<?php
session_start();
require "ligabd.php";

header('Content-Type: application/json');

if (!isset($_SESSION['id'])) {
    echo json_encode(['success' => false, 'message' => 'Não autenticado']);
    exit;
}

if (!isset($_POST['conversation_id']) || !isset($_POST['content'])) {
    echo json_encode(['success' => false, 'message' => 'Dados incompletos']);
    exit;
}

$currentUserId = $_SESSION['id'];
$conversationId = intval($_POST['conversation_id']);
$content = trim($_POST['content']);

if (empty($content)) {
    echo json_encode(['success' => false, 'message' => 'Mensagem vazia']);
    exit;
}

// Verificar se o utilizador faz parte da conversa
$sqlCheck = "SELECT id FROM conversas 
             WHERE id = ? AND (utilizador1_id = ? OR utilizador2_id = ?)";
$stmtCheck = $con->prepare($sqlCheck);
$stmtCheck->bind_param("iii", $conversationId, $currentUserId, $currentUserId);
$stmtCheck->execute();
$result = $stmtCheck->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Conversa não encontrada']);
    exit;
}

// Inserir mensagem
$sqlInsert = "INSERT INTO mensagens (conversa_id, remetente_id, conteudo) VALUES (?, ?, ?)";
$stmtInsert = $con->prepare($sqlInsert);
$stmtInsert->bind_param("iis", $conversationId, $currentUserId, $content);

if ($stmtInsert->execute()) {
    // Atualizar última atividade da conversa
    $sqlUpdate = "UPDATE conversas SET ultima_atividade = NOW() WHERE id = ?";
    $stmtUpdate = $con->prepare($sqlUpdate);
    $stmtUpdate->bind_param("i", $conversationId);
    $stmtUpdate->execute();
    
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Erro ao enviar mensagem']);
}
?>