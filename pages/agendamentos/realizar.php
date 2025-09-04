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

// Verificar se o agendamento existe e pode ser marcado como realizado
$stmt = $db->prepare("
    SELECT id, tipo FROM agendamentos
    WHERE id = :id AND usuario_id = :usuario_id
    AND status IN ('pendente', 'confirmado')
");
$stmt->bindValue(':id', $agendamentoId);
$stmt->bindValue(':usuario_id', $currentUserId);
$result = $stmt->execute();
$agendamento = $result->fetchArray(SQLITE3_ASSOC);

if (!$agendamento) {
    redirectWithMessage('listar.php', 'error', 'Agendamento não encontrado ou não pode ser marcado como realizado.');
}

// Atualizar o status para realizado
$stmt = $db->prepare("
    UPDATE agendamentos
    SET status = 'realizado'
    WHERE id = :id
");
$stmt->bindValue(':id', $agendamentoId);

if ($stmt->execute()) {
    // Se for uma avaliação física, redirecionar para criar avaliação
    if ($agendamento['tipo'] === 'Avaliação Física') {
        // Verificar se já existe uma avaliação para este agendamento
        $stmt = $db->prepare("
            SELECT id FROM avaliacoes
            WHERE agendamento_id = :agendamento_id
        ");
        $stmt->bindValue(':agendamento_id', $agendamentoId);
        $result = $stmt->execute();

        if (!$result->fetchArray()) {
            // Obter cliente_id do agendamento
            $stmt = $db->prepare("
                SELECT cliente_id FROM agendamentos
                WHERE id = :id
            ");
            $stmt->bindValue(':id', $agendamentoId);
            $result = $stmt->execute();
            $agendamento = $result->fetchArray(SQLITE3_ASSOC);

            redirectWithMessage(
                '../avaliacoes/registrar.php?cliente_id=' . $agendamento['cliente_id'] . '&agendamento_id=' . $agendamentoId,
                'info',
                'Agendamento marcado como realizado. Agora você pode registrar a avaliação física.'
            );
        }
    }

    redirectWithMessage('listar.php', 'success', 'Agendamento marcado como realizado com sucesso!');
} else {
    redirectWithMessage('listar.php', 'error', 'Erro ao marcar agendamento como realizado.');
}