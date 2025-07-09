<?php
session_start();
require "ligabd.php";
require "create_notification.php";

if (!isset($_SESSION["id"])) {
    die("Acesso negado.");
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["id_publicacao"])) {
    $userId = $_SESSION["id"];
    $publicacaoId = intval($_POST["id_publicacao"]);
    
    // Buscar o dono da publicação
    $sqlOwner = "SELECT id_utilizador FROM publicacoes WHERE id_publicacao = ?";
    $stmtOwner = $con->prepare($sqlOwner);
    $stmtOwner->bind_param("i", $publicacaoId);
    $stmtOwner->execute();
    $ownerResult = $stmtOwner->get_result();
    $owner = $ownerResult->fetch_assoc();
    
    if (!$owner) {
        echo "error";
        exit;
    }
    
    $ownerId = $owner['id_utilizador'];
    
    // Verificar se o usuário já deu like nesta publicação
    $checkSql = "SELECT * FROM publicacao_likes WHERE publicacao_id = ? AND utilizador_id = ?";
    $stmtCheck = $con->prepare($checkSql);
    $stmtCheck->bind_param("ii", $publicacaoId, $userId);
    $stmtCheck->execute();
    $resultCheck = $stmtCheck->get_result();
    
    if ($resultCheck->num_rows > 0) {
        // Remover like
        $deleteSql = "DELETE FROM publicacao_likes WHERE publicacao_id = ? AND utilizador_id = ?";
        $stmtDelete = $con->prepare($deleteSql);
        $stmtDelete->bind_param("ii", $publicacaoId, $userId);
        
        if ($stmtDelete->execute()) {
            // Atualizar contagem de likes
            $updateSql = "UPDATE publicacoes SET likes = likes - 1 WHERE id_publicacao = ?";
            $stmtUpdate = $con->prepare($updateSql);
            $stmtUpdate->bind_param("i", $publicacaoId);
            $stmtUpdate->execute();
            
            // Remover notificação de like se existir
            $deleteNotifSql = "DELETE FROM notificacoes 
                              WHERE utilizador_id = ? AND remetente_id = ? 
                              AND tipo = 'like' AND publicacao_id = ?";
            $stmtDeleteNotif = $con->prepare($deleteNotifSql);
            $stmtDeleteNotif->bind_param("iii", $ownerId, $userId, $publicacaoId);
            $stmtDeleteNotif->execute();
            
            echo "unliked";
        } else {
            echo "error";
        }
    } else {
        // Adicionar like
        $insertSql = "INSERT INTO publicacao_likes (publicacao_id, utilizador_id) VALUES (?, ?)";
        $stmtInsert = $con->prepare($insertSql);
        $stmtInsert->bind_param("ii", $publicacaoId, $userId);
        
        if ($stmtInsert->execute()) {
            // Atualizar contagem de likes
            $updateSql = "UPDATE publicacoes SET likes = likes + 1 WHERE id_publicacao = ?";
            $stmtUpdate = $con->prepare($updateSql);
            $stmtUpdate->bind_param("i", $publicacaoId);
            $stmtUpdate->execute();
            
            // Criar notificação
            createNotification($con, $ownerId, $userId, 'like', $publicacaoId);
            
            echo "liked";
        } else {
            echo "error";
        }
    }
} else {
    die("Requisição inválida.");
}
?>