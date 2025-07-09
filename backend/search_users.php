<?php
session_start();
require "ligabd.php";

header('Content-Type: application/json');

if (!isset($_SESSION['id'])) {
    echo json_encode([]);
    exit;
}

if (!isset($_GET['q']) || strlen(trim($_GET['q'])) < 2) {
    echo json_encode([]);
    exit;
}

$currentUserId = $_SESSION['id'];
$query = trim($_GET['q']);
$searchTerm = '%' . $query . '%';

$sql = "SELECT u.id, u.nick, u.nome_completo, p.foto_perfil 
        FROM utilizadores u 
        LEFT JOIN perfis p ON u.id = p.id_utilizador 
        WHERE u.id != ? 
        AND (u.nome_completo LIKE ? OR u.nick LIKE ?)
        ORDER BY u.nome_completo ASC 
        LIMIT 10";

$stmt = $con->prepare($sql);
$stmt->bind_param("iss", $currentUserId, $searchTerm, $searchTerm);
$stmt->execute();
$result = $stmt->get_result();

$users = [];
while ($row = $result->fetch_assoc()) {
    $users[] = $row;
}

echo json_encode($users);
?>