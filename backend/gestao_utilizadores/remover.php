<?php
session_start(); 

if(!isset($_POST["botaoRemover"]) || !isset($_SESSION["nick"]) || $_SESSION["id_tipos_utilizador"] != 2) {
    header("Location: ../../frontend/editar_utilizadores.php");
    exit();
}

// Proteger o admin principal
if($_POST["nick"] == "admin"){
    $_SESSION["erro"] = "Não é possível remover o utilizador admin principal.";
    header("Location: ../../frontend/editar_utilizadores.php");
    exit();
}

require "../ligabd.php"; 

$nick = mysqli_real_escape_string($con, $_POST["nick"]);

// Buscar o ID do utilizador
$sql_get_id = "SELECT id FROM utilizadores WHERE nick = '$nick'";
$result_id = mysqli_query($con, $sql_get_id);

if (!$result_id || mysqli_num_rows($result_id) == 0) {
    $_SESSION["erro"] = "Utilizador não encontrado.";
    header("Location: ../../frontend/editar_utilizadores.php");
    exit();
}

$user_data = mysqli_fetch_assoc($result_id);
$user_id = $user_data['id'];

// Iniciar transação para garantir consistência
mysqli_begin_transaction($con);

try {
    // 1. Remover notificações relacionadas com o utilizador
    $sql_delete_notifications_received = "DELETE FROM notificacoes WHERE utilizador_id = $user_id";
    mysqli_query($con, $sql_delete_notifications_received);
    
    $sql_delete_notifications_sent = "DELETE FROM notificacoes WHERE remetente_id = $user_id";
    mysqli_query($con, $sql_delete_notifications_sent);

    // 2. Remover mensagens do utilizador
    $sql_delete_messages = "DELETE FROM mensagens WHERE remetente_id = $user_id";
    mysqli_query($con, $sql_delete_messages);

    // 3. Remover conversas onde o utilizador participa
    $sql_delete_conversations = "DELETE FROM conversas WHERE utilizador1_id = $user_id OR utilizador2_id = $user_id";
    mysqli_query($con, $sql_delete_conversations);

    // 4. Buscar publicações do utilizador para remover dependências
    $sql_get_posts = "SELECT id_publicacao FROM publicacoes WHERE id_utilizador = $user_id";
    $result_posts = mysqli_query($con, $sql_get_posts);
    
    $post_ids = [];
    while ($post = mysqli_fetch_assoc($result_posts)) {
        $post_ids[] = $post['id_publicacao'];
    }

    // 5. Se há publicações, remover dependências relacionadas
    if (!empty($post_ids)) {
        $post_ids_str = implode(',', $post_ids);
        
        // Buscar polls relacionadas com as publicações
        $sql_get_polls = "SELECT id FROM polls WHERE publicacao_id IN ($post_ids_str)";
        $result_polls = mysqli_query($con, $sql_get_polls);
        
        $poll_ids = [];
        while ($poll = mysqli_fetch_assoc($result_polls)) {
            $poll_ids[] = $poll['id'];
        }
        
        // Remover votos em polls das publicações do utilizador
        if (!empty($poll_ids)) {
            $poll_ids_str = implode(',', $poll_ids);
            $sql_delete_poll_votes_posts = "DELETE FROM poll_votos WHERE poll_id IN ($poll_ids_str)";
            mysqli_query($con, $sql_delete_poll_votes_posts);
            
            // Remover opções de polls
            $sql_delete_poll_options = "DELETE FROM poll_opcoes WHERE poll_id IN ($poll_ids_str)";
            mysqli_query($con, $sql_delete_poll_options);
            
            // Remover polls
            $sql_delete_polls = "DELETE FROM polls WHERE id IN ($poll_ids_str)";
            mysqli_query($con, $sql_delete_polls);
        }
        
        // Buscar URLs das mídias para remover arquivos físicos
        $sql_get_medias = "SELECT url FROM publicacao_medias WHERE publicacao_id IN ($post_ids_str)";
        $result_medias = mysqli_query($con, $sql_get_medias);
        
        while ($media = mysqli_fetch_assoc($result_medias)) {
            $file_path = "../../frontend/images/publicacoes/" . $media['url'];
            if (file_exists($file_path)) {
                unlink($file_path);
            }
        }
        
        // Remover registos de mídias
        $sql_delete_medias = "DELETE FROM publicacao_medias WHERE publicacao_id IN ($post_ids_str)";
        mysqli_query($con, $sql_delete_medias);

        // Remover likes das publicações
        $sql_delete_post_likes = "DELETE FROM publicacao_likes WHERE publicacao_id IN ($post_ids_str)";
        mysqli_query($con, $sql_delete_post_likes);

        // Remover publicações salvas
        $sql_delete_post_saved = "DELETE FROM publicacao_salvas WHERE publicacao_id IN ($post_ids_str)";
        mysqli_query($con, $sql_delete_post_saved);

        // Remover comentários das publicações
        $sql_delete_post_comments = "DELETE FROM comentarios WHERE id_publicacao IN ($post_ids_str)";
        mysqli_query($con, $sql_delete_post_comments);

        // Remover publicações
        $sql_delete_posts = "DELETE FROM publicacoes WHERE id_utilizador = $user_id";
        mysqli_query($con, $sql_delete_posts);
    }

    // 6. Remover votos em polls do utilizador (em polls de outros utilizadores)
    $sql_delete_poll_votes = "DELETE FROM poll_votos WHERE utilizador_id = $user_id";
    mysqli_query($con, $sql_delete_poll_votes);

    // 7. Remover likes do utilizador (em publicações de outros)
    $sql_delete_likes = "DELETE FROM publicacao_likes WHERE utilizador_id = $user_id";
    mysqli_query($con, $sql_delete_likes);

    // 8. Remover publicações salvas pelo utilizador
    $sql_delete_saved = "DELETE FROM publicacao_salvas WHERE utilizador_id = $user_id";
    mysqli_query($con, $sql_delete_saved);

    // 9. Remover comentários do utilizador (em publicações de outros)
    $sql_delete_comments = "DELETE FROM comentarios WHERE utilizador_id = $user_id";
    mysqli_query($con, $sql_delete_comments);

    // 10. Remover relacionamentos de seguimento
    $sql_delete_followers = "DELETE FROM seguidores WHERE id_seguidor = $user_id OR id_seguido = $user_id";
    mysqli_query($con, $sql_delete_followers);

    // 11. Remover foto de perfil e capa (se não forem as padrão)
    $sql_get_profile = "SELECT foto_perfil, foto_capa FROM perfis WHERE id_utilizador = $user_id";
    $result_profile = mysqli_query($con, $sql_get_profile);
    
    if ($profile = mysqli_fetch_assoc($result_profile)) {
        // Remover foto de perfil se não for a padrão
        if ($profile['foto_perfil'] && $profile['foto_perfil'] !== 'default-profile.jpg') {
            $profile_pic_path = "../../frontend/images/perfil/" . $profile['foto_perfil'];
            if (file_exists($profile_pic_path)) {
                unlink($profile_pic_path);
            }
        }
        
        // Remover foto de capa se não for a padrão
        if ($profile['foto_capa'] && $profile['foto_capa'] !== 'default-capa.png') {
            $cover_pic_path = "../../frontend/images/capa/" . $profile['foto_capa'];
            if (file_exists($cover_pic_path)) {
                unlink($cover_pic_path);
            }
        }
    }

    // 12. Remover perfil do utilizador
    $sql_delete_profile = "DELETE FROM perfis WHERE id_utilizador = $user_id";
    mysqli_query($con, $sql_delete_profile);

    // 13. Finalmente, remover o utilizador
    $sql_delete_user = "DELETE FROM utilizadores WHERE id = $user_id";
    $resultado = mysqli_query($con, $sql_delete_user);

    if (!$resultado) {
        throw new Exception("Erro ao remover o utilizador da tabela principal: " . mysqli_error($con));
    }

    // Confirmar todas as operações
    mysqli_commit($con);
    
    $_SESSION["sucesso"] = "Utilizador '$nick' removido com sucesso, incluindo todos os dados associados.";

} catch (Exception $e) {
    // Reverter todas as operações em caso de erro
    mysqli_rollback($con);
    $_SESSION["erro"] = "Erro ao remover utilizador: " . $e->getMessage();
    error_log("Erro ao remover utilizador $nick: " . $e->getMessage());
}

header("Location: ../../frontend/editar_utilizadores.php");
exit();
?>