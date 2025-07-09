<?php
require 'ligabd.php';

if (isset($_GET['post_id'])) {
    $postId = intval($_GET['post_id']);
    
    $sql = "SELECT url, content_warning, ordem 
            FROM publicacao_medias 
            WHERE publicacao_id = $postId
            ORDER BY ordem ASC";
    
    $result = mysqli_query($con, $sql);
    $images = [];
    
    while ($row = mysqli_fetch_assoc($result)) {
        $images[] = $row;
    }
    
    header('Content-Type: application/json');
    echo json_encode($images);
    exit;
}

http_response_code(400);
echo json_encode(['error' => 'Post ID not provided']);