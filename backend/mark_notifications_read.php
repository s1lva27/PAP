<?php
session_start();
require "ligabd.php";

header('Content-Type: application/json');

if (!isset($_SESSION['id'])) {
    echo json_encode(['success' => false, 'message' => 'Não autenticado']);
    exit;
}

$userId = $_SESSION['id'];

// Marcar todas as notificações como lidas
$sql = "UPDATE notificacoes SET lida = 1 WHERE utilizador_id = ? AND lida = 0";
$stmt = $con->prepare($sql);
$stmt->bind_param("i", $userId);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'marked_count' => $stmt->affected_rows]);
} else {
    echo json_encode(['success' => false, 'message' => 'Erro ao marcar notificações']);
}
?>