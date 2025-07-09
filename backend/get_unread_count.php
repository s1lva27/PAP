<?php
session_start();
require "ligabd.php";

header('Content-Type: application/json');

if (!isset($_SESSION['id'])) {
    echo json_encode(['success' => false, 'message' => 'Não autenticado']);
    exit;
}

$currentUserId = $_SESSION['id'];

// Buscar total de mensagens não lidas
$sql = "SELECT COUNT(*) as total_unread
        FROM mensagens m
        JOIN conversas c ON m.conversa_id = c.id
        WHERE (c.utilizador1_id = ? OR c.utilizador2_id = ?)
        AND m.remetente_id != ?
        AND m.lida = 0";

$stmt = $con->prepare($sql);
$stmt->bind_param("iii", $currentUserId, $currentUserId, $currentUserId);
$stmt->execute();
$result = $stmt->get_result()->fetch_assoc();

echo json_encode([
    'success' => true,
    'total_unread' => (int) $result['total_unread']
]);
?>