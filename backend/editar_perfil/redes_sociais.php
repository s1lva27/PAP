<?php
session_start();

if (!isset($_SESSION["nick"])) {
    header("Location: ../../frontend/login.php");
    exit();
}

require "../ligabd.php";

// Obter os dados
$id = trim($_POST["id"]);

$x = trim($_POST["x"]);
$linkedin = trim($_POST["linkedin"]);
$github = trim(string: $_POST["github"]);

// Validar URLs se fornecidas
if (!empty($x) && !filter_var($x, FILTER_VALIDATE_URL)) {
    $_SESSION["erro"] = "URL do X (Twitter) inválida.";
    header("Location: ../../frontend/editar_perfil.php?section=social-info&status=error");
    exit();
}

if (!empty($linkedin) && !filter_var($linkedin, FILTER_VALIDATE_URL)) {
    $_SESSION["erro"] = "URL do LinkedIn inválida.";
    header("Location: ../../frontend/editar_perfil.php?section=social-info&status=error");
    exit();
}

if (!empty($github) && !filter_var($github, FILTER_VALIDATE_URL)) {
    $_SESSION["erro"] = "URL do GitHub inválida.";
    header("Location: ../../frontend/editar_perfil.php?section=social-info&status=error");
    exit();
}

$sql = "UPDATE perfis SET 
    x='$x',
    linkedin='$linkedin',
    github='$github'

    WHERE id_utilizador='$id'";

$resultado = mysqli_query($con, $sql);

if (!$resultado) {
    $_SESSION["erro"] = "Erro ao atualizar informações.";
    header("Location: ../../frontend/editar_perfil.php?section=social-info&status=error");
    exit();
}


$_SESSION["sucesso"] = "Redes sociais atualizadas com sucesso!";
header("Location: ../../frontend/editar_perfil.php?section=social-info&status=success");
?>