<?php
session_start();

if (!isset($_SESSION["nick"])) {
    header("Location: ../../frontend/login.php");
    exit();
}

require "../ligabd.php";

// Obter os dados
$id = trim($_POST["id"]);
$pass_atual = trim($_POST["pass_atual"]);
$pass_nova = trim($_POST["pass_nova"]);

// Validações
if (empty($pass_atual)) {
    $_SESSION["erro"] = "A palavra-passe atual é obrigatória.";
    header("Location: ../../frontend/editar_perfil.php?section=security-info&status=error");
    exit();
}

if (empty($pass_nova)) {
    $_SESSION["erro"] = "A nova palavra-passe é obrigatória.";
    header("Location: ../../frontend/editar_perfil.php?section=security-info&status=error");
    exit();
}

if (strlen($pass_nova) < 6) {
    $_SESSION["erro"] = "A nova palavra-passe deve ter pelo menos 6 caracteres.";
    header("Location: ../../frontend/editar_perfil.php?section=security-info&status=error");
    exit();
}

// Verificar se a palavra-passe atual está correta
$sql = "SELECT * FROM utilizadores WHERE id='$id' AND palavra_passe = password('$pass_atual')";
$resultado = mysqli_query($con, $sql );
if (mysqli_num_rows($resultado)<1) {
    $_SESSION["erro"] = "A palavra-passe atual está incorreta.";
    header("Location: ../../frontend/editar_perfil.php?section=security-info&status=error");
    exit();
}

// Verificar se a nova palavra-passe é diferente da atual
if ($pass_atual === $pass_nova) {
    $_SESSION["erro"] = "A nova palavra-passe deve ser diferente da atual.";
    header("Location: ../../frontend/editar_perfil.php?section=security-info&status=error");
    exit();
}

$sql = "UPDATE utilizadores SET 
    palavra_passe=password('$pass_nova')
    WHERE id='$id'";

$resultado = mysqli_query($con, $sql);

if (!$resultado) {
    $_SESSION["erro"] = "Erro ao atualizar palavra-passe.";
    header("Location: ../../frontend/editar_perfil.php?section=security-info&status=error");
    exit();
}

$_SESSION["sucesso"] = "Palavra-passe alterada com sucesso!";
header("Location: ../../frontend/editar_perfil.php?section=security-info&status=success");
?>