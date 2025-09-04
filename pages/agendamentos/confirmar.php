<?php
require_once '../../includes/config.php';
require_once '../../includes/auth.php';
require_once '../../includes/db.php';
require_once '../../includes/functions.php';

if (!isLoggedIn()) {
    header('Location: ../../login.php');
    exit();
}

if (!isset($_GET['id'])) {
    header('Location: listar.php');
    exit();
}

$agendamentoId = $_GET['id'];

// Verificar se o agendamento existe e pertence ao usuário
$stmt = $db->prepare("
    SELECT id FROM agendamentos
    WHERE id = :id AND usuario_id = :usuario_id AND status = 'pendente'
");
$stmt->bindValue(':id', $agendamentoId);
$stmt->bindValue(':usuario_id', getCurrentUserId());
$result = $stmt->execute();

if (!$result->fetchArray()) {
    header('Location: listar.php');
    exit();
}

// Atualizar o status para confirmado
$stmt = $db->prepare("
    UPDATE agendamentos
    SET status = 'confirmado'
    WHERE id = :id
");
$stmt->bindValue(':id', $agendamentoId);

if ($stmt->execute()) {
    redirectWithMessage('listar.php', 'success', 'Agendamento confirmado com sucesso!');
} else {
    redirectWithMessage('listar.php', 'error', 'Erro ao confirmar agendamento.');
}