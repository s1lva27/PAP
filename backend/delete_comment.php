<?php
session_start();
include "ligabd.php";

if (!isset($_SESSION['id'])) {
    echo json_encode(['success' => false, 'message' => 'Não autenticado']);
    exit;
}

if (!isset($_POST['id_comentario'])) {
    echo json_encode(['success' => false, 'message' => 'ID do comentário não fornecido']);
    exit;
}

$commentId = intval($_POST['id_comentario']);
$userId = $_SESSION['id'];
$userType = $_SESSION['id_tipos_utilizador'];

// Verificar se o usuário é o autor ou admin
$sql = "SELECT utilizador_id FROM comentarios WHERE id = $commentId";
$result = mysqli_query($con, $sql);
$comment = mysqli_fetch_assoc($result);

if (!$comment) {
    echo json_encode(['success' => false, 'message' => 'Comentário não encontrado']);
    exit;
}

if ($comment['utilizador_id'] != $userId && $userType != 2) {
    echo json_encode(['success' => false, 'message' => 'Não autorizado']);
    exit;
}

// Apagar o comentário
$sql = "DELETE FROM comentarios WHERE id = $commentId";
$result = mysqli_query($con, $sql);

if ($result) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Erro ao apagar comentário']);
}
?>