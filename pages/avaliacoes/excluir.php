<?php
require_once '../../includes/config.php';
require_once '../../includes/auth.php';
require_once '../../includes/db.php';

if (!isLoggedIn()) {
    header('Location: ../../login.php');
    exit();
}

if (!isset($_GET['id'])) {
    header('Location: listar.php');
    exit();
}

$avaliacaoId = $_GET['id'];
$usuarioId = getCurrentUserId();

// Verificar se a avaliação pertence ao usuário antes de excluir
$stmt = $db->prepare("DELETE FROM avaliacoes WHERE id = :id AND usuario_id = :usuario_id");
$stmt->bindValue(':id', $avaliacaoId);
$stmt->bindValue(':usuario_id', $usuarioId);
$resultado = $stmt->execute();

if ($resultado) {
    header('Location: listar.php?excluido=1');
} else {
    header('Location: listar.php?erro_excluir=1');
}
exit();
