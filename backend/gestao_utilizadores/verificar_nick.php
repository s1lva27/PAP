<?php
require "../backend/ligabd.php";

if (isset($_GET['nick'])) {
    $nick = mysqli_real_escape_string($con, trim($_GET['nick']));
    $query = "SELECT * FROM utilizadores WHERE nick = '$nick'";
    $result = mysqli_query($con, $query);

    echo (mysqli_num_rows($result) > 0) ? "exist" : "available";
}
?>
