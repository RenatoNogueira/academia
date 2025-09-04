<?php
require_once '../../includes/config.php';
require_once '../../includes/auth.php';
require_once '../../includes/db.php';
require_once '../../includes/functions.php';

if (!isLoggedIn()) {
    header('Location: ../../login.php');
    exit();
}

// Verificar se foi passado um ID de meta
if (!isset($_GET['id'])) {
    header('Location: ../clientes/listar.php');
    exit();
}

$currentUserId = getCurrentUserId();
$metaId = $_GET['id'];

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
    redirectWithMessage("../clientes/listar.php", 'error', 'Meta não encontrada ou você não tem permissão para excluí-la.');
}

// Excluir a meta
$stmt = $db->prepare("DELETE FROM metas WHERE id = :id");
$stmt->bindValue(':id', $metaId);

if ($stmt->execute()) {
    redirectWithMessage("../clientes/detalhes.php?id={$meta['cliente_id']}#metas", 'success', 'Meta excluída com sucesso!');
} else {
    redirectWithMessage("../clientes/detalhes.php?id={$meta['cliente_id']}#metas", 'error', 'Erro ao excluir meta.');
}