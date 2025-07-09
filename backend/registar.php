<?php
session_start();

if (!isset($_POST["submitBtn"]) || isset($_SESSION["email"])) {
    header("Location: ../");
    exit();
}

$nick = $_POST["nick"];
$name = $_POST["name"];
$email = $_POST["email"];
$date = $_POST["date"];
$password = $_POST["password"];

require "ligabd.php";

$sql_check_email = "SELECT * FROM utilizadores WHERE email='$email'";
$sql_check_nick = "SELECT * FROM utilizadores WHERE nick='$nick'";

$check_email = mysqli_query($con, $sql_check_email);
$check_nick = mysqli_query($con, $sql_check_nick);

if (mysqli_num_rows($check_email) > 0) {
    $_SESSION["erro"] = "Email já registado.";
    header("Location: ../frontend/registar.php");
    exit();
}

if (mysqli_num_rows($check_nick) > 0) {
    $_SESSION["erro"] = "Nome de utilizador já registado.";
    header("Location: ../frontend/registar.php");
    exit();
}

// Inserir utilizador
$sql_inserir = "INSERT INTO utilizadores VALUES
     (null, '$name', '$email', password('$password'), '$date', '$nick', '0', null)";

$resultado = mysqli_query($con, $sql_inserir);

if (!$resultado) {
    $_SESSION["erro"] = "Não foi possivel inserir o utilizador.";
    header("Location: ../frontend/registar.php");
    exit();
}

// Buscar o utilizador recém-criado para obter o ID
$sqlUser = "SELECT * FROM utilizadores WHERE email = '$email'";
$resultUser = mysqli_query($con, $sqlUser);
$userData = mysqli_fetch_assoc($resultUser);

if (!$userData) {
    $_SESSION["erro"] = "Erro ao criar conta.";
    header("Location: ../frontend/registar.php");
    exit();
}

$id = (int)$userData["id"];
$foto_perfil = "default-profile.jpg";
$biografia = "";
$data_criacao = $userData["data_criacao"];
$foto_capa = "default-capa.png";
$x = "";
$linkedin = "";
$github = "";
$ocupacao = "";
$pais = "";
$cidade = "";

// Criar perfil
$sql_inserir_perfil = "INSERT INTO perfis VALUES
     (null, '$id', '$biografia', '$foto_perfil', '$data_criacao', '$foto_capa', '$x', '$linkedin', '$github', '$ocupacao', '$pais', '$cidade')";

$resultado_perfil = mysqli_query($con, $sql_inserir_perfil);

if (!$resultado_perfil) {
    $_SESSION["erro"] = "Erro ao criar perfil.";
    header("Location: ../frontend/registar.php");
    exit();
}

// FAZER LOGIN AUTOMÁTICO
// Definir todas as variáveis de sessão necessárias
$_SESSION["id"] = $userData["id"];
$_SESSION["nome_completo"] = $userData["nome_completo"];
$_SESSION["email"] = $userData["email"];
$_SESSION["data_nascimento"] = $userData["data_nascimento"];
$_SESSION["nick"] = $userData["nick"];
$_SESSION["id_tipos_utilizador"] = $userData["id_tipos_utilizador"];
$_SESSION["data_criacao"] = $userData["data_criacao"];

// Definir mensagem de sucesso
$_SESSION["sucesso"] = "Conta criada com sucesso! Bem-vindo à Orange!";

// Redirecionar para a página principal já logado
header("Location: ../frontend/index.php");
exit();
?>