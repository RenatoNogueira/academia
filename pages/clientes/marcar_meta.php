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
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['id']) || !isset($_POST['concluida'])) {
    header('Location: ../clientes/listar.php');
    exit();
}

$currentUserId = getCurrentUserId();
$metaId = $_POST['id'];
$concluida = $_POST['concluida'] === '1';

// Verificar se a meta pertence ao usuário logado
$stmt = $db->prepare("
    SELECT m.id, m.cliente_id
    FROM metas m
    JOIN clientes c ON m.cliente_id = c.id
    WHERE m.id = :id AND c.usuario_id = :usuario_id
");
$stmt->bindValue(':id', $metaId);
$stmt->bindValue(':usuario_id', $currentUserId);
$result = $stmt->execute();
$meta = $result->fetchArray(SQLITE3_ASSOC);

if (!$meta) {
    redirectWithMessage("../clientes/listar.php", 'error', 'Meta não encontrada ou você não tem permissão para alterá-la.');
}

// Atualizar o status da meta
$stmt = $db->prepare("
    UPDATE metas
    SET concluida = :concluida
    WHERE id = :id
");
$stmt->bindValue(':concluida', $concluida ? 1 : 0, SQLITE3_INTEGER);
$stmt->bindValue(':id', $metaId);

if ($stmt->execute()) {
    redirectWithMessage("../clientes/detalhes.php?id={$meta['cliente_id']}#metas", 'success', 'Meta atualizada com sucesso!');
} else {
    redirectWithMessage("../clientes/detalhes.php?id={$meta['cliente_id']}#metas", 'error', 'Erro ao atualizar meta.');
}