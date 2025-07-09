<?php
session_start();
require "ligabd.php";

header('Content-Type: application/json');

if (!isset($_SESSION['id']) || empty($_SESSION['id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Sessão inválida ou expirada']);
    exit;
}

$userId = $_SESSION['id'];
$limit = isset($_GET['limit']) ? intval($_GET['limit']) : 20;
$offset = isset($_GET['offset']) ? intval($_GET['offset']) : 0;

// Primeiro obter o número de notificações não lidas ANTES de marcar como lidas
$sqlUnread = "SELECT COUNT(*) as total FROM notificacoes WHERE utilizador_id = ? AND lida = 0";
$stmtUnread = $con->prepare($sqlUnread);
$stmtUnread->bind_param("i", $userId);
$stmtUnread->execute();
$unreadResult = $stmtUnread->get_result()->fetch_assoc();
$unreadCount = (int)$unreadResult['total'];

// Marcar como lidas apenas se houver notificações não lidas e for o primeiro carregamento
if ($unreadCount > 0 && $offset == 0) {
    $sqlMarkRead = "UPDATE notificacoes SET lida = 1 WHERE utilizador_id = ? AND lida = 0";
    $stmtMarkRead = $con->prepare($sqlMarkRead);
    $stmtMarkRead->bind_param("i", $userId);
    $stmtMarkRead->execute();
}

// Buscar notificações
$sql = "SELECT n.*, 
               u.nick as remetente_nick, 
               u.nome_completo as remetente_nome,
               p.foto_perfil as remetente_foto,
               pub.conteudo as publicacao_conteudo
        FROM notificacoes n
        JOIN utilizadores u ON n.remetente_id = u.id
        LEFT JOIN perfis p ON u.id = p.id_utilizador
        LEFT JOIN publicacoes pub ON n.publicacao_id = pub.id_publicacao
        WHERE n.utilizador_id = ?
        ORDER BY n.data_criacao DESC
        LIMIT ? OFFSET ?";

$stmt = $con->prepare($sql);
$stmt->bind_param("iii", $userId, $limit, $offset);
$stmt->execute();
$result = $stmt->get_result();

$notifications = [];
while ($row = $result->fetch_assoc()) {
    $notifications[] = $row;
}

echo json_encode([
    'success' => true,
    'notifications' => $notifications,
    'unread_count' => $unreadCount // Retornar o valor ANTES de marcar como lidas
]);
?>