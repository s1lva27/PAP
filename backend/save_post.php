<?php
session_start();
include "ligabd.php";
require "create_notification.php";

if (!isset($_SESSION['id'])) {
    echo json_encode(['success' => false, 'message' => 'Usuário não autenticado']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $postId = $_POST['id_publicacao'];
    $userId = $_SESSION['id'];

    // Buscar o dono da publicação
    $sqlOwner = "SELECT id_utilizador FROM publicacoes WHERE id_publicacao = ?";
    $stmtOwner = $con->prepare($sqlOwner);
    $stmtOwner->bind_param("i", $postId);
    $stmtOwner->execute();
    $ownerResult = $stmtOwner->get_result();
    $owner = $ownerResult->fetch_assoc();
    
    if (!$owner) {
        echo json_encode(['success' => false, 'message' => 'Post not found']);
        exit;
    }
    
    $ownerId = $owner['id_utilizador'];

    // Verificar se já está salvo
    $checkSql = "SELECT * FROM publicacao_salvas 
                 WHERE publicacao_id = $postId AND utilizador_id = $userId";
    $checkResult = mysqli_query($con, $checkSql);

    if (mysqli_num_rows($checkResult) > 0) {
        // Remover salvamento
        $deleteSql = "DELETE FROM publicacao_salvas 
                      WHERE publicacao_id = $postId AND utilizador_id = $userId";
        if (mysqli_query($con, $deleteSql)) {
            // Remover notificação de save se existir
            $deleteNotifSql = "DELETE FROM notificacoes 
                              WHERE utilizador_id = ? AND remetente_id = ? 
                              AND tipo = 'save' AND publicacao_id = ?";
            $stmtDeleteNotif = $con->prepare($deleteNotifSql);
            $stmtDeleteNotif->bind_param("iii", $ownerId, $userId, $postId);
            $stmtDeleteNotif->execute();
            
            echo json_encode(['success' => true, 'action' => 'unsaved']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Erro ao remover salvamento']);
        }
    } else {
        // Adicionar salvamento
        $insertSql = "INSERT INTO publicacao_salvas (utilizador_id, publicacao_id) 
                      VALUES ($userId, $postId)";
        if (mysqli_query($con, $insertSql)) {
            // Criar notificação
            createNotification($con, $ownerId, $userId, 'save', $postId);
            
            echo json_encode(['success' => true, 'action' => 'saved']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Erro ao salvar publicação']);
        }
    }
}
?>