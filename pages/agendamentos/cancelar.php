<?php
require_once '../../includes/config.php';
require_once '../../includes/auth.php';
require_once '../../includes/db.php';
require_once '../../includes/functions.php';

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

// Verificar se o agendamento existe e pode ser cancelado
$stmt = $db->prepare("
    SELECT id FROM agendamentos
    WHERE id = :id AND usuario_id = :usuario_id
    AND status IN ('pendente', 'confirmado')
");
$stmt->bindValue(':id', $agendamentoId);
$stmt->bindValue(':usuario_id', $currentUserId);
$result = $stmt->execute();

if (!$result->fetchArray()) {
    redirectWithMessage('listar.php', 'error', 'Agendamento não encontrado ou não pode ser cancelado.');
}

// Atualizar o status para cancelado
$stmt = $db->prepare("
    UPDATE agendamentos
    SET status = 'cancelado'
    WHERE id = :id
");
$stmt->bindValue(':id', $agendamentoId);

if ($stmt->execute()) {
    // Registrar motivo do cancelamento se fornecido
    if (!empty($_POST['motivo'])) {
        $observacoes = "CANCELADO - Motivo: " . trim($_POST['motivo']);
        $stmt = $db->prepare("
            UPDATE agendamentos
            SET observacoes = :observacoes
            WHERE id = :id
        ");
        $stmt->bindValue(':id', $agendamentoId);
        $stmt->bindValue(':observacoes', $observacoes);
        $stmt->execute();
    }

    redirectWithMessage('listar.php', 'success', 'Agendamento cancelado com sucesso!');
} else {
    redirectWithMessage('listar.php', 'error', 'Erro ao cancelar agendamento.');
}