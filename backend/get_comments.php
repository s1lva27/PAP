<?php
include "ligabd.php";

$postId = intval($_GET['post_id']);
$sql = "SELECT c.*, u.id AS utilizador_id, u.nick, pr.foto_perfil 
        FROM comentarios c
        JOIN utilizadores u ON c.utilizador_id = u.id
        LEFT JOIN perfis pr ON u.id = pr.id_utilizador
        WHERE c.id_publicacao = $postId
        ORDER BY c.data ASC";

$result = mysqli_query($con, $sql);
$comments = [];

while ($row = mysqli_fetch_assoc($result)) {
    $comments[] = $row;
}

header('Content-Type: application/json');
echo json_encode($comments);
?>