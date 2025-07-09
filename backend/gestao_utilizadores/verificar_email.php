<?php
require "../backend/ligabd.php";

if (isset($_GET['email'])) {
    $email = mysqli_real_escape_string($con, trim($_GET['email']));
    $query = "SELECT * FROM utilizadores WHERE email = '$email'";
    $result = mysqli_query($con, $query);

    echo (mysqli_num_rows($result) > 0) ? "exist" : "available";
}
?>
