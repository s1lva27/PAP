<?php
session_start();
require "ligabd.php";
require "create_notification.php";

if (!isset($_SESSION["id"])) {
    die("Acesso negado.");
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["id_seguido"], $_POST["acao"])) {
    $idSeguidor = $_SESSION["id"];
    $idSeguido = intval($_POST["id_seguido"]);

    if ($idSeguidor === $idSeguido) {
        die("Não pode seguir-se a si próprio.");
    }

    if ($_POST["acao"] === "follow") {
        // Adicionar seguimento
        $sql = "INSERT INTO seguidores (id_seguidor, id_seguido) VALUES (?, ?)";
        $stmt = $con->prepare($sql);
        $stmt->bind_param("ii", $idSeguidor, $idSeguido);
        
        if ($stmt->execute()) {
            // Criar notificação
            createNotification($con, $idSeguido, $idSeguidor, 'follow');
            header("Location: ../frontend/perfil.php?id=$idSeguido");
        }
    } elseif ($_POST["acao"] === "unfollow") {
        // Remover seguimento
        $sql = "DELETE FROM seguidores WHERE id_seguidor = ? AND id_seguido = ?";
        $stmt = $con->prepare($sql);
        $stmt->bind_param("ii", $idSeguidor, $idSeguido);
        
        if ($stmt->execute()) {
            // Remover notificação de follow
            createNotification($con, $idSeguido, $idSeguidor, 'unfollow');
            header("Location: ../frontend/perfil.php?id=$idSeguido");
        }
    } else {
        die("Ação inválida.");
    }
} else {
    header("Location: ../frontend/index.php");
}
?>