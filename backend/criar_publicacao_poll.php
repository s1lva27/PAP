<?php
session_start();
include "ligabd.php";

// Verificar se o utilizador está autenticado
if (!isset($_SESSION['id'])) {
    $_SESSION['erro'] = 'Não autenticado';
    header('Location: ../frontend/index.php');
    exit;
}

// Verificar se é uma requisição POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['erro'] = 'Método não permitido';
    header('Location: ../frontend/index.php');
    exit;
}

// Verificar se é uma requisição AJAX
$isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
          strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';

// Função para retornar resposta
function returnResponse($success, $message, $data = null) {
    global $isAjax;
    
    if ($isAjax) {
        header('Content-Type: application/json');
        $response = ['success' => $success, 'message' => $message];
        if ($data) {
            $response = array_merge($response, $data);
        }
        echo json_encode($response);
        exit;
    } else {
        $_SESSION[$success ? 'sucesso' : 'erro'] = $message;
        header('Location: ../frontend/index.php');
        exit;
    }
}

// Obter e validar dados
$pergunta = trim($_POST['pergunta'] ?? '');
$conteudo = trim($_POST['conteudo'] ?? '');
$opcoes = $_POST['opcoes'] ?? [];
$duracao = intval($_POST['duracao'] ?? 24);

// Validações
if (empty($pergunta)) {
    returnResponse(false, 'A pergunta é obrigatória');
}

if (strlen($pergunta) > 500) {
    returnResponse(false, 'A pergunta não pode ter mais de 500 caracteres');
}

// Filtrar e validar opções
$opcoes = array_filter(array_map('trim', $opcoes), function($opcao) {
    return !empty($opcao);
});

if (count($opcoes) < 2) {
    returnResponse(false, 'São necessárias pelo menos 2 opções');
}

if (count($opcoes) > 4) {
    returnResponse(false, 'Máximo de 4 opções permitidas');
}

// Verificar opções duplicadas
if (count($opcoes) !== count(array_unique($opcoes))) {
    returnResponse(false, 'Não é possível ter opções duplicadas');
}

// Validar duração
if ($duracao < 1 || $duracao > 168) {
    returnResponse(false, 'A duração deve estar entre 1 e 168 horas');
}

// Calcular data de expiração
$dataExpiracao = date('Y-m-d H:i:s', strtotime("+{$duracao} hours"));

// Iniciar transação
$con->begin_transaction();

try {
    // 1. Criar a publicação
    $stmt = $con->prepare("INSERT INTO publicacoes (id_utilizador, conteudo, tipo, data_criacao) 
                          VALUES (?, ?, 'poll', NOW())");
    $stmt->bind_param("is", $_SESSION['id'], $conteudo);
    
    if (!$stmt->execute()) {
        throw new Exception('Erro ao criar publicação: ' . $stmt->error);
    }
    
    $publicacaoId = $con->insert_id;

    // 2. Criar a poll
    $stmt = $con->prepare("INSERT INTO polls (publicacao_id, pergunta, data_expiracao, total_votos) 
                          VALUES (?, ?, ?, 0)");
    $stmt->bind_param("iss", $publicacaoId, $pergunta, $dataExpiracao);
    
    if (!$stmt->execute()) {
        throw new Exception('Erro ao criar poll: ' . $stmt->error);
    }
    
    $pollId = $con->insert_id;

    // 3. Adicionar opções
    $stmt = $con->prepare("INSERT INTO poll_opcoes (poll_id, opcao_texto, votos, ordem) 
                          VALUES (?, ?, 0, ?)");
    
    $ordem = 1;
    foreach ($opcoes as $opcao) {
        $stmt->bind_param("isi", $pollId, $opcao, $ordem);
        if (!$stmt->execute()) {
            throw new Exception('Erro ao adicionar opção: ' . $stmt->error);
        }
        $ordem++;
    }

    // Confirmar transação
    $con->commit();
    
    returnResponse(true, 'Poll criada com sucesso!', [
        'poll_id' => $pollId,
        'publicacao_id' => $publicacaoId
    ]);
    
} catch (Exception $e) {
    // Reverter transação em caso de erro
    $con->rollback();
    error_log('Erro ao criar poll: ' . $e->getMessage());
    returnResponse(false, 'Erro ao criar poll: ' . $e->getMessage());
}
?>