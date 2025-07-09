<?php
session_start();
require "ligabd.php";
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

$currentUserId = $_SESSION['id'];
$targetUserId = (int)$_POST['user_id'];
$action = $_POST['action']; // 'follow' ou 'unfollow'

if ($currentUserId === $targetUserId) {
    echo json_encode(['success' => false, 'message' => 'Não pode seguir-se a si próprio']);
    exit;
}

try {
    if ($action === 'follow') {
        // Verificar se já segue
        $checkSql = "SELECT id_seguidor FROM seguidores WHERE id_seguidor = ? AND id_seguido = ?";
        $checkStmt = $con->prepare($checkSql);
        $checkStmt->bind_param("ii", $currentUserId, $targetUserId);
        $checkStmt->execute();
        
        if ($checkStmt->get_result()->num_rows > 0) {
            echo json_encode(['success' => false, 'message' => 'Já segue este utilizador']);
            exit;
        }
        
        // Seguir utilizador
        $sql = "INSERT INTO seguidores (id_seguidor, id_seguido) VALUES (?, ?)";
        $stmt = $con->prepare($sql);
        $stmt->bind_param("ii", $currentUserId, $targetUserId);
        
        if ($stmt->execute()) {
            // Criar notificação
            createNotification($con, $targetUserId, $currentUserId, 'follow');
            echo json_encode(['success' => true, 'action' => 'followed']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Erro ao seguir utilizador']);
        }
        
    } elseif ($action === 'unfollow') {
        // Deixar de seguir
        $sql = "DELETE FROM seguidores WHERE id_seguidor = ? AND id_seguido = ?";
        $stmt = $con->prepare($sql);
        $stmt->bind_param("ii", $currentUserId, $targetUserId);
        
        if ($stmt->execute()) {
            // Criar notificação de unfollow (opcional)
            createNotification($con, $targetUserId, $currentUserId, 'unfollow');
            echo json_encode(['success' => true, 'action' => 'unfollowed']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Erro ao deixar de seguir utilizador']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Ação inválida']);
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Erro interno: ' . $e->getMessage()]);
}
?>