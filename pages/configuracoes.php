<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

if (!isLoggedIn()) {
    header('Location: ../../login.php');
    exit();
}

// Verificar se foi passado um ID
if (!isset($_GET['id'])) {
    header('Location: listar.php');
    exit();
}

$agendamentoId = $_GET['id'];
$currentUserId = getCurrentUserId();

// Verificar se o agendamento existe e pertence ao usuário
$stmt = $db->prepare("
    SELECT id FROM agendamentos
    WHERE id = :id AND usuario_id = :usuario_id
");
$stmt->bindValue(':id', $agendamentoId);
$stmt->bindValue(':usuario_id', $currentUserId);
$result = $stmt->execute();

if (!$result->fetchArray()) {
    redirectWithMessage('listar.php', 'error', 'Agendamento não encontrado.');
}

// Excluir o agendamento
$stmt = $db->prepare("
    DELETE FROM agendamentos
    WHERE id = :id
");
$stmt->bindValue(':id', $agendamentoId);

if ($stmt->execute()) {
    redirectWithMessage('listar.php', 'success', 'Agendamento excluído com sucesso!');
} else {
    redirectWithMessage('listar.php', 'error', 'Erro ao excluir agendamento.');
}