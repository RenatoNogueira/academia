<?php
require_once '../../includes/config.php';
require_once '../../includes/auth.php';
require_once '../../includes/db.php';
require_once '../../includes/functions.php';

if (!isLoggedIn()) {
    header('Location: ../../login.php');
    exit();
}

// Verificar se a requisição é POST e tem os dados necessários
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['cliente_id'])) {
    header('Location: ../clientes/listar.php');
    exit();
}

$currentUserId = getCurrentUserId();
$clienteId = $_POST['cliente_id'];

// Verificar se o cliente pertence ao usuário logado
$stmt = $db->prepare("SELECT id FROM clientes WHERE id = :id AND usuario_id = :usuario_id");
$stmt->bindValue(':id', $clienteId);
$stmt->bindValue(':usuario_id', $currentUserId);
$result = $stmt->execute();

if (!$result->fetchArray()) {
    redirectWithMessage("../clientes/detalhes.php?id=$clienteId#metas", 'error', 'Cliente não encontrado ou você não tem permissão para adicionar metas.');
}

try {
    // Validar e sanitizar os dados
    $descricao = trim($_POST['descricao']);
    $valorAlvo = isset($_POST['valor_alvo']) ? trim($_POST['valor_alvo']) : null;
    $dataLimite = isset($_POST['data_limite']) ? trim($_POST['data_limite']) : null;

    // Validações
    if (empty($descricao)) {
        throw new Exception('A descrição da meta é obrigatória.');
    }

    if ($valorAlvo !== null && !is_numeric(str_replace(',', '.', $valorAlvo))) {
        throw new Exception('O valor alvo deve ser um número válido.');
    }

    if ($dataLimite !== null && !strtotime($dataLimite)) {
        throw new Exception('Data limite inválida.');
    }

    // Formatar valores
    $valorAlvo = $valorAlvo !== null ? (float)str_replace(',', '.', $valorAlvo) : null;
    $dataLimite = $dataLimite !== null ? date('Y-m-d', strtotime($dataLimite)) : null;

    // Inserir a meta no banco de dados
    $stmt = $db->prepare("
        INSERT INTO metas
        (cliente_id, descricao, valor_alvo, data_limite, usuario_id)
        VALUES
        (:cliente_id, :descricao, :valor_alvo, :data_limite, :usuario_id)
    ");

    $stmt->bindValue(':cliente_id', $clienteId);
    $stmt->bindValue(':descricao', $descricao);
    $stmt->bindValue(':valor_alvo', $valorAlvo);
    $stmt->bindValue(':data_limite', $dataLimite);
    $stmt->bindValue(':usuario_id', $currentUserId);

    if ($stmt->execute()) {
        redirectWithMessage("../clientes/detalhes.php?id=$clienteId#metas", 'success', 'Meta adicionada com sucesso!');
    } else {
        throw new Exception('Erro ao adicionar meta no banco de dados.');
    }
} catch (Exception $e) {
    redirectWithMessage("../clientes/detalhes.php?id=$clienteId#metas", 'error', $e->getMessage());
}