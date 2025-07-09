<?php
$servername ="localhost" ;
$username = "root";
$password_liga = "";

$con = mysqli_connect($servername, username: $username, password: $password_liga);

if (!$con) {
    die("Erro ao conectar ao MySQL:". mysqli_connect_error());
}

$escolheBD = mysqli_select_db($con,'orange');

if (!$escolheBD) {
    echo "Erro: não foi possível aceder à Base de Dados";
    exit;
}