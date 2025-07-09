<?php
session_start();
require "ligabd.php";

if (!isset($_POST["botaoInserir"]) && !isset($_POST["verificarCodigo"])) {
    unset($_SESSION["codigoVerificacao"]);
    unset($_SESSION["emailEnviado"]);
    unset($_SESSION['tempUserData']);
    unset($_SESSION["erro"]);
    unset($_SESSION["sucesso"]);
}

if (isset($_POST["botaoInserir"])) {
    $_SESSION['tempUserData'] = $_POST;

    $email = mysqli_real_escape_string($con, $_POST["email"]);
    $username = mysqli_real_escape_string($con, $_POST["user"]);
    $nome = mysqli_real_escape_string($con, $_POST["nome"]);

    $sql_existe_email = "SELECT * FROM utilizador WHERE email='$email'";
    $sql_existe_user = "SELECT * FROM utilizador WHERE user='$username'";
    $existe_email = mysqli_query($con, $sql_existe_email);
    $existe_user = mysqli_query($con, $sql_existe_user);

    if (mysqli_num_rows($existe_email) > 0) {
        $_SESSION["erro"] = "O email já está registrado.";
        header("Location: signup.php");
        exit();
    }

    if (mysqli_num_rows($existe_user) > 0) {
        $_SESSION["erro"] = "O nome de utilizador já está em uso.";
        header("Location: signup.php");
        exit();
    }

    $codigo = rand(100000, 999999);
    $_SESSION["codigoVerificacao"] = $codigo;

    // Envia o código por e-mail com um design moderno
    $assunto = "Código de Confirmação - Nexus";
    $mensagem = "
<!DOCTYPE html>
<html lang='pt'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Verificação Nexus</title>
</head>
<body style='font-family: Arial, sans-serif; background-color: #ffffff; color: #ffffff; padding: 20px; text-align: center;'>
    <div style='max-width: 600px; margin: auto; background-color: #0e2b3b; padding: 20px; border-radius: 10px;'>
        <img src='https://cdn.pixabay.com/photo/2025/02/05/09/29/internet-9383803_1280.png' alt='Nexus Logo' style='width: 80px; margin-bottom: 10px;'>
        <h2 style='color: #ffffff;'>Bem-vindo à Nexus!</h2>
        <p>Olá <strong>{$nome}</strong>,</p>
        <p>Parece que estás a tentar criar uma conta na Nexus. Aqui está o código de verificação que precisas para continuar:</p>
        <div style='background-color: #ffffff; padding: 10px; border-radius: 5px; display: inline-block; margin: 10px 0;'>
            <p style='font-size: 32px; font-weight: bold; color: #0e2b3b; margin: 0;'>{$codigo}</p>
        </div>
        <p>Se não foste tu que fizeste este pedido, ignora este e-mail.</p>
        <p style='color: #ccc;'>Atenciosamente, <br> Equipa Nexus</p>
    </div>
</body>
</html>
";


    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= "From: Nexus <noreply@nexus.com>" . "\r\n";

    if (mail($email, $assunto, $mensagem, $headers)) {
        $_SESSION["emailEnviado"] = true;
        $_SESSION["mensagem"] = "Código de confirmação enviado para $email.";
        header("Location: confirm_codigo.php");
        exit();
    } else {
        $_SESSION["erro"] = "Erro ao enviar o email de confirmação.";
        header("Location: signup.php");
        exit();
    }
}

if (isset($_POST["verificarCodigo"])) {
    $codigoInserido = $_POST["codigoInserido"];

    if ($_SESSION["codigoVerificacao"] != $codigoInserido) {
        $_SESSION["erro"] = "Código incorreto. Tente novamente.";
        header("Location: confirm_codigo.php");
        exit();
    }

    unset($_SESSION["codigoVerificacao"]);
    unset($_SESSION["emailEnviado"]);

    $dados = $_SESSION['tempUserData'];
    $dataRegistro = date("Y-m-d");

    $sql_inserir = "INSERT INTO utilizador 
                    VALUES (NULL, '"
        . $dados["nome"] . "', '"
        . $dados["email"] . "', '"
        . $dados["user"] . "', '"
        . $dados["telemovel"] . "', '"
        . $dados["data_nascimento"] . "',  '$dataRegistro', password('" . $dados["password"] . "'), '"
        . $dados["pais"] . "', NULL, NULL, '1')";

    if (!mysqli_query($con, $sql_inserir)) {
        $_SESSION["erro"] = "Erro ao registrar usuário.";
        header("Location: signup.php");
        exit();
    }

    unset($_SESSION['tempUserData']);
    $_SESSION["sucesso"] = "Bem-Vindo à Nexus! Faça login para começar";
    header("Location: index.php");
    exit();
}

header("Location: signup.php");
exit();
?>