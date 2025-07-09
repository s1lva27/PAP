<?php
session_start();
require "ligabd.php";

header('Content-Type: application/json');

if (!isset($_SESSION['id'])) {
    echo json_encode(['success' => false, 'message' => 'Não autenticado']);
    exit;
}

if (!isset($_POST['conversation_id'])) {
    echo json_encode(['success' => false, 'message' => 'ID da conversa não fornecido']);
    exit;
}

$currentUserId = $_SESSION['id'];
$conversationId = intval($_POST['conversation_id']);

// Marcar como lidas todas as mensagens da conversa que não foram enviadas pelo utilizador atual
$sql = "UPDATE mensagens 
        SET lida = 1 
        WHERE conversa_id = ? 
        AND remetente_id != ? 
        AND lida = 0";

$stmt = $con->prepare($sql);
$stmt->bind_param("ii", $conversationId, $currentUserId);

if ($stmt->execute()) {
    $affectedRows = $stmt->affected_rows;

    // Buscar o novo total de mensagens não lidas
    $sqlTotal = "SELECT COUNT(*) as total_unread
                 FROM mensagens m
                 JOIN conversas c ON m.conversa_id = c.id
                 WHERE (c.utilizador1_id = ? OR c.utilizador2_id = ?)
                 AND m.remetente_id != ?
                 AND m.lida = 0";
    
    $stmtTotal = $con->prepare($sqlTotal);
    $stmtTotal->bind_param("iii", $currentUserId, $currentUserId, $currentUserId);
    $stmtTotal->execute();
    $resultTotal = $stmtTotal->get_result()->fetch_assoc();
    $newUnreadCount = (int) $resultTotal['total_unread'];

    echo json_encode([
        'success' => true,
        'marked_as_read' => $affectedRows,
        'new_unread_count' => $newUnreadCount
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Erro ao marcar mensagens como lidas']);
}
?>