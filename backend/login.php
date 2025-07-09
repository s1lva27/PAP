<?php
session_start();

function ValidarEmail($inputText)
{
    return filter_var($inputText, FILTER_VALIDATE_EMAIL) !== false;
}

if (!isset($_POST["botaoLogin"])) {
    header("Location: ../frontend/login.php");
    exit();
}

require "ligabd.php";

$primeiro_campo = mysqli_real_escape_string($con, trim($_POST["primeiro_campo"]));
$p_password = mysqli_real_escape_string($con, trim($_POST["password"]));

$campo_pedido = ValidarEmail($primeiro_campo) ? "email" : "nick";

// Busca pelo utilizador
$sql = "SELECT * FROM utilizadores WHERE $campo_pedido = '$primeiro_campo'";
$resultado = mysqli_query($con, $sql);

if (!$resultado || mysqli_num_rows($resultado) == 0) {
    $_SESSION["erro"] = "O utilizador não existe.";
    header("Location: ../frontend/login.php");
    exit();
}

// Verifica a palavra-passe
$registo = mysqli_fetch_assoc($resultado);
$sqlpass = "SELECT * FROM utilizadores WHERE id = '{$registo['id']}' AND palavra_passe = password('$p_password')";
$resultado_pass = mysqli_query($con, $sqlpass);

if (!$resultado_pass || mysqli_num_rows($resultado_pass) == 0) {
    $_SESSION["erro"] = "A palavra-passe está incorreta.";
    header("Location: ../frontend/login.php");
    exit();
}

// Inicia a sessão e redireciona
$_SESSION = $registo;


header("Location: ../frontend/index.php");
exit();
?>
