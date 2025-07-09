<?php
require 'ligabd.php';
$email = $_GET['email'];
$query = "SELECT * FROM utilizadores WHERE email = ?";
$stmt = $con->prepare($query);
$stmt->bind_param("s", $email);
$stmt->execute();
$stmt->store_result();
echo $stmt->num_rows > 0 ? 'exist' : 'not-exist';
$stmt->close();
?>
