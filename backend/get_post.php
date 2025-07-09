<?php
include "ligabd.php";

if (isset($_GET['id'])) {
    $postId = intval($_GET['id']);
    
    $sql = "SELECT p.id_publicacao, p.conteudo, p.data_criacao, p.likes, 
                   u.id AS id_utilizador, u.nick, 
                   pr.foto_perfil, pr.ocupacao
            FROM publicacoes p
            JOIN utilizadores u ON p.id_utilizador = u.id
            LEFT JOIN perfis pr ON u.id = pr.id_utilizador
            WHERE p.id_publicacao = $postId";
    
    $result = mysqli_query($con, $sql);
    
    if ($result && mysqli_num_rows($result) > 0) {
        $post = mysqli_fetch_assoc($result);
        
        // Buscar imagens da publicação
        $sqlImages = "SELECT url, content_warning, tipo FROM publicacao_medias 
                      WHERE publicacao_id = $postId
                      ORDER BY ordem ASC";
        $resultImages = mysqli_query($con, $sqlImages);
        $images = [];
        
        while ($row = mysqli_fetch_assoc($resultImages)) {
            $images[] = $row;
        }
        
        $post['images'] = $images;
        
        header('Content-Type: application/json');
        echo json_encode($post);
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'Post not found']);
    }
} else {
    http_response_code(400);
    echo json_encode(['error' => 'Post ID not provided']);
}
?>