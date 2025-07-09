<?php
session_start();
include "ligabd.php";

if (!isset($_SESSION['id'])) {
    echo json_encode(['success' => false, 'message' => 'Não autenticado']);
    exit;
}

if (!isset($_POST['id_publicacao'])) {
    echo json_encode(['success' => false, 'message' => 'ID da publicação não fornecido']);
    exit;
}

$postId = intval($_POST['id_publicacao']);
$userId = $_SESSION['id'];
$userType = $_SESSION['id_tipos_utilizador'];

// Verificar se o usuário é o autor ou admin
$sql = "SELECT id_utilizador FROM publicacoes WHERE id_publicacao = $postId";
$result = mysqli_query($con, $sql);
$post = mysqli_fetch_assoc($result);

if (!$post) {
    echo json_encode(['success' => false, 'message' => 'Publicação não encontrada']);
    exit;
}

if ($post['id_utilizador'] != $userId && $userType != 2) {
    echo json_encode(['success' => false, 'message' => 'Não autorizado']);
    exit;
}

// Iniciar transação para garantir que todas as operações sejam concluídas com sucesso
mysqli_begin_transaction($con);

try {
    // 1. Apagar os comentários associados à publicação
    $sql = "DELETE FROM comentarios WHERE id_publicacao = $postId";
    mysqli_query($con, $sql);
    
    // 2. Apagar os likes associados à publicação
    $sql = "DELETE FROM publicacao_likes WHERE publicacao_id = $postId";
    mysqli_query($con, $sql);
    
    // 3. Apagar as publicações salvas associadas à publicação
    $sql = "DELETE FROM publicacao_salvas WHERE publicacao_id = $postId";
    mysqli_query($con, $sql);
    
    // 4. Apagar as mídias associadas à publicação (e os arquivos físicos se necessário)
    $sql = "SELECT url FROM publicacao_medias WHERE publicacao_id = $postId";
    $result = mysqli_query($con, $sql);
    $medias = mysqli_fetch_all($result, MYSQLI_ASSOC);
    
    foreach ($medias as $media) {
        $filePath = "../images/publicacoes/" . $media['url'];
        if (file_exists($filePath)) {
            unlink($filePath); // Remove o arquivo físico
        }
    }
    
    $sql = "DELETE FROM publicacao_medias WHERE publicacao_id = $postId";
    mysqli_query($con, $sql);
    
    // 5. Finalmente, apagar a publicação
    $sql = "DELETE FROM publicacoes WHERE id_publicacao = $postId";
    mysqli_query($con, $sql);
    
    // Confirmar todas as operações
    mysqli_commit($con);
    
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    // Em caso de erro, reverter todas as operações
    mysqli_rollback($con);
    echo json_encode(['success' => false, 'message' => 'Erro ao apagar publicação: ' . $e->getMessage()]);
}
?>