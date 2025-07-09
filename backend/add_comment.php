<?php
session_start();
include "ligabd.php";
require "create_notification.php";

if (!isset($_SESSION['id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

$postId = intval($_POST['post_id']);
$content = mysqli_real_escape_string($con, $_POST['content']);
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

$sql = "INSERT INTO comentarios (id_publicacao, utilizador_id, conteudo, data) 
        VALUES ($postId, $userId, '$content', NOW())";

if (mysqli_query($con, $sql)) {
    $commentId = mysqli_insert_id($con);
    
    // Criar notificação
    createNotification($con, $ownerId, $userId, 'comment', $postId, $commentId);
    
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => mysqli_error($con)]);
}
?>