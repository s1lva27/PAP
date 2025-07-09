<?php
session_start();

if (!isset($_POST["botaoInserir"]) || !isset($_SESSION["nick"]) || $_SESSION["id_tipos_utilizador"] != 2) {
    header("Location: ../../frontend/editar_utilizadores.php");
    exit();
}

require "../ligabd.php";

// Obter e sanitizar os dados
$nome_completo = trim($_POST["nome_completo"]);
$nick = trim($_POST["nick"]);
$password = trim($_POST["password"]);
$email = trim($_POST["email"]);
$id_tipos_utilizador = (int)$_POST["id_tipos_utilizador"];

// Validações detalhadas
$errors = [];

// Validar nome completo
if (empty($nome_completo)) {
    $errors[] = "O nome completo é obrigatório.";
} elseif (strlen($nome_completo) < 3) {
    $errors[] = "O nome completo deve ter pelo menos 3 caracteres.";
} elseif (!preg_match("/^[a-zA-ZÀ-ÿ\s]{3,}$/u", $nome_completo)) {
    $errors[] = "O nome completo deve conter apenas letras e espaços.";
}

// Validar nick
if (empty($nick)) {
    $errors[] = "O nome de utilizador é obrigatório.";
} elseif (strlen($nick) < 3 || strlen($nick) > 16) {
    $errors[] = "O nome de utilizador deve ter entre 3 e 16 caracteres.";
} elseif (!preg_match("/^[a-zA-Z0-9._]{3,16}$/", $nick)) {
    $errors[] = "O nome de utilizador só pode conter letras, números, pontos e sublinhados.";
}

// Validar email
if (empty($email)) {
    $errors[] = "O email é obrigatório.";
} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = "O formato do email é inválido.";
}

// Validar password
if (empty($password)) {
    $errors[] = "A palavra-passe é obrigatória.";
} elseif (strlen($password) < 6) {
    $errors[] = "A palavra-passe deve ter pelo menos 6 caracteres.";
}

// Validar tipo de utilizador
if (!in_array($id_tipos_utilizador, [0, 2])) {
    $errors[] = "Tipo de utilizador inválido.";
}

// Se há erros de validação, retornar
if (!empty($errors)) {
    $_SESSION["erro"] = implode(" ", $errors);
    header("Location: ../../frontend/editar_utilizadores.php");
    exit();
}

// Verificar se nick já existe
$nick_escaped = mysqli_real_escape_string($con, $nick);
$sql_check_nick = "SELECT id FROM utilizadores WHERE nick = '$nick_escaped'";
$result_nick = mysqli_query($con, $sql_check_nick);

if (mysqli_num_rows($result_nick) > 0) {
    $_SESSION["erro"] = "O nome de utilizador '$nick' já está em uso.";
    header("Location: ../../frontend/editar_utilizadores.php");
    exit();
}

// Verificar se email já existe
$email_escaped = mysqli_real_escape_string($con, $email);
$sql_check_email = "SELECT id FROM utilizadores WHERE email = '$email_escaped'";
$result_email = mysqli_query($con, $sql_check_email);

if (mysqli_num_rows($result_email) > 0) {
    $_SESSION["erro"] = "O email '$email' já está registado.";
    header("Location: ../../frontend/editar_utilizadores.php");
    exit();
}

// Iniciar transação
mysqli_begin_transaction($con);

try {
    // Escapar dados para inserção
    $nome_completo_escaped = mysqli_real_escape_string($con, $nome_completo);
    $password_escaped = mysqli_real_escape_string($con, $password);

    // Inserir utilizador
    $sql_inserir = "INSERT INTO utilizadores (nome_completo, email, palavra_passe, nick, id_tipos_utilizador) 
                    VALUES ('$nome_completo_escaped', '$email_escaped', password('$password_escaped'), '$nick_escaped', '$id_tipos_utilizador')";
    
    $result = mysqli_query($con, $sql_inserir);
    
    if (!$result) {
        throw new Exception("Erro ao inserir utilizador: " . mysqli_error($con));
    }

    // Obter ID do utilizador inserido
    $user_id = mysqli_insert_id($con);

    // Criar perfil padrão para o utilizador
    $sql_perfil = "INSERT INTO perfis (id_utilizador, biografia, foto_perfil, foto_capa, ocupacao, pais, cidade, x, linkedin, github) 
                   VALUES ($user_id, '', 'default-profile.jpg', 'default-capa.png', '', '', '', '', '', '')";
    
    $result_perfil = mysqli_query($con, $sql_perfil);
    
    if (!$result_perfil) {
        throw new Exception("Erro ao criar perfil: " . mysqli_error($con));
    }

    // Confirmar transação
    mysqli_commit($con);
    
    $tipo_texto = ($id_tipos_utilizador == 2) ? "administrador" : "utilizador";
    $_SESSION["sucesso"] = "Utilizador '$nick' criado com sucesso como $tipo_texto!";

} catch (Exception $e) {
    // Reverter transação em caso de erro
    mysqli_rollback($con);
    $_SESSION["erro"] = $e->getMessage();
    error_log("Erro ao inserir utilizador: " . $e->getMessage());
}

header("Location: ../../frontend/editar_utilizadores.php");
exit();
?>