<?php

session_start();

require "ligabd.php";

if (isset($_POST['submit'])) {
    $user_id = $_SESSION["id"]; // O ID do utilizador deve vir do sistema de login

    // Verifica se foi enviado um ficheiro
    if (!empty($_FILES['foto']['name'])) {
        $foto = $_FILES['foto'];
        $nome_temp = $foto['tmp_name'];
        $nome_original = basename($foto['name']);
        $extensao = strtolower(pathinfo($nome_original, PATHINFO_EXTENSION));

        // Permitir apenas imagens JPG, PNG ou GIF
        $extensoes_permitidas = ['jpg', 'jpeg', 'png', 'gif'];
        if (!in_array($extensao, $extensoes_permitidas)) {
            die("Erro: Apenas imagens JPG, JPEG, PNG ou GIF são permitidas.");
        }

        // Criar um nome único para a imagem
        $novo_nome = uniqid("capa_") . "." . $extensao;
        $destino = "../frontend/images/capa/".$novo_nome;

        if (move_uploaded_file($nome_temp, $destino)) {
            var_dump($novo_nome);
            var_dump($user_id);
            
            $sql = "UPDATE perfis SET foto_capa='$novo_nome' WHERE id_utilizador='$user_id'";
            $result = mysqli_query($con, $sql);

            if ($result) {
                echo "Upload feito com sucesso!";
                echo "<br><img src='$destino' width='150'>";
            }
        }
    }
}

header("Location: ../frontend/perfil.php");
exit();

?>