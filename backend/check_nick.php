<?php
require 'ligabd.php';
$nick = $_GET['nick'];
$query = "SELECT * FROM utilizadores WHERE nick = ?";
$stmt = $con->prepare($query);
$stmt->bind_param("s", $nick);
$stmt->execute();
$stmt->store_result();
echo $stmt->num_rows > 0 ? 'exist' : 'not-exist';
$stmt->close();
?>
