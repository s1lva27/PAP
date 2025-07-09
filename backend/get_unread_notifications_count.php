<?php
session_start();
require "ligabd.php";

header('Content-Type: application/json');

if (!isset($_SESSION['id'])) {
    echo json_encode(['success' => false, 'message' => 'Não autenticado']);
    exit;
}

$userId = $_SESSION['id'];

// Buscar total de notificações não lidas
$sql = "SELECT COUNT(*) as total FROM notificacoes WHERE utilizador_id = ? AND lida = 0";
$stmt = $con->prepare($sql);
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result()->fetch_assoc();

echo json_encode([
    'success' => true,
    'unread_count' => (int) $result['total']
]);
?>